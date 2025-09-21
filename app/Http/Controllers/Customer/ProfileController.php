<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customer');
    }

    public function show()
    {
        $customer = Auth::guard('customer')->user();
        $customer->load(['businessInfo', 'marketingPreferences', 'addresses']);

        return view('customers.profile', compact('customer'));
    }

    public function edit()
    {
        $customer = Auth::guard('customer')->user();
        $customer->load(['businessInfo', 'marketingPreferences']);

        return view('customer.edit_profile', compact('customer'));
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
        ]);

        $customer->update($validated);

        return redirect()->route('customer.profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $customer = Auth::guard('customer')->user();

        if (!Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $customer->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('customer.profile.show')
            ->with('success', 'Password updated successfully!');
    }
}
