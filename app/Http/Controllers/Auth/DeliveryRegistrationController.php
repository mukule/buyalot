<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPersonApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/DeliveryRegister', [
            'transportTypes' => DeliveryPersonApplication::TRANSPORT_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],

            'id_number' => ['required', 'string', 'max:50'],
            'id_copy' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'kra_pin' => ['required', 'string', 'max:50'],
            'kra_copy' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],

            'address' => ['required', 'string', 'max:1000'],
            'transport_type' => ['required', 'string', 'in:' . implode(',', DeliveryPersonApplication::TRANSPORT_TYPES)],
            'transport_registration_number' => ['nullable', 'string', 'max:100'],
            'transport_details' => ['nullable', 'string', 'max:500'],
        ]);

        $email = strtolower($validated['email']);

        $idCopyPath = $request->file('id_copy')->store('delivery-kyc/id', 'private');
        $kraCopyPath = $request->file('kra_copy')->store('delivery-kyc/kra', 'private');

        DB::beginTransaction();
        try {
            // Create application first (no user_id yet)
            $application = DeliveryPersonApplication::create([
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'],
                'id_number' => $validated['id_number'],
                'id_copy_path' => $idCopyPath,
                'kra_pin' => $validated['kra_pin'],
                'kra_copy_path' => $kraCopyPath,
                'address' => $validated['address'],
                'transport_type' => $validated['transport_type'],
                'transport_registration_number' => $validated['transport_registration_number'] ?? null,
                'transport_details' => $validated['transport_details'] ? ['notes' => $validated['transport_details']] : null,
                'status' => DeliveryPersonApplication::STATUS_PENDING,
            ]);

            // Create user with delivery_application_id so User::created skips generic welcome
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'status' => false,
                'delivery_application_id' => $application->id,
            ]);

            $application->update(['user_id' => $user->id]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('delivery.login')
            ->with('status', 'Registration submitted. You will be able to login after admin approval.');
    }
}
