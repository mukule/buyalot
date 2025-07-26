<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;


class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses.
     */
  
  public function index()
{
    $warehouses = Warehouse::orderBy('created_at', 'desc')
        ->paginate(15);

    return Inertia::render('Admin/Warehouses/Index', [
        'warehouses' => $warehouses, 
    ]);
}



    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        return Inertia::render('Admin/Warehouses/Create');
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name',
            'location' => 'nullable|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        // Generate a unique slug based on name
        $slug = Str::slug($validated['name']);
        $count = Warehouse::where('slug', 'like', "$slug%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        Warehouse::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'location' => $validated['location'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    /**
     * Show the form for editing the specified warehouse.
     */
   
public function edit(Warehouse $warehouse)
{
    Log::info('Edit method called for warehouse:', ['id' => $warehouse->id, 'name' => $warehouse->name]);

    return Inertia::render('Admin/Warehouses/Edit', [
        'warehouse' => $warehouse,
    ]);
}


    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $warehouse->id,
            'location' => 'nullable|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($warehouse->name !== $validated['name']) {
            $slug = Str::slug($validated['name']);
            $count = Warehouse::where('slug', 'like', "$slug%")
                ->where('id', '!=', $warehouse->id)
                ->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $warehouse->slug = $slug;
        }

        $warehouse->name = $validated['name'];
        $warehouse->location = $validated['location'] ?? null;
        $warehouse->active = $validated['active'] ?? $warehouse->active;

        $warehouse->save();

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
