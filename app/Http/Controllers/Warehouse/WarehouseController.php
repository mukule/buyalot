<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\User;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseManager;
use App\Models\Warehouse\WarehouseProductInventory;
use App\Traits\HasPermissionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;


class WarehouseController extends Controller
{
    use HasPermissionCheck;

    /**
     * Display a listing of warehouses.
     */
    public function index()
    {
        $permissionCheck = $this->checkPermissionOrFail('view-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $warehouses = Warehouse::with(['region', 'managers'])
            ->orderBy('created_at', 'desc')
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
        $permissionCheck = $this->checkPermissionOrFail('create-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['store_manager', 'warehouse_manager']);
        })->select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Warehouses/Create', [
            'regions' => Region::select('id', 'name')->get(),
            'parentWarehouses' => Warehouse::select('id', 'name')->get(),
            'users' => $users,
            'types' => ['warehouse', 'store', 'pickup_point', 'dispatch_center','general'],
        ]);
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $permissionCheck = $this->checkPermissionOrFail('create-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name',
            'code' => 'nullable|string|max:50|unique:warehouses,code',
            'type' => 'required|in:warehouse,store,pickup_point,dispatch_center,general',
            'region_id' => 'nullable|exists:regions,id',
            'parent_warehouse_id' => 'nullable|exists:warehouses,id',
            'address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'is_default' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'supports_pos' => 'sometimes|boolean',
            'supports_pickup' => 'sometimes|boolean',
            'supports_delivery' => 'sometimes|boolean',
            'managers' => 'nullable|array',
            'managers.*.name' => 'required|string|max:255',
            'managers.*.email' => 'nullable|email|max:255',
            'managers.*.phone' => 'nullable|string|max:20',
            'managers.*.role' => 'required|in:general_manager,inventory_manager,dispatch_officer,sales_manager,pos_operator',
        ]);

        DB::transaction(function () use ($validated) {
            // Generate slug
            $slug = Str::slug($validated['name']);
            $count = Warehouse::where('slug', 'like', "$slug%")->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }

            // Generate unique code if not provided
            $code = $validated['code'] ?? strtoupper(Str::random(10));

            $warehouse = Warehouse::create([
                'code' => "WH".$code,
                'name' => $validated['name'],
                'slug' => $slug,
                'type' => $validated['type'],
                'region_id' => $validated['region_id'] ?? null,
                'parent_warehouse_id' => $validated['parent_warehouse_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'location' => $validated['location'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'active' => $validated['active'] ?? true,
                'supports_pos' => $validated['supports_pos'] ?? false,
                'supports_pickup' => $validated['supports_pickup'] ?? false,
                'supports_delivery' => $validated['supports_delivery'] ?? true,
            ]);

            // Save managers if provided
            if (!empty($validated['managers'])) {
                foreach ($validated['managers'] as $manager) {
                    WarehouseManager::create([
                        'warehouse_id' => $warehouse->id,
                        'name' => $manager['name'],
                        'email' => $manager['email'] ?? null,
                        'phone' => $manager['phone'] ?? null,
                        'role' => $manager['role'],
                        'active' => true,
                    ]);
                }
            }
        });

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse)
    {
        $permissionCheck = $this->checkPermissionOrFail('update-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $warehouse->load(['region', 'managers']);

        return Inertia::render('Admin/Warehouses/Edit', [
            'warehouse' => $warehouse,
            'regions' => Region::select('id', 'name')->get(),
            'types' => ['warehouse', 'store', 'pickup_point', 'dispatch_center'],
        ]);
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $permissionCheck = $this->checkPermissionOrFail('update-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $warehouse->id,
            'code' => 'nullable|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'type' => 'required|in:warehouse,store,pickup_point,dispatch_center',
            'region_id' => 'nullable|exists:regions,id',
            'parent_warehouse_id' => 'nullable|exists:warehouses,id',
            'address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'is_default' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'supports_pos' => 'sometimes|boolean',
            'supports_pickup' => 'sometimes|boolean',
            'supports_delivery' => 'sometimes|boolean',
            'managers' => 'nullable|array',
            'managers.*.id' => 'nullable|exists:warehouse_managers,id',
            'managers.*.name' => 'required|string|max:255',
            'managers.*.email' => 'nullable|email|max:255',
            'managers.*.phone' => 'nullable|string|max:20',
            'managers.*.role' => 'required|in:general_manager,inventory_manager,dispatch_officer,sales_manager,pos_operator',
        ]);

        DB::transaction(function () use ($warehouse, $validated) {
            // Update slug if name changed
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

            $warehouse->update([
                'code' => $validated['code'] ?? $warehouse->code,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'region_id' => $validated['region_id'] ?? null,
                'parent_warehouse_id' => $validated['parent_warehouse_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'location' => $validated['location'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'active' => $validated['active'] ?? true,
                'supports_pos' => $validated['supports_pos'] ?? false,
                'supports_pickup' => $validated['supports_pickup'] ?? false,
                'supports_delivery' => $validated['supports_delivery'] ?? true,
            ]);

            // Update or create managers
            if (!empty($validated['managers'])) {
                $existingIds = [];
                foreach ($validated['managers'] as $managerData) {
                    if (!empty($managerData['id'])) {
                        $manager = WarehouseManager::find($managerData['id']);
                        $manager->update([
                            'name' => $managerData['name'],
                            'email' => $managerData['email'] ?? null,
                            'phone' => $managerData['phone'] ?? null,
                            'role' => $managerData['role'],
                            'active' => true,
                        ]);
                        $existingIds[] = $manager->id;
                    } else {
                        $new = WarehouseManager::create([
                            'warehouse_id' => $warehouse->id,
                            'name' => $managerData['name'],
                            'email' => $managerData['email'] ?? null,
                            'phone' => $managerData['phone'] ?? null,
                            'role' => $managerData['role'],
                            'active' => true,
                        ]);
                        $existingIds[] = $new->id;
                    }
                }

                // Remove managers not present in update payload
                WarehouseManager::where('warehouse_id', $warehouse->id)
                    ->whereNotIn('id', $existingIds)
                    ->delete();
            }
        });

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $permissionCheck = $this->checkPermissionOrFail('delete-warehouses');
        if ($permissionCheck) {
            return $permissionCheck;
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }



    public function assignManagers(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'managers' => 'required|array|min:1',
            'managers.*.name' => 'required|string|max:255',
            'managers.*.role' => 'nullable|string|max:255',
            'managers.*.phone' => 'nullable|string|max:255',
        ]);
        $warehouse->managers()->delete();
        $warehouse->managers()->createMany($validated['managers']);

        return back()->with('success', 'Warehouse managers updated successfully.');
    }

    public function getAssignableUsers()
    {
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['store_manager', 'warehouse_manager','store_keeper']);
        })->select('id', 'name', 'email')->get();

        return response()->json(['users' => $users]);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load([
            'region',
            'managers',
            'inventories.productVariant.product',
        ]);

        return inertia('Admin/Warehouses/InventoryView', [
            'warehouse' => $warehouse,
            'inventories' => $warehouse->inventories->map(function ($inv) {
                return [
                    'product_name' => $inv->productVariant->product->name ?? 'N/A',
                    'variant_name' => $inv->productVariant->variant_name ?? '',
                    'stock' => $inv->stock,
                    'reserved_stock' => $inv->reserved_stock,
                    'damaged_stock' => $inv->damaged_stock,
                    'cost_price' => $inv->cost_price,
                ];
            }),
        ]);
    }

    public function inventory(Request $request, Warehouse $warehouse)
    {
        $query = WarehouseProductInventory::with('productVariant.product')
            ->where('warehouse_id', $warehouse->id);

        // Search & filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('productVariant.product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('productVariant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Paginate
        $perPage = $request->input('per_page', 20);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        // Transform for frontend
        $inventories = $paginated->getCollection()->map(function ($inv) {
            return [
                'id' => $inv->id,
                'product_name' => $inv->productVariant->product->name,
                'variant_name' => $inv->productVariant->name,
                'stock' => $inv->stock,
                'reserved_stock' => $inv->reserved_stock,
                'damaged_stock' => $inv->damaged_stock,
                'cost_price' => $inv->cost_price,
            ];
        });

        return Inertia::render('Admin/Warehouses/InventoryView', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'capacity' => $warehouse->capacity,
            ],
            'inventories' => $inventories,
            'pagination' => [
                'links' => $paginated->links(),
                'meta' => $paginated->toArray(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function updateInventory(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'inventories.*.id' => 'required|exists:warehouse_product_inventories,id',
            'inventories.*.stock' => 'required|integer|min:0',
            'inventories.*.reserved_stock' => 'required|integer|min:0',
            'inventories.*.damaged_stock' => 'required|integer|min:0',
            'inventories.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['inventories'] as $item) {
            WarehouseProductInventory::where('id', $item['id'])
                ->update([
                    'stock' => $item['stock'],
                    'reserved_stock' => $item['reserved_stock'],
                    'damaged_stock' => $item['damaged_stock'],
                    'cost_price' => $item['cost_price'],
                ]);
        }

        return back()->with('success', 'Inventory updated successfully.');
    }

    public function adjustStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'inventory_id' => 'required|exists:warehouse_product_inventories,id',
            'type' => 'required|in:increase,decrease,damaged',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = WarehouseProductInventory::findOrFail($request->inventory_id);

        switch ($request->type) {
            case 'increase':
                $inventory->stock += $request->quantity;
                break;
            case 'decrease':
                if ($inventory->stock < $request->quantity) {
                    return back()->withErrors('Insufficient stock to decrease');
                }
                $inventory->stock -= $request->quantity;
                break;
            case 'damaged':
                $inventory->damaged_stock += $request->quantity;
                if ($inventory->stock < $request->quantity) $inventory->stock = 0;
                else $inventory->stock -= $request->quantity;
                break;
        }

        $inventory->save();
        return back()->with('success', 'Stock updated successfully.');
    }

    public function transferStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'inventory_id' => 'required|exists:warehouse_product_inventories,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = WarehouseProductInventory::findOrFail($request->inventory_id);
        if ($inventory->stock < $request->quantity) return back()->withErrors('Insufficient stock');

        $inventory->stock -= $request->quantity;
        $inventory->save();

        // Add to target warehouse
        $targetInventory = WarehouseProductInventory::firstOrCreate([
            'warehouse_id' => $request->to_warehouse_id,
            'product_variant_id' => $inventory->product_variant_id,
        ], [
            'stock' => 0,
            'reserved_stock' => 0,
            'damaged_stock' => 0,
            'cost_price' => $inventory->cost_price,
        ]);

        $targetInventory->stock += $request->quantity;
        $targetInventory->save();

        return back()->with('success', 'Stock transferred successfully.');
    }


}
