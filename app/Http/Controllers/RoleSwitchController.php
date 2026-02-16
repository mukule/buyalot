<?php

namespace App\Http\Controllers;

use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitchController extends Controller
{
    /**
     * Switch the current session to another portal role (customer, seller, or distributor).
     * Supports multiple roles: user can switch to any of their portal roles.
     */
    public function switchRole(Request $request)
    {
        $user = Auth::user();
        $portalRoles = $user->getPortalRoles();

        if (count($portalRoles) < 2) {
            return back()->with('error', 'You do not have an alternative account to switch to.');
        }

        $currentActive = session('active_role', $user->getRawOriginal('user_type') ?? $user->user_type);
        $currentActive = $currentActive === 'vendor' ? 'seller' : $currentActive;

        // Optional: switch to a specific role from request (for dropdown with 3+ roles)
        $targetRole = $request->input('role');
        if ($targetRole !== null && $targetRole !== '') {
            $targetRole = $targetRole === 'vendor' ? 'seller' : $targetRole;
            if (! in_array($targetRole, $portalRoles, true)) {
                return back()->with('error', 'You do not have access to that account.');
            }
            $newRole = $targetRole;
        } else {
            // Legacy: cycle between two roles (current vs first other)
            $newRole = ($currentActive === $portalRoles[0])
                ? ($portalRoles[1] ?? $portalRoles[0])
                : $portalRoles[0];
        }

        session(['active_role' => $newRole]);

        if ($newRole === 'customer') {
            $customer = Customer::where('user_id', $user->id)->first();
            if ($customer) {
                session(['customer_id' => $customer->id]);
            }
        }

        return $this->redirectForRole($newRole, $user);
    }

    private function redirectForRole(string $role, $user): \Illuminate\Http\RedirectResponse
    {
        return match ($role) {
            'admin', 'seller' => redirect()->route('admin.dashboard'),
            'distributor' => redirect()->route('distributor.dashboard'),
            'customer' => $this->redirectToCustomerDashboard($user),
            default => redirect()->route('customers.dashboard'),
        };
    }

    private function redirectToCustomerDashboard($user): \Illuminate\Http\RedirectResponse
    {
        $customer = Customer::where('user_id', $user->id)->first();
        if ($customer) {
            return redirect()->route('customers.dashboard', ['customer' => $customer->id]);
        }
        return redirect()->route('customers.dashboard');
    }
}
