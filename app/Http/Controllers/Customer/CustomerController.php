<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer\Customer;
use App\Services\CustomerService;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

        return Inertia::render('customers/index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'addresses', 'orders.items', 'loyaltyPoints',
            'preferences', 'supportTickets', 'wishlistItems'
        ]);

        $stats = $this->customerService->getCustomerStats($customer);

        return Inertia::render('customers/show', compact('customer', 'stats'));
    }

    public function create()
    {
        return Inertia::render('customers/create');
    }

    public function store(CustomerRequest $request)
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('customers/edit', compact('customer'));
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
        return Inertia::render('customers/dashboard', compact('customer', 'stats'));
    }

    public function profile()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('login')->with('error', 'Please log in to access your profile.');
        }

        return Inertia::render('Customer/Profile', [
            'customer' => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'date_of_birth' => $customer->date_of_birth?->format('Y-m-d'),
                'gender' => $customer->gender,
                'avatar' => $customer->avatar ? Storage::url($customer->avatar) : null,
                'customer_type' => $customer->customer_type,
                'status' => $customer->status,
                'provider' => $customer->provider,
                'email_verified_at' => $customer->email_verified_at,
                'created_at' => $customer->created_at->format('M d, Y'),
                'has_password' => !empty($customer->password),
            ]
        ]);
    }

    /**
     * Display welcome page for customers
     */
    public function welcome(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        // If no authenticated customer, use a sample customer for email preview
        if (!$customer) {
            $customer = (object) [
                'first_name' => $request->get('first_name', 'John'),
                'last_name' => $request->get('last_name', 'Doe'),
                'email' => $request->get('email', 'john.doe@example.com'),
                'customer_type' => 'individual',
                'provider' => null,
                'avatar' => null,
                'created_at' => now(),
            ];
        }

        return Inertia::render('Customer/Welcome', [
            'customer' => [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'avatar' => $customer->avatar ? Storage::url($customer->avatar) : null,
                'customer_type' => $customer->customer_type ?? 'individual',
                'provider' => $customer->provider,
                'created_at' => $customer->created_at->format('F d, Y'),
            ]
        ]);
    }

    /**
     * Update the customer's profile
     */
    public function updateProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($customer->avatar && Storage::disk('public')->exists($customer->avatar)) {
                Storage::disk('public')->delete($customer->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $customer->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }
    public function updatePassword(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'current_password' => 'required_if:has_password,true',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($customer->password && !Hash::check($validated['current_password'], $customer->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $customer->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }
    public function deleteAccount(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('login');
        }

        $request->validate([
            'confirmation' => 'required|in:DELETE',
            'password' => 'required_if:has_password,true',
        ]);

        // Verify password if customer has one
        if ($customer->password && !Hash::check($request->password, $customer->password)) {
            return back()->withErrors(['password' => 'Password is incorrect.']);
        }

        // Delete avatar if exists
        if ($customer->avatar && Storage::disk('public')->exists($customer->avatar)) {
            Storage::disk('public')->delete($customer->avatar);
        }

        // Log out and delete account
        Auth::guard('customer')->logout();
        $customer->delete();

        return redirect()->route('home')->with('success', 'Your account has been deleted successfully.');
    }
    }
