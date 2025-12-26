<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\Customer\CustomerAddress;
use Inertia\Inertia;
use App\Services\CartReservationService;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{

   
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


public function index(CartReservationService $cartService)
{
    $customer = auth()->user()?->customer;

    if (!$customer) {
        return redirect()->back()->with('error', 'An error occurred: User details not found for the account.');
    }

    // Fetch all customer addresses with eager-loaded relations and map to simplified format
    $addresses = $customer->addresses()
        ->with(['pickupPoint.region'])
        ->orderByDesc('is_default')
        ->get()
        ->map(fn($address) => [
            'id'           => $address->id,
            'first_name'   => $address->first_name,
            'last_name'    => $address->last_name,
            'phone'        => $address->phone,
            'address'      => $address->address_line_1,
            'region'       => $address->pickupPoint?->region?->name,
            'pickup_point' => $address->pickupPoint?->name,
            'is_default'   => $address->is_default,
        ]);

    $cart = $cartService->getCart(request());
    $totals = $cart->calculateTotals();

    // Split user's full name into first and last name
    $userName = auth()->user()?->name ?? '';
    $nameParts = explode(' ', $userName, 2);

    return Inertia::render('Frontend/Addresses/Index', [
        'customer'       => $customer,
        'addresses'      => $addresses,
        'cart_subtotal'  => $totals['grand_total'],
        'user_name'      => [
            'first_name' => $nameParts[0] ?? '',
            'last_name'  => $nameParts[1] ?? '',
        ],
    ]);
}


public function form(CustomerAddress $address = null, CartReservationService $cartService)
{
    $customer = auth()->user()?->customer;

    if (!$customer) {
        return redirect()->back()->with('error', 'User details not found.');
    }

    // Cart + totals
    $cart = $cartService->getCart(request());
    $totals = $cart->calculateTotals();

    // Split user name
    $userName = auth()->user()?->name ?? '';
    $nameParts = explode(' ', trim($userName), 2);
    $firstName = $nameParts[0] ?? '';
    $lastName  = $nameParts[1] ?? '';

    // Detect edit
    $isEdit = $address && $address->exists;

    // Shipping service
    $shippingService = app(\App\Services\ShippingService::class);

    // Regions with pickup points and shipping options
    $regions = \App\Models\Region::with(['pickupPoints' => fn($q) => $q->active()])
        ->active()
        ->level('region')
        ->get()
        ->map(function ($region) use ($shippingService) {
            return [
                'id' => $region->id,
                'name' => $region->name,
                'pickup_points' => $region->pickupPoints->map(fn($pp) => [
                    'id' => $pp->id,
                    'name' => $pp->name,
                ])->values()->toArray(),
                'shipping_options' => $shippingService->getOptionsByRegion($region->id),
            ];
        });

    // Determine form action + method
    $action = $isEdit
        ? route('checkout.addresses.update', $address)
        : route('checkout.addresses.store');

    $method = $isEdit ? 'put' : 'post';

    // Prefill region if editing and pickup point exists
    $selectedRegionId = null;
    if ($isEdit && $address->pickup_point_id) {
        $pickupPoint = \App\Models\PickupPoint::with('region')->find($address->pickup_point_id);
        $selectedRegionId = $pickupPoint?->region?->id;
    }

    return Inertia::render('Frontend/Addresses/Form', [
        'address'                   => $address,
        'is_edit'                   => $isEdit,
        'customer'                  => $customer,
        'cart_subtotal'             => $totals['grand_total'] ?? 0,
        'regions'                   => $regions,
        'selected_region_id'        => $selectedRegionId,             // Prefill region
        'selected_pickup_point_id'  => $address?->pickup_point_id ?? null, // Prefill pickup point
        'is_default'                => $address?->is_default ?? false,    // Prefill default checkbox
        'first_name'                => $address?->first_name ?? $firstName,
        'last_name'                 => $address?->last_name ?? $lastName,
        'phone'                     => $address?->phone ?? '',
        'address_line_1'            => $address?->address_line_1 ?? '',
        'action'                    => $action,
        'method'                    => $method,
    ]);
}


public function store(CustomerAddressRequest $request)
{
    $customer = auth()->user()?->customer;
    if (!$customer) {
        return redirect()->back()->with('error', 'User details not found.');
    }

    $data = $request->validated();
    $data['is_default'] = boolval($request->input('is_default', false)); 

    $address = $customer->addresses()->create($data);

    if ($data['is_default']) {
        $address->makeDefault();
    }

    return redirect()->route('checkout.addresses.index')
        ->with('success', 'Address added successfully.');
}




public function update(CustomerAddressRequest $request, CustomerAddress $address)
{
    $data = $request->validated();

    $isDefault = boolval($request->input('is_default', false));

    unset($data['is_default']); // ← prevent conflicting save

    \Log::info('Updating customer address (before update)', [
        'address_id' => $address->id,
        'customer_id' => $address->customer_id,
        'submitted_data' => $data,
        'old_is_default' => $address->is_default,
    ]);

    $address->update($data);

    if ($isDefault) {
        $address->makeDefault();
    }

    \Log::info('Customer address updated (after update)', [
        'address_id' => $address->id,
        'customer_id' => $address->customer_id,
        'new_is_default' => $address->fresh()->is_default,
    ]);

    return redirect()->route('checkout.addresses.index')
        ->with('success', 'Address updated successfully.');
}




    public function destroy(CustomerAddress $address)
    {
        $customer = auth()->user()?->customer;
        if (!$customer) {
            return redirect()->back()->with('error', 'User details not found.');
        }

        if ($address->is_default && $customer->addresses()->count() > 1) {
            return back()->with('error', 'Cannot delete default address. Please set another address as default first.');
        }

        $address->delete();

        return redirect()->route('customer.addresses.index')
            ->with('success', 'Address deleted successfully.');
    }

    public function makeDefault(CustomerAddress $address)
    {
        $address->makeDefault();

        return back()->with('success', 'Default address updated successfully.');
    }
}
