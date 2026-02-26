<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Models\Zone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ShippingRateController extends Controller
{
  
    public function index()
    {
        $rates = ShippingRate::with('zone')
            ->orderBy('zone_id', 'asc')
            ->orderBy('package_size', 'asc')
            ->paginate(40);

        return Inertia::render('Admin/ShippingRates/Index', [
            'shippingRates' => $rates,
        ]);
    }

   
    public function create()
    {
        $packageSizes = ['small', 'medium', 'large'];
        $zones = Zone::orderBy('name')->get(['id', 'name', 'tier']);

        return Inertia::render('Admin/ShippingRates/Create', [
            'packageSizes' => $packageSizes,
            'zones' => $zones,
        ]);
    }

   
    public function store(Request $request)
    {
        $request->validate([
            'zone_id'               => ['nullable', 'exists:zones,id'],
            'package_size'          => ['required', 'in:small,medium,large'],
            'base_price'            => ['required', 'numeric', 'min:0'],
            'door_fallback_price'   => ['required', 'numeric', 'min:0'],
            'door_fallback_min_km'  => ['required', 'numeric', 'min:0'],
            'door_extra_km_cost'    => ['required', 'numeric', 'min:0'],
            'cod_min_amount'        => ['nullable', 'numeric', 'min:0'],
            'free_shipping_min_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zoneAndPackage = ShippingRate::where('package_size', $request->package_size);
        if ($request->zone_id) {
            $zoneAndPackage->where('zone_id', $request->zone_id);
        } else {
            $zoneAndPackage->whereNull('zone_id');
        }
        if ($zoneAndPackage->exists()) {
            return back()->withErrors(['package_size' => 'A rate already exists for this zone and package size.']);
        }

        ShippingRate::create([
            'zone_id'                 => $request->zone_id,
            'package_size'            => $request->package_size,
            'base_price'              => $request->base_price,
            'door_price'              => $request->door_fallback_price,
            'door_fallback_price'     => $request->door_fallback_price,
            'door_fallback_min_km'    => $request->door_fallback_min_km,
            'door_extra_km_cost'      => $request->door_extra_km_cost,
            'cod_min_amount'          => $request->cod_min_amount ?: null,
            'free_shipping_min_amount' => $request->free_shipping_min_amount ?: null,
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
        $zones = Zone::orderBy('name')->get(['id', 'name', 'tier']);
        $shippingRate->load('zone');

        return Inertia::render('Admin/ShippingRates/Edit', [
            'rate'         => $shippingRate,
            'packageSizes' => $packageSizes,
            'zones'        => $zones,
        ]);
    }

   
    public function update(Request $request, ShippingRate $shippingRate)
    {
        $request->validate([
            'zone_id'                 => ['nullable', 'exists:zones,id'],
            'package_size'            => ['required', 'in:small,medium,large'],
            'base_price'              => ['required', 'numeric', 'min:0'],
            'door_fallback_price'     => ['required', 'numeric', 'min:0'],
            'door_fallback_min_km'    => ['required', 'numeric', 'min:0'],
            'door_extra_km_cost'      => ['required', 'numeric', 'min:0'],
            'cod_min_amount'          => ['nullable', 'numeric', 'min:0'],
            'free_shipping_min_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zoneAndPackage = ShippingRate::where('package_size', $request->package_size)
            ->where('id', '!=', $shippingRate->id);
        if ($request->zone_id) {
            $zoneAndPackage->where('zone_id', $request->zone_id);
        } else {
            $zoneAndPackage->whereNull('zone_id');
        }
        if ($zoneAndPackage->exists()) {
            return back()->withErrors(['package_size' => 'A rate already exists for this zone and package size.']);
        }

        $shippingRate->update([
            'zone_id'                 => $request->zone_id ?: null,
            'package_size'            => $request->package_size,
            'base_price'              => $request->base_price,
            'door_price'              => $request->door_fallback_price,
            'door_fallback_price'     => $request->door_fallback_price,
            'door_fallback_min_km'    => $request->door_fallback_min_km,
            'door_extra_km_cost'      => $request->door_extra_km_cost,
            'cod_min_amount'          => $request->cod_min_amount ?: null,
            'free_shipping_min_amount' => $request->free_shipping_min_amount ?: null,
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
