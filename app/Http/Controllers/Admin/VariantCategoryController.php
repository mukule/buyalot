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
        ]);

        VariantCategory::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.variant-categories.index')->with('success', 'Variant type created successfully.');
    }

    public function edit(VariantCategory $variantCategory)
    {
        return inertia('Admin/VariantCategories/Edit', compact('variantCategory'));
    }

    public function update(Request $request, VariantCategory $variantCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:variant_categories,name,' . $variantCategory->id],
        ]);

        $variantCategory->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.variant-categories.index')->with('success', 'Variant type updated successfully.');
    }

    public function destroy(VariantCategory $variantCategory)
    {
        $variantCategory->delete();

        return redirect()->route('admin.variant-categories.index')->with('success', 'Variant type deleted.');
    }
}
