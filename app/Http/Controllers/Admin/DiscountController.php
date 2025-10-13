<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment\Discount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::query()->orderByDesc('created_at');

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
        return Inertia::render('Admin/Discounts/Create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // Normalize optional JSON fields
        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        // Generate slug if missing
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']) . '-' . strtolower(\Illuminate\Support\Str::random(6));
        }

        $discount = Discount::create($data);

        return redirect()->route('admin.discounts.edit', $discount->slug)
            ->with('success', 'Discount created successfully');
    }

    public function edit(Discount $discount)
    {
        return Inertia::render('Admin/Discounts/Edit', [
            'discount' => $discount,
        ]);
    }

    public function update(Request $request, Discount $discount)
    {
        $data = $this->validateData($request, $discount->id);
        $data['conditions'] = $this->decodeJsonOrNull($request->input('conditions'));
        $data['metadata'] = $this->decodeJsonOrNull($request->input('metadata'));

        // If slug left blank, keep existing
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $discount->update($data);

        return back()->with('success', 'Discount updated successfully');
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
            'type' => ['required', Rule::in(['percentage', 'fixed_amount', 'fixed', 'bogo', 'buy_x_get_y', 'free_shipping'])],
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
