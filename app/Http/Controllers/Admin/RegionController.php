<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    
    
    

public function index()
{
    Log::info('Admin/RegionController@index called', [
        'user_id' => auth()->id(),
        'route' => request()->fullUrl(),
    ]);

    $regions = Region::orderBy('created_at', 'desc')
        ->paginate(15);

    return Inertia::render('Admin/Regions/Index', [
        'regions' => $regions,
    ]);
}

    
    public function create()
    {
        return Inertia::render('Admin/Regions/Create');
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:regions,name'],
        ]);

        Region::create([
            'name' => $request->name,
            'code' => strtoupper(substr(Str::slug($request->name, ''), 0, 5)), 
            // e.g. "Nairobi" -> "NAIRO", "Coast Region" -> "COAST"
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region created successfully.');
    }

    /**
     * Show the form for editing a region.
     */
    public function edit(Region $region)
    {
        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region,
        ]);
    }

    /**
     * Update an existing region.
     */
    public function update(Request $request, Region $region)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:regions,name,' . $region->id],
        ]);

        $region->update([
            'name' => $request->name,
            'code' => strtoupper(substr(Str::slug($request->name, ''), 0, 5)),
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region updated successfully.');
    }

    /**
     * Show a single region.
     */
    public function show(Region $region)
    {
        return Inertia::render('Admin/Regions/Show', [
            'region' => $region,
        ]);
    }

    /**
     * Delete a region.
     */
    public function destroy(Region $region)
    {
        $region->delete();

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region deleted successfully.');
    }
}
