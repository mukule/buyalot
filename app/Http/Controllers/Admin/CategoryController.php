<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VariantCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    
    
    public function index(Request $request)
{
    // Select only top-level categories
    $query = Category::select('id', 'name', 'slug', 'parent_id', 'active')
        ->with(['parent:id,name']) // load only parent fields
        ->whereNull('parent_id');  // only categories with no parent

    // Filter by name if provided
    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    // Filter by active status if provided
    if ($request->filled('active')) {
        $query->where('active', $request->boolean('active'));
    }

    // Paginate results
    $categories = $query->orderBy('created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

    // Render Inertia page
    return Inertia::render('Admin/Categories/Index', [
        'categories' => $categories,
        'filters' => $request->only(['name', 'active']),
    ]);
}



    public function create()
    {
        $categories = Category::select('id', 'name')
            ->where('active', true)
            ->get();

        $variantCategories = VariantCategory::select('id', 'name', 'default')->get();

        return Inertia::render('Admin/Categories/Create', [
            'categories' => $categories,
            'variantCategories' => $variantCategories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(fn($q) => $q->where('parent_id', $request->parent_id)),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'variant_categories' => ['nullable', 'array'],
            'variant_categories.*' => ['integer', 'exists:variant_categories,id'],
        ]);

        $category = Category::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'active' => $request->boolean('active', true),
            'description' => $request->description ?? null,
        ]);

        if ($request->filled('variant_categories')) {
            $category->variantCategories()->sync($request->variant_categories);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $categories = Category::select('id', 'name')
            ->where('active', true)
            ->where('id', '!=', $category->id)
            ->get();

        $variantCategories = VariantCategory::select('id', 'name', 'default')->get();

        $category->load('variantCategories:id');

        $prechecked = $category->variantCategories->pluck('id')->toArray();
        if (empty($prechecked)) {
            $default = $variantCategories->firstWhere('default', true);
            $prechecked = $default ? [$default->id] : [];
        }

        $category->variant_categories = $prechecked;

        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
            'categories' => $categories,
            'variantCategories' => $variantCategories,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(fn($q) => $q->where('parent_id', $request->parent_id)),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'variant_categories' => ['nullable', 'array'],
            'variant_categories.*' => ['integer', 'exists:variant_categories,id'],
        ]);

        $category->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'active' => $request->boolean('active', true),
            'description' => $request->description ?? null,
        ]);

        $category->variantCategories()->sync($request->variant_categories ?? []);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

   
    public function show(Category $category)
{
    // Load children and parent
    $category->load(['children', 'parent']);

    // Build breadcrumb from hierarchy
    $breadcrumb = $category->getHierarchy(); // returns array of ['id', 'name', 'slug']
    // Convert to format suitable for frontend: { name, hashid }
    $breadcrumbFormatted = array_map(function ($cat) {
        return [
            'name' => $cat['name'],
            'hashid' => \App\Models\Category::find($cat['id'])->hashid, // get hashid for each ancestor
        ];
    }, $breadcrumb);

    return Inertia::render('Admin/Categories/Show', [
        'category' => $category->toArray() + ['breadcrumb' => $breadcrumbFormatted],
        'children' => $category->children,
        'parent' => $category->parent,
    ]);
}



    /**
     * Permanently delete category and all its children
     */
    public function destroy(Category $category)
    {
        DB::transaction(function () use ($category) {
            // Recursively delete children
            foreach ($category->children as $child) {
                $this->destroy($child);
            }

            // Detach pivot relations
            $category->variantCategories()->detach();

            // Permanently delete
            $category->forceDelete();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
