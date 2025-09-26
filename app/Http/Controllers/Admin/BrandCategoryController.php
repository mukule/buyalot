<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class BrandCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = BrandCategory::with('parent')->latest()->paginate(10);

        return Inertia::render('Admin/BrandCategories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = BrandCategory::where('active', true)->get();
        return Inertia::render('Admin/BrandCategories/Create', [
            'parents' => $parents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:brand_categories,id',
            'active' => 'boolean',
        ]);

        $slug = Str::slug($validated['name']);
        if (BrandCategory::where('slug', $slug)->exists()) {
            $slug .= '-' . uniqid();
        }

        $category = BrandCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        Log::info('Brand category created', ['id' => $category->id]);

        return redirect()->route('admin.brand-categories.index')
            ->with('success', 'Brand category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BrandCategory $brandCategory)
    {
        $parents = BrandCategory::where('id', '!=', $brandCategory->id)
            ->where('active', true)
            ->get();

        return Inertia::render('Admin/BrandCategories/Edit', [
            'category' => $brandCategory,
            'parents' => $parents,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BrandCategory $brandCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:brand_categories,id',
            'active' => 'boolean',
        ]);

        $slug = Str::slug($validated['name']);
        if ($slug !== $brandCategory->slug && BrandCategory::where('slug', $slug)->where('id', '!=', $brandCategory->id)->exists()) {
            $slug .= '-' . uniqid();
        }

        $brandCategory->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        return redirect()->route('admin.brand-categories.index')
            ->with('success', 'Brand category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BrandCategory $brandCategory)
    {
        $brandCategory->delete();

        return redirect()->route('admin.brand-categories.index')
            ->with('success', 'Brand category deleted successfully.');
    }
}
