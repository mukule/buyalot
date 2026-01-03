<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;

class PosCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::active();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_code', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($qu) use ($request) {
                      $qu->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%");
                  });
            });
        }

        $customers = $query->with('user')->limit(10)->get();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        // For POS, we might want a quick way to create a customer
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:255|unique:users,email',
        ]);

        // Logic to create User and Customer...
        // Simplified for now
        $user = \App\Models\User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email ?? ($request->phone . '@pos.customer'),
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(10)),
            'user_type' => 'customer',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'status' => 1,
        ]);

        return response()->json($customer->load('user'));
    }
}
