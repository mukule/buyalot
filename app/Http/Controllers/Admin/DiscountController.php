<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Payment\Discount;
use App\Models\Payment\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::with('discountType')->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $discounts = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Discounts/Index', [
            'discounts' => $discounts,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        // Provide minimal datasets for selection UIs
        $categories = \App\Models\Category::select('id', 'name', 'parent_id')->orderBy('name')->get();
        $products = \App\Models\Product::select('id', 'name', 'category_id')->orderBy('name')->limit(1000)->get();
        $variants = \App\Models\ProductVariant::select('id', 'product_id')->orderBy('id')->limit(1000)->get();
        $customers = \App\Models\Customer\Customer::select('id', 'first_name','last_name','email', 'created_at')->orderByDesc('created_at')->limit(1000)->get();
        $brands = Brand::select('id', 'name', 'slug', 'active', 'logo_path', 'created_at')->orderByDesc('created_at')->limit(1000)->get();
        $discountTypes = DiscountType::where('is_active', true)->get(['code', 'name']);
        return Inertia::render('Admin/Discounts/Create', [
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'customers' => $customers,
            'discountTypes' => $discountTypes,
            'brands' => $brands,
        ]);
    }

    public function store(Request $request)
    {
        logger("store discount");
        logger(request()->all());

        $data = $this->validateData($request);

        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . strtolower(Str::random(6));
        }
        $discount = Discount::create($data);
        $conditions = json_decode($request->input('conditions'), true);
        if ($conditions && isset($conditions['applies_to'])) {
            switch ($conditions['applies_to']) {
                case 'items':
                    if (isset($conditions['product_ids'])) {
                        $discount->products()->attach($conditions['product_ids']);
                    }
                    if (isset($conditions['category_ids'])) {
                        $discount->categories()->attach($conditions['category_ids']);
                    }
                    if (isset($conditions['variant_ids'])) {
                        $discount->variants()->attach($conditions['variant_ids']);
                    }
                    break;

                case 'customers':
                    if (isset($conditions['customer_ids'])) {
                        $discount->customers()->attach($conditions['customer_ids']);
                    }
                    break;
            }
        }

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Discount created successfully');
    }


    public function store1(Request $request)
    {
        logger("store discount");
        logger(request()->all());
        $data = $this->validateData($request);

        // Normalize optional JSON fields
        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        // Generate slug if missing
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']) . '-' . strtolower(\Illuminate\Support\Str::random(6));
        }

        $discount = Discount::create($data);

        return redirect()->route('admin.discounts.index', $discount->slug)
            ->with('success', 'Discount created successfully');
    }

    public function edit(Discount $discount)
    {
        $categories = \App\Models\Category::select('id', 'name', 'parent_id')->orderBy('name')->get();
        $products = \App\Models\Product::select('id', 'name', 'category_id')->orderBy('name')->limit(500)->get();
        // ProductVariant table has no 'name' column; rely on appends (display_name) and include sku
        $variants = \App\Models\ProductVariant::select('id', 'product_id', 'sku')->orderBy('id')->limit(1000)->get();
        $customers = \App\Models\Customer\Customer::select('id', 'first_name','last_name','email', 'created_at')->orderByDesc('created_at')->limit(500)->get();
        $discountTypes = DiscountType::where('is_active', true)->get(['code', 'name']);
        $brands = Brand::select('id', 'name', 'slug', 'active', 'logo_path', 'created_at')->orderByDesc('created_at')->limit(1000)->get();

        return Inertia::render('Admin/Discounts/Edit', [
            'discount' => $discount,
            'categories' => $categories,
            'products' => $products,
            'variants' => $variants,
            'customers' => $customers,
            'discountTypes' => $discountTypes,
            'brands' => $brands,
        ]);
    }


    public function update(Request $request, Discount $discount)
    {
        $data = $this->validateData($request, $discount->id);
        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        if (empty($data['slug'])) unset($data['slug']);
        $discount->update($data);

        // Sync relations
        $conditions = json_decode($request->input('conditions'), true);
        $discount->products()->sync($conditions['product_ids'] ?? []);
        $discount->categories()->sync($conditions['category_ids'] ?? []);
        $discount->variants()->sync($conditions['variant_ids'] ?? []);
        $discount->customers()->sync($conditions['customer_ids'] ?? []);

        return redirect()->route('admin.discounts.index')->with('success', 'Discount updated successfully');
    }


    public function update1(Request $request, Discount $discount)
    {
        $data = $this->validateData($request, $discount->id);
        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        // If slug left blank, keep existing
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $discount->update($data);

        return redirect()->route('admin.discounts.index')->with('success', 'Discount updated successfully');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return redirect()->route('admin.discounts.index')->with('success', 'Discount deleted');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('discounts', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('discounts', 'code')->ignore($id)],
            'type' => ['required', Rule::in(['percentage', 'fixed', 'bogo', 'buy_x_get_y', 'free_shipping'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'applicable_to' => ['nullable', 'string', 'max:50'],
            'conditions' => ['nullable'], // JSON (string in form)
            'metadata' => ['nullable'], // JSON
            'discount_type_code' => ['nullable', 'string', 'max:255'],
            'discount_type_name' => ['nullable', 'string', 'max:255'],
            'discount_type_value' => ['nullable', 'numeric', 'min:0'],
            'no_time_limit' => ['nullable', 'boolean'],
        ]);
    }

    private function decodeJsonOrNull($value)
    {
        if ($value === null || $value === '') return null;
        if (is_array($value)) return $value;
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (\Throwable $e) {
            // Keep as null if invalid JSON
            return null;
        }
    }
}
