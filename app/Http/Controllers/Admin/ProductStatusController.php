<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class ProductStatusController extends Controller
{
    /**
     * Display a listing of product statuses.
     */
   
    public function index()
{
    $statuses = ProductStatus::orderBy('created_at', 'desc')
        ->paginate(15);

    return Inertia::render('Admin/ProductStatuses/Index', [
        'productStatuses' => $statuses->through(fn ($status) => [
            'id' => $status->id,
            'hashid' => Hashids::encode($status->id),
            'name' => $status->name,
            'label' => $status->label,
            'color_class' => $status->color_class,
        ]),
    ]);
}

    /**
     * Show the form for creating a new product status.
     */
    public function create()
    {
        return Inertia::render('Admin/ProductStatuses/Create');
    }

    /**
     * Store a newly created product status in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:product_statuses,name',
            'label'       => 'required|string|max:255',
            'color_class' => 'nullable|string|max:255',
        ]);

        ProductStatus::create([
            'name'        => $validated['name'],
            'label'       => $validated['label'],
            'color_class' => $validated['color_class'] ?? null,
        ]);

        return redirect()->route('admin.product-statuses.index')
            ->with('success', 'Product status created successfully.');
    }

    /**
     * Show the form for editing the specified product status.
     */
    public function edit(ProductStatus $productStatus)
    {
        Log::info('Edit method called for product status:', [
            'id' => $productStatus->id,
            'name' => $productStatus->name,
        ]);

        return Inertia::render('Admin/ProductStatuses/Edit', [
            'status' => $productStatus,
        ]);
    }

    /**
     * Update the specified product status in storage.
     */
    public function update(Request $request, ProductStatus $productStatus)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:product_statuses,name,' . $productStatus->id,
            'label'       => 'required|string|max:255',
            'color_class' => 'nullable|string|max:255',
        ]);

        $productStatus->name        = $validated['name'];
        $productStatus->label       = $validated['label'];
        $productStatus->color_class = $validated['color_class'] ?? $productStatus->color_class;

        $productStatus->save();

        return redirect()->route('admin.product-statuses.index')
            ->with('success', 'Product status updated successfully.');
    }

    /**
     * Remove the specified product status from storage.
     */
    public function destroy(ProductStatus $productStatus)
    {
        $productStatus->delete();

        return redirect()->route('admin.product-statuses.index')
            ->with('success', 'Product status deleted successfully.');
    }
}
