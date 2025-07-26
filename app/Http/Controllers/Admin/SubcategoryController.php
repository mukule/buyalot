<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubcategoryController extends Controller
{
    // Show create form for subcategory under a given category
    public function create(Category $category)
    {
        return Inertia::render('Admin/Categories/CreateSubCat', [
            'category' => $category,
        ]);
    }

    // Store new subcategory for a category
    public function store(Request $request, Category $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);

        $category->subcategories()->create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.categories.show', $category->getRouteKey())
            ->with('success', 'Subcategory created successfully.');
    }

    // Show edit form for subcategory
    public function edit(Category $category, Subcategory $subcategory)
    {
        // Optional: verify $subcategory belongs to $category
        abort_if($subcategory->category_id !== $category->id, 404);

        return Inertia::render('Admin/Categories/EditSubCat', [
            'category' => $category,
            'subcategory' => $subcategory,
        ]);
    }

    // Update subcategory
    public function update(Request $request, Category $category, Subcategory $subcategory)
    {
        abort_if($subcategory->category_id !== $category->id, 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);

        $subcategory->update([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.categories.show', $category->getRouteKey())
            ->with('success', 'Subcategory updated successfully.');
    }

    // Delete subcategory
    public function destroy(Category $category, Subcategory $subcategory)
    {
        abort_if($subcategory->category_id !== $category->id, 404);

        $subcategory->delete();

        return redirect()->route('admin.categories.show', $category->getRouteKey())
            ->with('success', 'Subcategory deleted successfully.');
    }

    // Optionally: Show a single subcategory if you want (not required)
    // public function show(Category $category, Subcategory $subcategory) { ... }
}
