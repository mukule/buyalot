<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;

class CustomerAddressController extends Controller
{
    // JSON API: List current customer's addresses
    public function apiList(): \Illuminate\Http\JsonResponse
    {
        $customer = auth()->user()?->customer;
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $addresses = $customer->addresses()->orderByDesc('is_default')->get();
        return response()->json([
            'success' => true,
            'data' => [
                'addresses' => $addresses,
                'default_id' => optional($customer->defaultAddress)->id,
            ],
        ]);
    }

    // JSON API: Create a new address for current customer
    public function apiStore(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $customer = auth()->user()?->customer;
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $data = $request->validate([
            'type' => 'sometimes|string|in:shipping,billing,other',
            'label' => 'sometimes|nullable|string|max:100',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'company' => 'sometimes|nullable|string|max:150',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'sometimes|nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'sometimes|nullable|string|max:120',
            'postal_code' => 'sometimes|nullable|string|max:30',
            'country' => 'sometimes|string|max:2',
            'phone' => 'sometimes|nullable|string|max:30',
            'is_default' => 'sometimes|boolean',
            'delivery_instructions' => 'sometimes|nullable|string|max:500',
            'coordinates' => 'sometimes|array',
            'coordinates.lat' => 'sometimes|numeric',
            'coordinates.lng' => 'sometimes|numeric',
            'coordinates.accuracy' => 'sometimes|numeric',
        ]);
        $data['type'] = $data['type'] ?? 'shipping';

        // Map incoming fields to DB columns
        $payload = [
            'type' => $data['type'],
            'label' => $data['label'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'company' => $data['company'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'state_province' => $data['state'] ?? ($request->input('state') ?? ''),
            'postal_code' => $data['postal_code'] ?? ($request->input('postal_code') ?? ''),
            'country_code' => $data['country'] ?? ($request->input('country') ?? 'KE'),
            'country_name' => ($data['country'] ?? $request->input('country') ?? 'KE') === 'KE' ? 'Kenya' : ($request->input('country_name') ?? ''),
            'phone' => $data['phone'] ?? null,
            'is_default' => (bool)($data['is_default'] ?? true),
            'delivery_instructions' => $data['delivery_instructions'] ?? null,
        ];
        $coords = $data['coordinates'] ?? null;
        if (is_array($coords)) {
            if (isset($coords['lat'])) {
                $payload['latitude'] = (float)$coords['lat'];
            }
            if (isset($coords['lng'])) {
                $payload['longitude'] = (float)$coords['lng'];
            }
        }

        // Create and ensure default uniqueness per customer
        $address = $customer->addresses()->create($payload);
        if (!empty($payload['is_default'])) {
            $address->makeDefault();
        }
        return response()->json(['success' => true, 'address' => $address], 201);
    }

    // JSON API: Make default for current customer
    public function apiMakeDefault(CustomerAddress $address): \Illuminate\Http\JsonResponse
    {
        $customer = auth()->user()?->customer;
        if (!$customer || $address->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        $address->makeDefault();
        return response()->json(['success' => true, 'default_id' => $address->id]);
    }
    public function index(Customer $customer)
    {
        $addresses = $customer->addresses()->get();
        return view('customers.addresses.index', compact('customer', 'addresses'));
    }

    public function create(Customer $customer)
    {
        return view('customers.addresses.create', compact('customer'));
    }

    public function store(CustomerAddressRequest $request, Customer $customer)
    {
        $address = $customer->addresses()->create($request->validated());

        if ($request->is_default) {
            $address->makeDefault();
        }

        return redirect()->route('customers.addresses.index', $customer)
            ->with('success', 'Address added successfully.');
    }

    public function show(Customer $customer, CustomerAddress $address)
    {
        return view('customers.addresses.show', compact('customer', 'address'));
    }

    public function edit(Customer $customer, CustomerAddress $address)
    {
        return view('customers.addresses.edit', compact('customer', 'address'));
    }

    public function update(CustomerAddressRequest $request, Customer $customer, CustomerAddress $address)
    {
        $address->update($request->validated());

        if ($request->is_default) {
            $address->makeDefault();
        }

        return redirect()->route('customers.addresses.index', $customer)
            ->with('success', 'Address updated successfully.');
    }

    public function destroy(Customer $customer, CustomerAddress $address)
    {
        if ($address->is_default && $customer->addresses()->count() > 1) {
            return back()->with('error', 'Cannot delete default address. Please set another address as default first.');
        }

        $address->delete();

        return redirect()->route('customers.addresses.index', $customer)
            ->with('success', 'Address deleted successfully.');
    }

    public function makeDefault(Customer $customer, CustomerAddress $address)
    {
        $address->makeDefault();

        return back()->with('success', 'Default address updated successfully.');
    }
}
