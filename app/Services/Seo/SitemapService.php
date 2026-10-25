<?php

namespace App\Services\Seo;

use App\Models\Category;
use App\Models\Policy\Policy;
use App\Models\Products\Product;
use Illuminate\Support\Carbon;

/**
 * Builds XML sitemaps for the public storefront.
 *
 * Sections:
 *  - static      : home, search, sell, terms, marketplace verticals, active policies
 *  - categories  : active categories (root + children) -> /{slug}
 *  - products-N  : active products (status_id = 2) -> /products/{slug}, chunked
 *
 * sitemap.xml is a sitemap index pointing at each section.
 */
class SitemapService
{
    /** Google's hard limit is 50 000 URLs / 50 MB per file; stay well under it. */
    public const PRODUCTS_PER_FILE = 10000;

    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: config('app.url'), '/');
    }

    /** All section names, e.g. ['static', 'categories', 'products-1', ...]. */
    public function sections(): array
    {
        $sections = ['static', 'categories'];
        $pages = max(1, (int) ceil(Product::active()->count() / self::PRODUCTS_PER_FILE));
        for ($i = 1; $i <= $pages; $i++) {
            $sections[] = "products-{$i}";
        }

        return $sections;
    }

    public function index(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->sections() as $section) {
            $xml .= "  <sitemap>\n    <loc>" . $this->esc("{$this->baseUrl}/sitemap-{$section}.xml") . "</loc>\n";
            if ($lastmod = $this->sectionLastmod($section)) {
                $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            }
            $xml .= "  </sitemap>\n";
        }

        return $xml . '</sitemapindex>';
    }

    /** Render a single section, or null if the section name is unknown. */
    public function section(string $name): ?string
    {
        if ($name === 'static') {
            return $this->urlset($this->staticUrls());
        }
        if ($name === 'categories') {
            return $this->urlset($this->categoryUrls());
        }
        if (preg_match('/^products-(\d+)$/', $name, $m)) {
            $page = (int) $m[1];
            if ($page < 1) {
                return null;
            }
            $urls = $this->productUrls($page);

            return ($page === 1 || $urls !== []) ? $this->urlset($urls) : null;
        }

        return null;
    }

    // ---------------------------------------------------------------- URL sources

    public function staticUrls(): array
    {
        $urls = [
            $this->url('/', null, 'daily', '1.0'),
            $this->url('/search', null, 'daily', '0.6'),
            $this->url('/marketplace', null, 'weekly', '0.6'),
            $this->url('/sell', null, 'monthly', '0.5'),
            $this->url('/terms', null, 'yearly', '0.3'),
        ];

        $default = config('marketplace.default', 'ecommerce');
        foreach (array_keys(config('marketplace.verticals', [])) as $vertical) {
            if ($vertical !== $default) {
                $urls[] = $this->url("/marketplace/{$vertical}", null, 'daily', '0.8');
            }
        }

        Policy::active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Policy $p) use (&$urls) {
                $urls[] = $this->url("/policies/{$p->slug}", $p->updated_at, 'yearly', '0.3');
            });

        return $urls;
    }

    public function categoryUrls(): array
    {
        return Category::active()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get(['slug', 'parent_id', 'updated_at'])
            ->map(fn (Category $c) => $this->url(
                "/{$c->slug}",
                $c->updated_at,
                'daily',
                $c->parent_id ? '0.7' : '0.8'
            ))
            ->all();
    }

    public function productUrls(int $page = 1): array
    {
        return Product::active()
            ->with('primaryImage:id,product_id,image_path')
            ->orderBy('id')
            ->forPage($page, self::PRODUCTS_PER_FILE)
            ->get(['id', 'slug', 'name', 'updated_at'])
            ->map(function (Product $p) {
                $url = $this->url("/products/{$p->slug}", $p->updated_at, 'weekly', '0.9');
                if ($p->primaryImage) {
                    // Image sitemap extension: lets Google Images index product photos.
                    $url['images'] = [[
                        'loc'   => $this->baseUrl . '/storage/' . ltrim($p->primaryImage->image_path, '/'),
                        'title' => $p->name,
                    ]];
                }

                return $url;
            })
            ->all();
    }

    // ---------------------------------------------------------------- helpers

    private function sectionLastmod(string $section): ?string
    {
        $ts = match (true) {
            $section === 'static'     => Policy::active()->max('updated_at'),
            $section === 'categories' => Category::active()->max('updated_at'),
            default                   => Product::active()->max('updated_at'),
        };

        return $ts ? Carbon::parse($ts)->toAtomString() : null;
    }

    private function url(string $path, $lastmod, string $changefreq, string $priority): array
    {
        $path = '/' . ltrim($path, '/');

        return [
            'loc'        => $path === '/' ? $this->baseUrl . '/' : $this->baseUrl . $path,
            'lastmod'    => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null,
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }

    private function urlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>" . $this->esc($u['loc']) . "</loc>\n";
            if ($u['lastmod']) {
                $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n"
                 .  "    <priority>{$u['priority']}</priority>\n";
            foreach ($u['images'] ?? [] as $img) {
                $xml .= "    <image:image>\n      <image:loc>" . $this->esc($img['loc']) . "</image:loc>\n"
                     .  "      <image:title>" . $this->esc($img['title']) . "</image:title>\n    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>';
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
