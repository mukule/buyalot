<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ZoneController extends Controller
{
    /**
     * Display a listing of zones.
     */
   
    public function index()
{
    $zones = Zone::orderBy('tier', 'asc')
        ->orderBy('created_at', 'desc')
        ->paginate(40);

    return Inertia::render('Admin/Zones/Index', [
        'zones' => $zones,
    ]);
}


    /**
     * Show the form for creating a new zone.
     */
    public function create()
    {
        return Inertia::render('Admin/Zones/Create');
    }

    /**
     * Store a newly created zone.
     */
   
    public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:zones,name'],
        'tier' => ['required', 'integer', 'min:1', 'unique:zones,tier'], // enforce unique tier
        'is_default_origin' => ['boolean'],
    ]);

    // If this zone is marked as default origin, unset others
    if ($request->boolean('is_default_origin')) {
        Zone::where('is_default_origin', true)->update(['is_default_origin' => false]);
    }

    Zone::create([
        'name' => $request->name,
        'tier' => $request->tier,
        'is_default_origin' => $request->boolean('is_default_origin', false),
    ]);

    return redirect()->route('admin.zones.index')
        ->with('success', 'Zone created successfully.');
}

    /**
     * Show the form for editing the specified zone.
     */
    public function edit(Zone $zone)
    {
        return Inertia::render('Admin/Zones/Edit', [
            'zone' => $zone,
        ]);
    }

    /**
     * Update the specified zone.
     */
    public function update(Request $request, Zone $zone)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:zones,name,' . $zone->id],
            'tier' => ['nullable', 'integer', 'min:1'],
            'is_default_origin' => ['boolean'],
        ]);

        // If this zone is marked as default origin, unset others
        if ($request->boolean('is_default_origin')) {
            Zone::where('is_default_origin', true)->where('id', '!=', $zone->id)
                ->update(['is_default_origin' => false]);
        }

        $zone->update([
            'name' => $request->name,
            'tier' => $request->tier ?? 1,
            'is_default_origin' => $request->boolean('is_default_origin', false),
        ]);

        return redirect()->route('admin.zones.index')
            ->with('success', 'Zone updated successfully.');
    }

    /**
     * Display the specified zone.
     */
    public function show(Zone $zone)
    {
        return Inertia::render('Admin/Zones/Show', [
            'zone' => $zone,
        ]);
    }

    /**
     * Remove the specified zone from storage.
     */
    public function destroy(Zone $zone)
    {
        $zone->delete();

        return redirect()->route('admin.zones.index')
            ->with('success', 'Zone deleted successfully.');
    }
}
