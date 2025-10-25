<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ShippingRateController extends Controller
{
    /**
     * Display a listing of the shipping rates.
     */
    public function index()
    {
        $rates = ShippingRate::orderBy('package_size', 'asc')->paginate(40);

        return Inertia::render('Admin/ShippingRates/Index', [
            'shippingRates' => $rates,
        ]);
    }

    /**
     * Show the form for creating a new shipping rate.
     */
    public function create()
    {
        $packageSizes = ['small', 'medium', 'large'];

        return Inertia::render('Admin/ShippingRates/Create', [
            'packageSizes' => $packageSizes,
        ]);
    }

    /**
     * Store a newly created shipping rate in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_size' => ['required', 'in:small,medium,large'],
            'base_price' => ['required', 'numeric', 'min:0'],
        ]);

        // Prevent duplicate package_size
        if (ShippingRate::where('package_size', $request->package_size)->exists()) {
            return back()->withErrors(['package_size' => 'A rate already exists for this package size.']);
        }

        ShippingRate::create([
            'package_size' => $request->package_size,
            'base_price' => $request->base_price,
        ]);

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate created successfully.');
    }

    /**
     * Show the form for editing the specified shipping rate.
     */
    public function edit(ShippingRate $shippingRate)
    {

          Log::info('Admin/ShippingController@Edit called', [
        'user_id' => auth()->id(),
        'route' => request()->fullUrl(),
    ]);


        $packageSizes = ['small', 'medium', 'large'];

        return Inertia::render('Admin/ShippingRates/Edit', [
            'rate' => $shippingRate,
            'packageSizes' => $packageSizes,
        ]);
    }

    /**
     * Update the specified shipping rate in storage.
     */
    public function update(Request $request, ShippingRate $shippingRate)
    {
        $request->validate([
            'package_size' => ['required', 'in:small,medium,large'],
            'base_price' => ['required', 'numeric', 'min:0'],
        ]);

        // Ensure uniqueness of package_size (excluding current record)
        $exists = ShippingRate::where('package_size', $request->package_size)
            ->where('id', '!=', $shippingRate->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['package_size' => 'A rate already exists for this package size.']);
        }

        $shippingRate->update([
            'package_size' => $request->package_size,
            'base_price' => $request->base_price,
        ]);

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate updated successfully.');
    }

    /**
     * Remove the specified shipping rate from storage.
     */
    public function destroy(ShippingRate $shippingRate)
    {
        $shippingRate->delete();

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate deleted successfully.');
    }
}
