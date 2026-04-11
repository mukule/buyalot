<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Category;
use App\Models\Products\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    private const CACHE_TAGS = ['frontend_promotions', 'homepage'];

    /**
     * Display promotions list
     */
  
 
    public function index(Request $request)
{
    $promotions = Promotion::query()
        ->with(['category:id,name,slug', 'product:id,name,slug'])
        ->when($request->filled('position'), fn($q) => $q->where('position', $request->position))
        ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
        ->orderBy('priority')
        ->paginate(20)
        ->withQueryString()
        ->through(fn($promotion) => [
            'id'          => $promotion->id,
            'title'       => $promotion->title,
            'image_url'   => $promotion->image_url,
            'position'    => $promotion->position,
            'is_active'   => $promotion->is_active,
            'link_type'   => $promotion->link_type,
            'link_id'     => $promotion->link_id,
            'linked_item' => match($promotion->link_type) {
                'category' => $promotion->category,
                'product'  => $promotion->product,
                default    => null,
            },
        ]);

    return Inertia::render('Admin/Promotions/Index', [
        'promotions' => $promotions,
        'filters'    => $request->only(['position', 'is_active']),
    ]);
}

public function save(Request $request)
{
    $isUpdate = $request->filled('id');

    $validated = $request->validate([
        'id'         => ['nullable', 'integer', 'exists:promotions,id'],
        'title'      => ['nullable', 'string', 'max:255'],
        'image'      => [$isUpdate ? 'nullable' : 'required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        'position'   => ['required', 'string', 'max:255'],
        'link_type'  => ['required', Rule::in(['category', 'product'])],
        'link_id'    => ['nullable', 'integer'],
        'start_date' => ['nullable', 'date'],
        'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        'priority'   => ['nullable', 'integer', 'min:0'],
        'is_active'  => ['boolean'],
    ]);

    $data = collect($validated)
        ->except('id')
        ->when($isUpdate, fn($c) => $c->filter(fn($v) => !is_null($v))) // ✅ keeps false and 0
        ->all();

    if ($isUpdate) {
        $promotion = Promotion::findOrFail($request->id); // ✅ single fetch

        if ($request->hasFile('image')) {
            if ($promotion->image) {
                Storage::disk('public')->delete($promotion->image);
            }
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }

        $promotion->update($data);
        $message = 'Promotion updated successfully.';
    } else {
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }
        $promotion = Promotion::create($data);
        $message = 'Promotion created successfully.';
    }

    $this->flushPromotionCache();

    return redirect()->back()->with('success', $message);
}

private function flushPromotionCache(): void
{
    try {
        Cache::tags(self::CACHE_TAGS)->flush();
    } catch (\Exception $e) {
        Cache::forget('promotions:homepage:category');
        Cache::forget('promotions:homepage');
        Cache::forget('promotions:all');
    }
}

public function destroy(Promotion $promotion)
{
    // Delete image from storage if exists
    if ($promotion->image) {
        Storage::disk('public')->delete($promotion->image);
    }

    $promotion->delete();
    Cache::tags(self::CACHE_TAGS)->flush();

    return response()->json(['success' => true]);
}

    /**
     * Search categories for autocomplete
     */
    public function searchCategories(Request $request)
    {
        $term = trim($request->input('query', ''));
        if (strlen($term) < 2) return response()->json([]);

        return response()->json(
            Category::active()
                ->where('name', 'like', "%{$term}%")
                ->select('id', 'name')
                ->limit(20)
                ->get()
        );
    }

    /**
     * Search products for autocomplete
     */
    public function searchProducts(Request $request)
    {
        $term = trim($request->input('query', ''));
        if (strlen($term) < 2) return response()->json([]);

        return response()->json(
            Product::active()
                ->where('name', 'like', "%{$term}%")
                ->select('id', 'name')
                ->limit(20)
                ->get()
        );
    }
}