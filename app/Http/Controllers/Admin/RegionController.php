<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RegionController extends Controller
{


    public function index()
{
    $regions = Region::with(['parent', 'zone'])
        ->orderBy('created_at', 'desc')
        ->paginate(15)
        ->withQueryString();

    $regions->getCollection()->transform(function ($region) {
        return [
            'id' => $region->id,
            'hashid' => $region->hashid ?? null,
            'name' => $region->name,
            'parent_id' => $region->parent_id,
            'parent_name' => $region->parent?->name ?? null,

            'zone_name' => $region->zone?->name ?? null,
            'tier' => $region->zone?->tier ?? null,
            'active' => $region->active,
            'created_at' => $region->created_at,
        ];
    });

    return Inertia::render('Admin/Regions/Index', [
        'regions' => $regions,
        'title' => 'Regions',
    ]);
}


    public function create()
    {
        $parents = Region::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $zones = \App\Models\Zone::orderBy('name')->get(['id', 'name', 'tier']); // load zones

        return Inertia::render('Admin/Regions/Create', [
            'title' => 'Create Region',
            'parents' => $parents,
            'zones' => $zones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:regions,name'],
            'parent_id' => ['nullable', 'exists:regions,id'],
            'level' => ['nullable', 'string', 'max:50'],
            'zone_id' => ['nullable', 'exists:zones,id'], // new
        ]);

        Region::create([
            'name' => $validated['name'],
            'code' => strtoupper(substr(Str::slug($validated['name'], ''), 0, 5)),
            'level' => $validated['level'] ?? 'region',
            'parent_id' => $validated['parent_id'] ?? null,
            'zone_id' => $validated['zone_id'] ?? null, // assign zone
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region created successfully.');
    }

    public function edit(Region $region)
    {
        $parents = Region::whereNull('parent_id')->where('id', '!=', $region->id)->get(['id', 'name']);
        $zones = \App\Models\Zone::orderBy('name')->get(['id', 'name', 'tier']); // load zones

        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region,
            'parents' => $parents,
            'zones' => $zones,
            'title' => 'Edit Region',
        ]);
    }

    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:regions,name,' . $region->id],
            'parent_id' => ['nullable', 'exists:regions,id'],
            'level' => ['nullable', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'exists:zones,id'], // new
        ]);

        $region->update([
            'name' => $validated['name'],
            'code' => strtoupper(substr(Str::slug($validated['name'], ''), 0, 5)),
            'level' => $validated['level'] ?? $region->level,
            'parent_id' => $validated['parent_id'] ?? null,
            'active' => $request->boolean('active'),
            'zone_id' => $validated['zone_id'] ?? $region->zone_id, // update zone
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region updated successfully.');
    }

    public function show(Region $region)
    {
        $regionIds = $region->children()->pluck('id')->toArray();
        $regionIds[] = $region->id;

        $pickupPoints = PickupPoint::whereIn('region_id', $regionIds)
            ->with('region')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Regions/Show', [
            'region' => $region->load('zone'), // include zone
            'pickupPoints' => $pickupPoints,
            'title' => $region->name . ' Pickup Points',
        ]);
    }

    public function destroy(Region $region)
    {
        $region->delete();

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region deleted successfully.');
    }
}
