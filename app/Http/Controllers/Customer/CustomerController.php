<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    public function index(Request $request)
    {
        $customers = Customer::query()
            ->with(['defaultAddress', 'orders'])
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_type, fn($q) => $q->byType($request->customer_type))
            ->latest()
            ->paginate(20);

        return Inertia::render('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'addresses', 'orders.items', 'loyaltyPoints',
            'preferences', 'supportTickets', 'wishlistItems'
        ]);

        $stats = $this->customerService->getCustomerStats($customer);

        return Inertia::render('customers.show', compact('customer', 'stats'));
    }

    public function create()
    {
        return Inertia::render('customers.create');
    }

    public function store(CustomerRequest $request)
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $this->customerService->deleteCustomer($customer);

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function dashboard(Customer $customer)
    {
        $stats = $this->customerService->getDashboardStats($customer);
        return Inertia::render('customers.dashboard', compact('customer', 'stats'));
    }
}
