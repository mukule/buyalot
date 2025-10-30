<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariantCategory;
use Illuminate\Http\Request;

class VariantCategoryController extends Controller
{
   
    public function index()
{
    // Paginate, e.g. 15 per page
    $variantCategories = VariantCategory::orderBy('created_at', 'desc')->paginate(15);

    return inertia('Admin/VariantCategories/Index', [
        'variantCategories' => $variantCategories,
    ]);
}


    public function create()
    {
        return inertia('Admin/VariantCategories/Create');
    }

   
   public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'unique:variant_categories,name'],
        'default' => ['nullable', 'boolean'],
        'active' => ['nullable', 'boolean'],
    ]);

    // If this is set as default, unset default for others
    if ($request->boolean('default')) {
        VariantCategory::where('default', true)->update(['default' => false]);
    }

    VariantCategory::create([
        'name' => $request->name,
        'default' => $request->boolean('default'),
        'active' => $request->boolean('active', true),
    ]);

    return redirect()
        ->route('admin.variant-categories.index')
        ->with('success', 'Variant category created successfully.');
}




    public function edit(VariantCategory $variantCategory)
    {
        return inertia('Admin/VariantCategories/Edit', compact('variantCategory'));
    }

   
    public function update(Request $request, VariantCategory $variantCategory)
{
    $request->validate([
        'name' => ['required', 'string', 'unique:variant_categories,name,' . $variantCategory->id],
        'default' => ['nullable', 'boolean'],
        'active' => ['nullable', 'boolean'],
    ]);

    // Ensure only one default
    if ($request->boolean('default')) {
        VariantCategory::where('default', true)
            ->where('id', '!=', $variantCategory->id)
            ->update(['default' => false]);
    }

    $variantCategory->update([
        'name' => $request->name,
        'default' => $request->boolean('default'),
        'active' => $request->boolean('active', true),
    ]);

    return redirect()
        ->route('admin.variant-categories.index')
        ->with('success', 'Variant category updated successfully.');
}


    public function destroy(VariantCategory $variantCategory)
    {
        $variantCategory->delete();

        return redirect()->route('admin.variant-categories.index')->with('success', 'Variant type deleted.');
    }
}
