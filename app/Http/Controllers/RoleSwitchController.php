<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitchController extends Controller
{
    public function switchRole(Request $request)
    {
        $user = Auth::user();

        // Safety check: Does the user even have a secondary role?
        if (!$user->secondary_role) {
            return back()->with('error', 'You do not have an alternative account to switch to.');
        }
        // Determine the new active role
        // If current session is 'seller', switch to 'customer', and vice versa.
        $currentActive = session('active_role', $user->user_type);

        $newRole = ($currentActive === $user->user_type) ? $user->secondary_role : $user->user_type;

        // Update the session
        session(['active_role' => $newRole]);

        // Redirect to the appropriate dashboard based on the NEW role
//        return $newRole === 'seller'
//            ? redirect()->route('admin.dashboard')
//            : redirect()->route('customers.dashboard');

        return match($newRole) {
            'admin', 'seller' => redirect()->route('admin.dashboard'),
            default  => redirect()->route('customers.dashboard'),
        };
    }
}
