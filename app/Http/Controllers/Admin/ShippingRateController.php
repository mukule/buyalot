<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ShippingRateController extends Controller
{
  
    public function index()
    {
        $rates = ShippingRate::orderBy('package_size', 'asc')->paginate(40);

        return Inertia::render('Admin/ShippingRates/Index', [
            'shippingRates' => $rates,
        ]);
    }

   
    public function create()
    {
        $packageSizes = ['small', 'medium', 'large'];

        return Inertia::render('Admin/ShippingRates/Create', [
            'packageSizes' => $packageSizes,
        ]);
    }

   
    public function store(Request $request)
    {
        $request->validate([
            'package_size'        => ['required', 'in:small,medium,large'],
            'base_price'          => ['required', 'numeric', 'min:0'],
            'door_fallback_price' => ['required', 'numeric', 'min:0'],
            'door_fallback_min_km' => ['required', 'numeric', 'min:0'],
            'door_extra_km_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        if (ShippingRate::where('package_size', $request->package_size)->exists()) {
            return back()->withErrors(['package_size' => 'A rate already exists for this package size.']);
        }

        ShippingRate::create([
            'package_size'        => $request->package_size,
            'base_price'          => $request->base_price,
            'door_price'          => $request->door_fallback_price,
            'door_fallback_price' => $request->door_fallback_price,
            'door_fallback_min_km' => $request->door_fallback_min_km,
            'door_extra_km_cost'  => $request->door_extra_km_cost,
        ]);

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate created successfully.');
    }

 
    public function edit(ShippingRate $shippingRate)
    {
        Log::info('Admin/ShippingRateController@edit called', [
            'user_id' => auth()->id(),
            'route'   => request()->fullUrl(),
        ]);

        $packageSizes = ['small', 'medium', 'large'];

        return Inertia::render('Admin/ShippingRates/Edit', [
            'rate'         => $shippingRate,
            'packageSizes' => $packageSizes,
        ]);
    }

   
    public function update(Request $request, ShippingRate $shippingRate)
    {
        $request->validate([
            'package_size'        => ['required', 'in:small,medium,large'],
            'base_price'          => ['required', 'numeric', 'min:0'],
            'door_fallback_price' => ['required', 'numeric', 'min:0'],
            'door_fallback_min_km' => ['required', 'numeric', 'min:0'],
            'door_extra_km_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        $exists = ShippingRate::where('package_size', $request->package_size)
            ->where('id', '!=', $shippingRate->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['package_size' => 'A rate already exists for this package size.']);
        }

        $shippingRate->update([
            'package_size'        => $request->package_size,
            'base_price'          => $request->base_price,
            'door_price'          => $request->door_fallback_price,
            'door_fallback_price' => $request->door_fallback_price,
            'door_fallback_min_km' => $request->door_fallback_min_km,
            'door_extra_km_cost'  => $request->door_extra_km_cost,
        ]);

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate updated successfully.');
    }

   
    public function destroy(ShippingRate $shippingRate)
    {
        $shippingRate->delete();

        return redirect()->route('admin.shipping-rates.index')
            ->with('success', 'Shipping rate deleted successfully.');
    }
}
