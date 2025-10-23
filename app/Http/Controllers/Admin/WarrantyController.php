<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

use Vinkla\Hashids\Facades\Hashids;

class WarrantyController extends Controller
{
    /**
     * Show form for creating a warranty for a product.
     */
    public function create(Product $product)
    {
        return Inertia::render('Admin/Products/CreateWarranty', [
            'product' => [
                'id' => $product->id,
                'hashid' => $product->hashid,
                'slug' => $product->slug,
                'name' => $product->name,
            ],
        ]);
    }

    
    public function store(Request $request, Product $product)
{
    // Ensure the logged-in user is the owner of the product
    if ($product->owner_id !== auth()->id()) {
        return redirect()->back()->with('error', 'You are not allowed to add a warranty to this product.');
    }

    // Validate input
    $data = $request->validate([
        'duration'    => ['required', 'integer', 'min:1'],
        'description' => ['nullable', 'string'],
        'active'      => ['boolean'],
    ]);

    // Prevent duplicate warranty duration per product
    if ($product->warranties()->where('duration', $data['duration'])->exists()) {
        return back()->with('error', 'The product already has a warranty with this duration.');
    }

    // Handle activation toggle
    if ($request->boolean('active', true)) {
        $product->warranties()->update(['active' => false]);
    }

    $product->warranties()->create([
        'name'        => $product->name . ' Warranty',
        'duration'    => $data['duration'],
        'description' => $data['description'],
        'active'      => $request->boolean('active', true),
    ]);

    return redirect()->route('admin.products.show', $product)
                     ->with('success', 'Warranty created successfully.');
}


   
    public function edit(Product $product, Warranty $warranty)
    {
        abort_if($warranty->product_id !== $product->id, 404);

        return Inertia::render('Admin/Products/EditWarranty', [
            'product'  => $product,
            'warranty' => $warranty,
        ]);
    }

  
public function update(Request $request, Product $product, Warranty $warranty)
{
    // Ensure the warranty belongs to the product
    abort_if($warranty->product_id !== $product->id, 404);

    // Ensure the logged-in user is the owner of the product
    if ($product->owner_id !== auth()->id()) {
        return redirect()->back()->with('error', 'You are not allowed to update this warranty.');
    }

    // Validate input
    $data = $request->validate([
        'duration'    => ['required', 'integer', 'min:1'],
        'description' => ['nullable', 'string'],
        'active'      => ['boolean'],
    ]);

    // Prevent duplicate duration
    $exists = $product->warranties()
        ->where('duration', $data['duration'])
        ->where('id', '!=', $warranty->id)
        ->exists();

    if ($exists) {
        return back()->with('error', 'Another warranty with this duration already exists.');
    }

    // Handle activation toggle
    if ($request->boolean('active')) {
        $product->warranties()
            ->where('id', '!=', $warranty->id)
            ->update(['active' => false]);
    }

    // Update the warranty
    $warranty->update([
        'duration'    => $data['duration'],
        'description' => $data['description'] ?? null,
        'active'      => $request->boolean('active'),
    ]);

    // Redirect to products index
    return redirect()->route('admin.products.index')
                     ->with('success', 'Warranty updated successfully.');
}



public function toggleActive(string $warranty)
{
    $decoded = Hashids::decode($warranty);

    if (count($decoded) !== 1) {
        return redirect()->back()->with('error', 'Invalid warranty ID.');
    }

    $warranty = Warranty::findOrFail($decoded[0]);

    
    if ($warranty->product->owner_id !== auth()->id()) {
        return redirect()->back()->with('error', 'You are not allowed to perform this action.');
    }

    $newStatus = !$warranty->active;

    if ($newStatus) {
        $warranty->product->warranties()
            ->where('id', '!=', $warranty->id)
            ->update(['active' => false]);
    }

    $warranty->update(['active' => $newStatus]);

    $message = $newStatus
        ? 'Warranty activated successfully.'
        : 'Warranty deactivated successfully.';

    return redirect()->back()->with('success', $message);
}



}
