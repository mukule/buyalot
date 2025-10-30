<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment\DiscountType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscountTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = DiscountType::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $discountTypes = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/DiscountTypes/Index', [
            'discountTypes' => $discountTypes,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/DiscountTypes/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:discount_types,code',
            'description' => 'nullable|string',
        ]);

        DiscountType::create($validated);

        return redirect()->route('admin.discount-types.index')->with('success', 'Discount Type created successfully.');
    }

    public function edit(DiscountType $discountType)
    {
        return Inertia::render('Admin/DiscountTypes/Edit', [
            'discountType' => $discountType,
        ]);
    }

  
    public function update(Request $request, DiscountType $discountType)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:255|unique:discount_types,code,' . $discountType->id,
        'description' => 'nullable|string',
        'is_active' => 'required|boolean',
    ]);

    // Ensure boolean is cast to tinyint
    $validated['is_active'] = $validated['is_active'] ? 1 : 0;

    $discountType->update($validated);

    return redirect()
        ->route('admin.discount-types.index')
        ->with('success', 'Discount Type updated successfully.');
}


    public function destroy(DiscountType $discountType)
    {
        $discountType->delete();

        return back()->with('success', 'Discount Type deleted successfully.');
    }

    public function toggleStatus(DiscountType $discountType)
    {
        $discountType->update(['is_active' => !$discountType->is_active]);

        return back()->with('success', 'Discount Type status updated.');
    }
}
