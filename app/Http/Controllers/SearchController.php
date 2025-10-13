<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function products(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 20);
        $ajax = (bool) $request->boolean('ajax', false);

        if ($q === '') {
            if ($ajax) {
                return response()->json(['data' => [], 'q' => $q]);
            }
            return Inertia::render('Frontend/SearchResults', [
                'q' => $q,
                'results' => [
                    'data' => [],
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $normalizedQ = $this->normalize($q);
        $currentPage = max(1, (int) $request->query('page', 1));
        $cacheKey = 'search:v1:' . ($ajax ? 'a' : 'f') . ":{$perPage}:{$currentPage}:" . md5($normalizedQ);

        $payload = Cache::remember($cacheKey, 60, function () use ($q, $normalizedQ, $perPage, $currentPage, $ajax) {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $baseLimit = $ajax ? 50 : 200;

            $prefiltered = ProductVariant::query()
                ->with([
                    'product' => function ($p) {
                        $p->select('id', 'slug', 'name', 'brand_id', 'status_id');
                    },
                    'product.brand:id,name',
                    'product.primaryImage:id,product_id,image_path',
                ])
                ->whereHas('product', function ($qp) use ($like) {
                    $qp->where('status_id', 2)
                       ->where(function ($w) use ($like) {
                           $w->where('name', 'like', $like)
                             ->orWhere('slug', 'like', $like)
                             ->orWhere('product_code', 'like', $like)
                             ->orWhere('meta_keywords', 'like', $like);
                       });
                })
                ->orWhere('sku', 'like', $like)
                ->limit($baseLimit)
                ->get(['id', 'product_id', 'regular_price', 'selling_price', 'stock', 'sku', 'created_at']);

            $scored = $prefiltered->map(function ($variant) use ($q) {
                $product = $variant->product;
                $fields = [
                    (string) ($variant->display_name ?? ''),
                    (string) ($product->name ?? ''),
                    (string) ($variant->sku ?? ''),
                    (string) ($product->brand->name ?? ''),
                    (string) ($product->product_code ?? ''),
                ];

                $score = $this->fuzzyScore($q, $fields);

                return [
                    'score' => $score,
                    'variant' => $variant,
                ];
            })
            ->filter(fn ($row) => $row['score'] > 0.2)
            ->sortByDesc('score')
            ->values();

            $total = $scored->count();
            $start = ($currentPage - 1) * $perPage;
            $pageItems = $scored->slice($start, $perPage)->values();

            $data = $pageItems->map(function ($row) {
                $v = $row['variant'];
                $p = $v->product;

                $imagePath = optional($p->primaryImage)->image_path;
                $image = $imagePath ? (str_starts_with($imagePath, 'http') ? $imagePath : Storage::disk('s3')->url($imagePath)) : null;

                return [
                    'id' => $v->id,
                    'hashid' => $v->hashid,
                    'product_slug' => $p->slug,
                    'name' => $v->display_name ?? $p->name,
                    'brand' => $p->brand->name ?? null,
                    'regular_price' => $v->regular_price,
                    'selling_price' => $v->selling_price,
                    'discount' => $v->discount_percent ?? null,
                    'in_stock' => $v->in_stock,
                    'primary_image_url' => $image,
                ];
            });

            return [
                'data' => $data,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ];
        });

        if ($ajax || $request->wantsJson()) {
            return response()->json(['q' => $q, 'results' => $payload]);
        }

        return Inertia::render('Frontend/SearchResults', [
            'q' => $q,
            'results' => $payload,
        ]);
    }

    private function fuzzyScore(string $query, array $fields): float
    {
        $queryNorm = $this->normalize($query);
        if ($queryNorm === '') return 0.0;

        $best = 0.0;
        foreach ($fields as $field) {
            $text = $this->normalize($field);
            if ($text === '') continue;

            if (str_contains($text, $queryNorm)) {
                $best = max($best, 0.9);
            }

            $lev = $this->levenshteinSimilarity($queryNorm, $text);
            $best = max($best, $lev);

            $sound = $this->phoneticSimilarity($queryNorm, $text);
            $best = max($best, $sound * 0.8);
        }
        return $best;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function levenshteinSimilarity(string $a, string $b): float
    {
        $la = strlen($a);
        $lb = strlen($b);
        if ($la === 0 || $lb === 0) return 0.0;
        $max = max($la, $lb);

        $distance = levenshtein(substr($a, 0, 255), substr($b, 0, 255));
        $sim = 1 - ($distance / $max);
        return max(0.0, min(1.0, $sim));
    }

    private function phoneticSimilarity(string $a, string $b): float
    {
        $ma = metaphone($a);
        $mb = metaphone($b);
        if ($ma === '' || $mb === '') return 0.0;
        similar_text($ma, $mb, $pct);
        return $pct / 100.0;
    }
}
