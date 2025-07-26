<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UnitController extends Controller
{
    // Show create form for a unit under a given unit type
    public function create(UnitType $unitType)
    {
        return Inertia::render('Admin/Units/CreateUnit', [
            'unitType' => $unitType,
        ]);
    }

    // Store new unit for a unit type
    public function store(Request $request, UnitType $unitType)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:50'],
            'active' => ['boolean'],
        ]);

        $unitType->units()->create([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.unit-types.show', $unitType->getRouteKey())
            ->with('success', 'Unit created successfully.');
    }

    // Show edit form for unit
    public function edit(UnitType $unitType, Unit $unit)
    {
        abort_if($unit->unit_type_id !== $unitType->id, 404);

        return Inertia::render('Admin/Units/EditUnit', [
            'unitType' => $unitType,
            'unit' => $unit,
        ]);
    }

    // Update unit
    public function update(Request $request, UnitType $unitType, Unit $unit)
    {
        abort_if($unit->unit_type_id !== $unitType->id, 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:50'],
            'active' => ['boolean'],
        ]);

        $unit->update([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.unit-types.show', $unitType->getRouteKey())
            ->with('success', 'Unit updated successfully.');
    }

    // Delete unit
    public function destroy(UnitType $unitType, Unit $unit)
    {
        abort_if($unit->unit_type_id !== $unitType->id, 404);

        $unit->delete();

        return redirect()->route('admin.unit-types.show', $unitType->getRouteKey())
            ->with('success', 'Unit deleted successfully.');
    }
}
