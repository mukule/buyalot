<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\VariantCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
   
    public function index()
    {
        $categories = Category::with('parent') 
            ->orderBy('created_at', 'desc')
            ->paginate(40);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

   
    
    public function create()
{
    
    $categories = Category::select('id', 'name')
        ->where('active', true)
        ->get();

    
    $variantCategories = \App\Models\VariantCategory::select('id', 'name', 'default')
        ->get();

    return Inertia::render('Admin/Categories/Create', [
        'categories' => $categories,
        'variantCategories' => $variantCategories,
    ]);
}


    /**
     * Store a newly created category.
     */
   

    public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        'variant_categories' => ['nullable', 'array'], // array of selected variant IDs
        'variant_categories.*' => ['integer', 'exists:variant_categories,id'], // each ID must exist
    ]);

    $category = Category::create([
        'name' => $request->name,
        'slug' => Str::slug($request->name),
        'parent_id' => $request->parent_id,
        'active' => $request->boolean('active', true),
    ]);

    // Attach selected variants
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

    
    $variantCategories = \App\Models\VariantCategory::select('id', 'name', 'default')->get();

    
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
        'slug' => \Str::slug($request->name),
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

   


public function show(Category $category)
{
    // Eager load children and parent
    $category->load(['children', 'parent']);

    return Inertia::render('Admin/Categories/Show', [
        'category' => $category,
        'children' => $category->children,
        'parent' => $category->parent, // null if top-level
    ]);
}

   
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
