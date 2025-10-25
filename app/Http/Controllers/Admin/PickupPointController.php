<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PickupPointController extends Controller
{
   

    
    public function create(Region $region)
    {
        return Inertia::render('Admin/Regions/CreatePickupPoint', [
            'region' => $region,
        ]);
    }

    /**
     * Store new pickup point for a region.
     */
  
    public function store(Request $request, Region $region)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'address' => ['nullable', 'string', 'max:255'],
        'contact_phone' => ['nullable', 'string', 'max:255'],
        'contact_email' => ['nullable', 'email', 'max:255'],
        'active' => ['boolean'], 
    ]);

    $region->pickupPoints()->create([
        'name' => $request->name,
        'uuid' => (string) Str::uuid(),
        'code' => strtoupper(substr(Str::slug($request->name, ''), 0, 5)),
        'description' => $request->description,
        'address' => $request->address,
        'contact_phone' => $request->contact_phone,
        'contact_email' => $request->contact_email,
        'active' => $request->boolean('active', true), 
    ]);

    return redirect()->route('admin.regions.show', $region->getRouteKey())
        ->with('success', 'Pickup point created successfully.');
}


    /**
     * Show edit form for a pickup point under a region.
     */
    public function edit(Region $region, PickupPoint $pickupPoint)
    {
        // abort_if($pickupPoint->region_id !== $region->id, 404);

         Log::info('Admin/Pickup point@edit called', [
        'user_id' => auth()->id(),
        'route' => request()->fullUrl(),
    ]);

        return Inertia::render('Admin/Regions/EditPickupPoint', [
            'region' => $region,
            'pickupPoint' => $pickupPoint,
        ]);
    }

    /**
     * Update pickup point under a region.
     */
    public function update(Request $request, Region $region, PickupPoint $pickupPoint)
    {
        abort_if($pickupPoint->region_id !== $region->id, 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $pickupPoint->update([
            'name' => $request->name,
            'code' => strtoupper(substr(Str::slug($request->name, ''), 0, 5)),
            'description' => $request->description,
            'address' => $request->address,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.regions.show', $region->getRouteKey())
            ->with('success', 'Pickup point updated successfully.');
    }

    /**
     * Delete a pickup point under a region.
     */
    public function destroy(Region $region, PickupPoint $pickupPoint)
    {
        abort_if($pickupPoint->region_id !== $region->id, 404);

        $pickupPoint->delete();

        return redirect()->route('admin.regions.show', $region->getRouteKey())
            ->with('success', 'Pickup point deleted successfully.');
    }
}
