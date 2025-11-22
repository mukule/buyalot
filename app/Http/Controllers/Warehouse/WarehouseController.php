<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\WarehouseActivityLog;
use App\Models\Warehouse\WarehouseManager;
use App\Models\Warehouse\WarehouseProductInventory;
use App\Models\Warehouse\WarehouseInventoryMovement;
use App\Models\Warehouse\WarehouseReceivable;
use App\Traits\HasPermissionCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;


class WarehouseController extends Controller
{
    use HasPermissionCheck;

    protected function logActivity(Warehouse $warehouse, string $action, $reference = null, array $details = []): void
    {
        WarehouseActivityLog::create([
            'warehouse_id' => $warehouse->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }


    public function searchVariants(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $q = trim((string)$request->get('q', ''));
        $excludeExisting = (bool)$request->get('exclude_existing', true);

        $existingVariantIds = [];
        if ($excludeExisting) {
            $existingVariantIds = WarehouseProductInventory::where('warehouse_id', $warehouse->id)
                ->pluck('product_variant_id')
                ->all();
        }

        $variantsQuery = \App\Models\ProductVariant::with(['product:id,name', 'values.variant'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%");
                })->orWhereHas('values.variant', function ($sub) use ($q) {
                    $sub->where('value', 'like', "%{$q}%");
                })->orWhere('sku', 'like', "%{$q}%");
            })
            ->when(!empty($existingVariantIds), function ($query) use ($existingVariantIds) {
                $query->whereNotIn('id', $existingVariantIds);
            })
            ->orderBy('id', 'desc')
            ->limit(25);

        $variants = $variantsQuery->get()->map(function ($v) {
            return [
                'id' => $v->id,
                'product_name' => optional($v->product)->name,
                'variant_values' => $v->values->map(fn($pv) => $pv->variant->value)->filter()->join(', '),
                'sku' => $v->sku,
                'display_name' => $v->display_name,
            ];
        });

        return response()->json([
            'variants' => $variants,
        ]);
    }

    // New endpoints for cascading selection in Add Product modal
    public function categories(Request $request, Warehouse $warehouse): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $parentId = $request->input('parent_id');

        $query = Category::active()
            ->select('id', 'name', 'parent_id')
            ->when(is_null($parentId), function ($q) {
                $q->whereNull('parent_id');
            }, function ($q) use ($parentId) {
                $q->where('parent_id', $parentId);
            })
            ->orderBy('name');

        $categories = $query->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'parent_id' => $cat->parent_id,
                'has_children' => $cat->children()->active()->exists(),
            ];
        });

        return response()->json(['categories' => $categories]);
    }

    public function productsByCategory(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $products = Product::select('id', 'name')
            ->where('category_id', $data['category_id'])
            ->orderBy('name')
            ->get();

        return response()->json(['products' => $products]);
    }

    public function variantsByProduct(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $excludeExisting = $request->boolean('exclude_existing', true);
        $existingVariantIds = [];
        if ($excludeExisting) {
            $existingVariantIds = WarehouseProductInventory::where('warehouse_id', $warehouse->id)
                ->pluck('product_variant_id')
                ->all();
        }

        $variants = ProductVariant::with(['values.variant'])
            ->where('product_id', $validated['product_id'])
            ->when(!empty($existingVariantIds), function ($query) use ($existingVariantIds) {
                $query->whereNotIn('id', $existingVariantIds);
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'variant_values' => $v->values->map(fn($pv) => $pv->variant->value)->filter()->join(', '),
                    'sku' => $v->sku,
                    'display_name' => $v->display_name,
                ];
            });

        return response()->json(['variants' => $variants]);
    }

    private function sellerOwns(Warehouse $warehouse): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('seller') && (int)$warehouse->created_by === (int)$user->id;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index()
    {
        // Sellers can access their own warehouses list without explicit permission
        if (!auth()->user()?->hasRole('seller')) {
            $permissionCheck = $this->checkPermissionOrFail('view-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $warehouses = Warehouse::visibleTo(auth()->user())
            ->with(['region', 'managers'])->withCount('pendingReceivables')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        // Sellers may create their own warehouse
        if (!auth()->user()?->hasRole('seller')) {
            $permissionCheck = $this->checkPermissionOrFail('create-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
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
        if (!auth()->user()?->hasRole('seller')) {
            $permissionCheck = $this->checkPermissionOrFail('create-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name',
            'code' => 'nullable|string|max:50|unique:warehouses,code',
            'type' => 'required|in:warehouse,store,pickup_point,dispatch_center,general',
            'region_id' => 'nullable|exists:regions,id',
            'parent_warehouse_id' => 'nullable|exists:warehouses,id',
            'address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:0',
            'is_default' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'supports_pos' => 'sometimes|boolean',
            'supports_pickup' => 'sometimes|boolean',
            'supports_delivery' => 'sometimes|boolean',
            'regions' => 'nullable|array',
            'regions.*' => 'integer|exists:regions,id',
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
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'address' => $validated['address'] ?? null,
                'location' => $validated['location'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'active' => $validated['active'] ?? true,
                'supports_pos' => $validated['supports_pos'] ?? false,
                'supports_pickup' => $validated['supports_pickup'] ?? false,
                'supports_delivery' => $validated['supports_delivery'] ?? true,
                'created_by' => auth()->id(),
            ]);

            // Sync coverage regions if provided
            if (!empty($validated['regions'])) {
                $warehouse->regions()->sync($validated['regions']);
            }

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
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('update-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
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
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('update-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $warehouse->id,
            'code' => 'nullable|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'type' => 'required|in:warehouse,store,pickup_point,dispatch_center',
            'region_id' => 'nullable|exists:regions,id',
            'parent_warehouse_id' => 'nullable|exists:warehouses,id',
            'address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:0',
            'is_default' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'supports_pos' => 'sometimes|boolean',
            'supports_pickup' => 'sometimes|boolean',
            'supports_delivery' => 'sometimes|boolean',
            'regions' => 'nullable|array',
            'regions.*' => 'integer|exists:regions,id',
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
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'active' => $validated['active'] ?? true,
                'supports_pos' => $validated['supports_pos'] ?? false,
                'supports_pickup' => $validated['supports_pickup'] ?? false,
                'supports_delivery' => $validated['supports_delivery'] ?? true,
            ]);

            if (array_key_exists('regions', $validated)) {
                $warehouse->regions()->sync($validated['regions'] ?? []);
            }

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
    public function toggleStatus(Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('update-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $current = $warehouse->status ?? ($warehouse->active ? 'active' : 'inactive');
        $new = $current === 'active' ? 'inactive' : 'active';
        $warehouse->status = $new;
        $warehouse->active = ($new === 'active');
        $warehouse->save();

        return back()->with('success', "Warehouse status updated to {$new}.");
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('delete-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }



    public function assignManagers(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-warehouse-staff');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
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
            $q->whereIn('name', [
                'warehouse_manager',
                'store_keeper',
                'delivery',
                'store_manager',
                'assistant_store_manager',
                'assistant_warehouse_manager',
                'record_keeper',
            ]);
        })->select('id', 'name', 'email')->get();

        return response()->json(['users' => $users]);
    }

    public function getAssignableRoles()
    {
        $roles = Role::whereIn('name', [
            'warehouse_manager',
            'store_keeper',
            'delivery',
            'store_manager',
            'assistant_store_manager',
            'assistant_warehouse_manager',
            'record_keeper',
        ])->get(['id','name']);

        return response()->json(['roles' => $roles]);
    }

    public function show(Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-warehouses');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
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
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('view-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $query = WarehouseProductInventory::with(['productVariant.product', 'productVariant.values.variant'])
            ->where('warehouse_id', $warehouse->id);

        // Search & filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('productVariant.product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('productVariant.values.variant', function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%");
            });
        }

        // Paginate
        $perPage = $request->input('per_page', 20);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        // Transform for frontend
        $inventories = $paginated->getCollection()->map(function ($inv) {
            $variantName = optional($inv->productVariant->values)->map(fn($pv) => $pv->variant->value)->filter()->join(', ');
            return [
                'id' => $inv->id,
                'product_name' => optional($inv->productVariant->product)->name,
                'variant_name' => $variantName,
                'stock' => $inv->stock,
                'reserved_stock' => $inv->reserved_stock,
                'damaged_stock' => $inv->damaged_stock,
                'regular_price' => $inv->regular_price,
                'selling_price' => $inv->selling_price,
                'cost_price' => $inv->cost_price,
            ];
        });

        // Metrics
        $totalUnits = WarehouseProductInventory::where('warehouse_id', $warehouse->id)->sum('stock');
        $totalVariants = WarehouseProductInventory::where('warehouse_id', $warehouse->id)
            ->distinct('product_variant_id')->count('product_variant_id');

        return Inertia::render('Admin/Warehouses/InventoryView', [
            'warehouse' => [
                'id' => $warehouse->id,
                'hashid' => $warehouse->hashid,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'capacity' => $warehouse->capacity,
            ],
            'inventories' => $inventories,
            'pagination' => [
                'links' => $paginated->toArray()['links'] ?? [],
                'meta' => $paginated->toArray(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
            'summary' => [
                'total_units' => $totalUnits,
                'total_variants' => $totalVariants,
                'capacity' => $warehouse->capacity,
                'remaining_capacity' => max(0, (int)($warehouse->capacity ?? 0) - (int)$totalUnits),
            ],
            'warehouses' => \App\Models\Warehouse\Warehouse::visibleTo(auth()->user())
                ->where('id', '!=', $warehouse->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function addInventory(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $data = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'regular_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        // Check if this variant already exists in the warehouse
        $inventory = WarehouseProductInventory::where('warehouse_id', $warehouse->id)
            ->where('product_variant_id', $data['product_variant_id'])
            ->first();

        if ($inventory) {
            $inventory->increment('stock', $data['quantity']);
            $inventory->update([
                'regular_price' => $data['regular_price'] ?? $inventory->regular_price,
                'selling_price' => $data['selling_price'] ?? $inventory->selling_price,
                'cost_price' => $data['cost_price'] ?? $inventory->cost_price,
            ]);
        } else {
            $inventory = WarehouseProductInventory::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $data['product_variant_id'],
                'stock' => $data['quantity'],
                'reserved_stock' => 0,
                'damaged_stock' => 0,
                'regular_price' => $data['regular_price'] ?? null,
                'selling_price' => $data['selling_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
            ]);
        }

        // Record in warehouse activity log
        $this->logActivity($warehouse, 'inventory_added', $inventory, [
            'variant_id' => $data['product_variant_id'],
            'quantity' => $data['quantity'],
        ]);


        return back()->with('success', 'Product added to warehouse successfully.');
    }



    public function updateInventory(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $data = $request->validate([
            'inventories' => 'required|array',
            'inventories.*.id' => 'required|exists:warehouse_product_inventories,id',
            'inventories.*.stock' => 'required|integer|min:0',
            'inventories.*.reserved_stock' => 'required|integer|min:0',
            'inventories.*.damaged_stock' => 'required|integer|min:0',
            'inventories.*.regular_price' => 'nullable|numeric|min:0',
            'inventories.*.selling_price' => 'nullable|numeric|min:0',
            'inventories.*.cost_price' => 'nullable|numeric|min:0',
        ]);
        $inventory = null;
        $items=[];

        foreach ($data['inventories'] as $item) {
            $inventory=WarehouseProductInventory::where('id', $item['id'])
                ->update([
                    'stock' => $item['stock'],
                    'reserved_stock' => $item['reserved_stock'],
                    'damaged_stock' => $item['damaged_stock'],
                    'regular_price' => $item['regular_price'] ?? null,
                    'selling_price' => $item['selling_price'] ?? null,
                    'cost_price' => $item['cost_price'] ?? null,
                ]);
            $items[]=$item;
        }
        $this->logActivity($warehouse, 'inventory_updated', $inventory, [
            'changes' => $items,
        ]);


        return back()->with('success', 'Inventory updated successfully.');
    }

    public function adjustStock(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('adjust-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $request->validate([
            'inventory_id' => 'required|exists:warehouse_product_inventories,id',
            'type' => 'required|in:increase,decrease,damaged',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        $inventory=null;

        DB::transaction(function () use ($request, $warehouse) {
            $inventory = WarehouseProductInventory::lockForUpdate()->findOrFail($request->inventory_id);
            $before = $inventory->stock;

            switch ($request->type) {
                case 'increase':
                    $inventory->stock += $request->quantity;
                    $movementType = 'adjust_increase';
                    break;
                case 'decrease':
                    if ($inventory->stock < $request->quantity) {
                        abort(422, 'Insufficient stock to decrease');
                    }
                    $inventory->stock -= $request->quantity;
                    $movementType = 'adjust_decrease';
                    break;
                case 'damaged':
                    $inventory->damaged_stock += $request->quantity;
                    $inventory->stock = max(0, $inventory->stock - $request->quantity);
                    $movementType = 'damaged';
                    break;
            }

            $inventory->save();

            WarehouseInventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $inventory->product_variant_id,
                'type' => $movementType,
                'quantity' => (int) $request->quantity,
                'user_id' => auth()->id(),
                'before_stock' => $before,
                'after_stock' => $inventory->stock,
                'note' => $request->note,
            ]);
        });
        $this->logActivity($warehouse, 'stock_adjusted', $inventory, [
            'adjustment' => $request->all(),
        ]);

        return back()->with('success', 'Stock updated successfully.');
    }

    public function transferStock(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('transfer-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $request->validate([
            'inventory_id' => 'required|exists:warehouse_product_inventories,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        $toWarehouse =$request->to_warehouse_id;
        $transferItems =null ;
        $transfer = null;
        DB::transaction(function () use ($request, $warehouse) {
            $inventory = WarehouseProductInventory::lockForUpdate()->findOrFail($request->inventory_id);
            if ($inventory->stock < $request->quantity) {
                abort(422, 'Insufficient stock');
            }
            $before = $inventory->stock;
            $inventory->stock -= $request->quantity;
            $inventory->save();

            // Log transfer out
            $transfer=WarehouseInventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $inventory->product_variant_id,
                'type' => 'transfer_out',
                'quantity' => (int) $request->quantity,
                'from_warehouse_id' => $warehouse->id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'user_id' => auth()->id(),
                'before_stock' => $before,
                'after_stock' => $inventory->stock,
                'note' => $request->note,
            ]);

            // Add to target warehouse
            $targetInventory = WarehouseProductInventory::lockForUpdate()->firstOrCreate([
                'warehouse_id' => $request->to_warehouse_id,
                'product_variant_id' => $inventory->product_variant_id,
            ], [
                'stock' => 0,
                'reserved_stock' => 0,
                'damaged_stock' => 0,
                'regular_price' => $inventory->regular_price,
                'selling_price' => $inventory->selling_price,
                'cost_price' => $inventory->cost_price,
            ]);

            $targetBefore = $targetInventory->stock;
            $targetInventory->stock += $request->quantity;
            $targetInventory->save();

            // Log transfer in
            WarehouseInventoryMovement::create([
                'warehouse_id' => $request->to_warehouse_id,
                'product_variant_id' => $inventory->product_variant_id,
                'type' => 'transfer_in',
                'quantity' => (int) $request->quantity,
                'from_warehouse_id' => $warehouse->id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'user_id' => auth()->id(),
                'before_stock' => $targetBefore,
                'after_stock' => $targetInventory->stock,
                'note' => $request->note,
            ]);
        });
        $this->logActivity($warehouse, 'stock_transferred', $transfer, [
            'to_warehouse' => $toWarehouse,
            'items' => $transferItems,
        ]);

        return back()->with('success', 'Stock transferred successfully.');
    }

    // Minimal Receivables/Dispatches implementations
    public function receivables(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $query = \App\Models\Warehouse\WarehouseReceivable::with(['productVariant.product'])
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'pending');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('productVariant.product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $paginated = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $receivables = $paginated->getCollection()->map(function ($r) {
            return [
                'id' => $r->id,
                'product_name' => optional($r->productVariant->product)->name,
                'variant_display' => method_exists($r->productVariant, 'getDisplayNameAttribute') ? $r->productVariant->display_name : null,
                'quantity' => $r->quantity,
                'note' => $r->note,
                'created_at' => $r->created_at?->toDateTimeString(),
            ];
        });

        return \Inertia\Inertia::render('Admin/Warehouses/Receivables', [
            'warehouse' => [
                'id' => $warehouse->id,
                'hashid' => $warehouse->hashid,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
            ],
            'receivables' => $receivables,
            'pagination' => [
                'links' => $paginated->toArray()['links'] ?? [],
                'meta' => $paginated->toArray(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function createReceivable(Request $request, Warehouse $warehouse)
    {
        logger("receivables");
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $data = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        WarehouseReceivable::create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $data['product_variant_id'],
            'quantity' => (int)$data['quantity'],
            'status' => 'pending',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Receivable created successfully.');
    }

    public function acceptReceivable(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        $data = $request->validate([
            'receivable_id' => 'nullable|exists:warehouse_receivables,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $warehouse) {
            // If receivable_id is provided, use it; else fallback to direct add
            $receivable = null;
            if (!empty($data['receivable_id'])) {
                $receivable = WarehouseReceivable::lockForUpdate()
                    ->where('id', $data['receivable_id'])
                    ->where('warehouse_id', $warehouse->id)
                    ->firstOrFail();
                if ($receivable->status !== 'pending') {
                    abort(422, 'Receivable is not pending.');
                }
                $productVariantId = $receivable->product_variant_id;
                $quantity = (int)$receivable->quantity;
            } else {
                // Backward compatibility: direct add to inventory
                if (empty($data['product_variant_id']) || empty($data['quantity'])) {
                    abort(422, 'Invalid payload.');
                }
                $productVariantId = (int)$data['product_variant_id'];
                $quantity = (int)$data['quantity'];
            }

            $inv = WarehouseProductInventory::lockForUpdate()->firstOrCreate([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $productVariantId,
            ], [
                'stock' => 0,
                'reserved_stock' => 0,
                'damaged_stock' => 0,
                'cost_price' => 0,
            ]);

            $before = $inv->stock;
            $inv->stock += $quantity;
            $inv->save();

            WarehouseInventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $productVariantId,
                'type' => 'receive',
                'quantity' => $quantity,
                'user_id' => auth()->id(),
                'before_stock' => $before,
                'after_stock' => $inv->stock,
                'note' => $data['note'] ?? null,
            ]);

            if ($receivable) {
                $receivable->status = 'received';
                $receivable->received_by = auth()->id();
                $receivable->received_at = now();
                $receivable->save();
            }
        });

        return back()->with('success', 'Items received successfully.');
    }

    public function dispatches(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        return redirect()->route('admin.inventory', ['warehouse' => $warehouse->id]);
    }

    public function createDispatch(Request $request, Warehouse $warehouse)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }
        $data = $request->validate([
            'inventory_id' => 'required|exists:warehouse_product_inventories,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($data, $warehouse) {
            $inv = WarehouseProductInventory::lockForUpdate()->findOrFail($data['inventory_id']);
            if ($inv->stock < $data['quantity']) {
                abort(422, 'Insufficient stock for dispatch');
            }
            $before = $inv->stock;
            $inv->stock -= (int) $data['quantity'];
            $inv->save();

            WarehouseInventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $inv->product_variant_id,
                'type' => 'dispatch',
                'quantity' => (int) $data['quantity'],
                'user_id' => auth()->id(),
                'before_stock' => $before,
                'after_stock' => $inv->stock,
                'note' => $data['note'] ?? null,
            ]);
        });

        return back()->with('success', 'Dispatch created successfully.');
    }

    public function publishInventory(Request $request, Warehouse $warehouse, WarehouseProductInventory $inventory)
    {
        if (!$this->sellerOwns($warehouse)) {
            $permissionCheck = $this->checkPermissionOrFail('manage-inventory');
            if ($permissionCheck) {
                return $permissionCheck;
            }
        }

        // Ensure the inventory belongs to the warehouse context
        if ($inventory->warehouse_id !== $warehouse->id) {
            abort(404);
        }

        $data = $request->validate([
            'pricing_source' => 'nullable|in:this,keep,override',
            'regular_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'all' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($warehouse, $inventory, $data) {
            // Lock inventory row for update
            $inv = WarehouseProductInventory::lockForUpdate()->findOrFail($inventory->id);
            $variant = ProductVariant::lockForUpdate()->findOrFail($inv->product_variant_id);

            // Determine publish quantity
            $publishAll = (bool)($data['all'] ?? false);
            $requestedQty = isset($data['quantity']) ? (int)$data['quantity'] : null;
            $qty = $publishAll ? (int)$inv->stock : ($requestedQty ?? 0);

            if ($qty <= 0) {
                abort(422, 'Publish quantity must be greater than zero or select All.');
            }
            if ($qty > $inv->stock) {
                abort(422, 'Insufficient stock in warehouse for publish.');
            }

            // Decrease warehouse inventory, increase public variant stock
            $beforeInvStock = $inv->stock;
            $inv->stock -= $qty;
            $inv->save();

            $beforeVariantStock = (int)$variant->stock;
            $variant->stock = $beforeVariantStock + $qty;

            // Pricing strategy: by default copy from this inventory if present
            $pricingSource = $data['pricing_source'] ?? 'this';
            if ($pricingSource === 'this') {
                if (!is_null($inv->regular_price)) {
                    $variant->regular_price = $inv->regular_price;
                }
                if (!is_null($inv->selling_price)) {
                    $variant->selling_price = $inv->selling_price;
                }
            } elseif ($pricingSource === 'override') {
                if (array_key_exists('regular_price', $data) && $data['regular_price'] !== null) {
                    $variant->regular_price = $data['regular_price'];
                }
                if (array_key_exists('selling_price', $data) && $data['selling_price'] !== null) {
                    $variant->selling_price = $data['selling_price'];
                }
            }

            $variant->save();

            // Log movement
            WarehouseInventoryMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $inv->product_variant_id,
                'type' => 'publish',
                'quantity' => $qty,
                'user_id' => auth()->id(),
                'before_stock' => $beforeInvStock,
                'after_stock' => $inv->stock,
                'note' => 'Published to variants inventory',
            ]);
        });

        return back()->with('success', 'Published to variant inventory successfully.');
    }
}
