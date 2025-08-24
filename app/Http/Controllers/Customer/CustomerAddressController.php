<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;

class CustomerAddressController extends Controller
{
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
