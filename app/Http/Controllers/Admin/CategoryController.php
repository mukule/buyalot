<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VariantCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a paginated listing of categories (excluding soft-deleted).
     */
   

    public function index(Request $request)
{
    
    $query = Category::with('parent');

    
    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    
    if ($request->filled('active')) {
        $query->where('active', $request->boolean('active'));
    }

    
    if ($request->boolean('with_deleted')) {
        $query->withTrashed(); 
    }

    
    $categories = $query->orderBy('created_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

    return Inertia::render('Admin/Categories/Index', [
        'categories' => $categories,
        'filters' => $request->only(['name', 'active', 'with_deleted']),
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

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'variant_categories' => ['nullable', 'array'],
            'variant_categories.*' => ['integer', 'exists:variant_categories,id'],
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'active' => $request->boolean('active', true),
        ]);

        if ($request->filled('variant_categories')) {
            $category->variantCategories()->sync($request->variant_categories);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category)
    {
        $categories = Category::select('id', 'name')
            ->where('active', true)
            ->where('id', '!=', $category->id)
            ->get();

        $variantCategories = VariantCategory::select('id', 'name', 'default')->get();

        $category->load('variantCategories:id');

        if ($category->variantCategories->isNotEmpty()) {
            $prechecked = $category->variantCategories->pluck('id')->toArray();
        } else {
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

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'variant_categories' => ['nullable', 'array'],
            'variant_categories.*' => ['integer', 'exists:variant_categories,id'],
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'active' => $request->boolean('active', true),
        ]);

        if ($request->has('variant_categories')) {
            $category->variantCategories()->sync($request->variant_categories);
        } else {
            $category->variantCategories()->sync([]);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Display the specified category (with children & parent).
     */
    public function show(Category $category)
    {
        $category->load(['children', 'parent']);

        return Inertia::render('Admin/Categories/Show', [
            'category' => $category,
            'children' => $category->children,
            'parent' => $category->parent,
        ]);
    }

    /**
     * Soft delete the category and optionally cascade to children.
     */
    public function destroy(Category $category)
    {
        DB::transaction(function () use ($category) {
           
            foreach ($category->children as $child) {
                $this->destroy($child);
            }

            $category->delete();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Restore a soft-deleted category and optionally cascade to children.
     */
    public function restore(int $id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($category) {
            // Restore all children recursively
            foreach ($category->children()->withTrashed()->get() as $child) {
                $this->restore($child->id);
            }

            $category->restore();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category restored successfully.');
    }



   
public function forceDestroy(Category $category)
{
    DB::transaction(function () use ($category) {

        // If this category has products — block deletion
        if ($category->products()->exists()) {
            abort(400, 'Cannot delete this category because it has products attached.');
        }

        // Recursively check children
        foreach ($category->children()->withTrashed()->get() as $child) {

            if ($child->products()->exists()) {
                abort(400, 'Cannot delete child category "' . $child->name . '" because it has products attached.');
            }

            // Detach pivot relations before permanent delete
            $child->variantCategories()->detach();
            $child->forceDelete();
        }

        // Detach pivot relations for parent category
        $category->variantCategories()->detach();

        // Permanently delete this category
        $category->forceDelete();
    });

    return redirect()
        ->route('admin.categories.index')
        ->with('success', 'Category permanently deleted.');
}

}
