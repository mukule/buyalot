<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitTypeController extends Controller
{
    public function index()
    {
        $unitTypes = UnitType::orderBy('name')->paginate(15);

        return Inertia::render('Admin/Units/Index', [
            'unitTypes' => $unitTypes,
            'filters' => request()->only('search', 'page'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Units/Create');
    }

   
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|unique:unit_types,name',
        'slug' => 'nullable|string|unique:unit_types,slug',
    ]);

    UnitType::create([
        'name' => $request->name,
        'slug' => $request->slug,
        'active' => true,  // Set active true by default here
    ]);

    return redirect()->route('admin.unit-types.index')
        ->with('success', 'Unit type created successfully.');
}



public function show(UnitType $unitType)
{
    $unitType->load('units'); 

    return Inertia::render('Admin/Units/Show', [
        'unitType' => $unitType,
        'units' => $unitType->units,
    ]);
}


    public function edit(UnitType $unitType)
    {
        return Inertia::render('Admin/Units/Edit', [
            'unitType' => $unitType,
        ]);
    }

    public function update(Request $request, UnitType $unitType)
    {
        $request->validate([
            'name' => 'required|string|unique:unit_types,name,' . $unitType->id,
            'slug' => 'nullable|string|unique:unit_types,slug,' . $unitType->id,
        ]);

        $unitType->update($request->only('name', 'slug'));

        return redirect()->route('admin.unit-types.index')
            ->with('success', 'Unit type updated successfully.');
    }

    public function destroy(UnitType $unitType)
    {
        $unitType->delete();

        return redirect()->route('admin.unit-types.index')
            ->with('success', 'Unit type deleted successfully.');
    }
}
