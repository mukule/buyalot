<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DeliveryAccountSuspended;
use App\Mail\DeliveryApplicationApproved;
use App\Mail\DeliveryApplicationRejected;
use App\Models\DeliveryPersonApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryPersonController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $query = User::role('delivery')->with(['deliveryApplication']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'active') {
            $query->where('status', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', false);
        }

        $deliveryPersons = $query->latest()->paginate(15)->through(function (User $user) {
            $app = $user->deliveryApplication;
            return [
                'id' => $user->id,
                'application_id' => $app?->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'transport_type' => $app?->transport_type,
                'transport_registration_number' => $app?->transport_registration_number,
                'status' => $user->status ? 'active' : 'inactive',
                'suspended' => $user->isSuspended(),
                'created_at' => $user->created_at->toIso8601String(),
            ];
        });

        $pendingCount = DeliveryPersonApplication::query()
            ->where('status', DeliveryPersonApplication::STATUS_PENDING)
            ->count();

        return Inertia::render('Admin/DeliveryPersons/Index', [
            'deliveryPersons' => $deliveryPersons,
            'pendingCount' => $pendingCount,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function pendingApplications(): Response
    {
        $pendingApplications = DeliveryPersonApplication::query()
            ->where('status', DeliveryPersonApplication::STATUS_PENDING)
            ->with(['user'])
            ->latest()
            ->get()
            ->map(fn (DeliveryPersonApplication $app) => [
                'id' => $app->id,
                'name' => $app->name,
                'email' => $app->email,
                'phone' => $app->phone,
                'transport_type' => $app->transport_type,
                'transport_registration_number' => $app->transport_registration_number,
                'created_at' => $app->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/DeliveryPersons/PendingApplications', [
            'pendingApplications' => $pendingApplications,
        ]);
    }

    public function show(DeliveryPersonApplication $deliveryPerson): Response
    {
        $deliveryPerson->load(['user', 'reviewedBy']);

        $idCopyUrl = null;
        $kraCopyUrl = null;
        if ($deliveryPerson->id_copy_path && \Storage::disk('private')->exists($deliveryPerson->id_copy_path)) {
            $idCopyUrl = route('admin.delivery-persons.document', [
                'deliveryPerson' => $deliveryPerson->id,
                'type' => 'id',
            ]);
        }
        if ($deliveryPerson->kra_copy_path && \Storage::disk('private')->exists($deliveryPerson->kra_copy_path)) {
            $kraCopyUrl = route('admin.delivery-persons.document', [
                'deliveryPerson' => $deliveryPerson->id,
                'type' => 'kra',
            ]);
        }

        return Inertia::render('Admin/DeliveryPersons/Show', [
            'application' => [
                'id' => $deliveryPerson->id,
                'name' => $deliveryPerson->name,
                'email' => $deliveryPerson->email,
                'phone' => $deliveryPerson->phone,
                'id_number' => $deliveryPerson->id_number,
                'kra_pin' => $deliveryPerson->kra_pin,
                'address' => $deliveryPerson->address,
                'transport_type' => $deliveryPerson->transport_type,
                'transport_registration_number' => $deliveryPerson->transport_registration_number,
                'transport_details' => $deliveryPerson->transport_details,
                'status' => $deliveryPerson->status,
                'rejection_reason' => $deliveryPerson->rejection_reason,
                'reviewed_at' => $deliveryPerson->reviewed_at?->toIso8601String(),
                'created_at' => $deliveryPerson->created_at->toIso8601String(),
                'reviewed_by' => $deliveryPerson->reviewedBy ? [
                    'id' => $deliveryPerson->reviewedBy->id,
                    'name' => $deliveryPerson->reviewedBy->name,
                ] : null,
                'id_copy_url' => $idCopyUrl,
                'kra_copy_url' => $kraCopyUrl,
                'suspended_at' => $deliveryPerson->user?->suspended_at?->toIso8601String(),
                'suspension_reason' => $deliveryPerson->user?->suspension_reason,
            ],
        ]);
    }

    public function suspend(Request $request, DeliveryPersonApplication $deliveryPerson): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'notify' => ['nullable', 'boolean'],
        ]);

        $user = $deliveryPerson->user;
        if (! $user) {
            return redirect()->back()->with('error', 'No user linked to this application.');
        }
        if ($deliveryPerson->status !== DeliveryPersonApplication::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'Only approved delivery persons can be suspended.');
        }
        if ($user->isSuspended()) {
            return redirect()->back()->with('error', 'This account is already suspended.');
        }

        $user->update([
            'status' => false,
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        if ($request->boolean('notify')) {
            try {
                Mail::to($user->email)->send(new DeliveryAccountSuspended($user, $request->reason));
            } catch (\Throwable $e) {
                Log::error('Delivery suspension email failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Delivery person suspended.' . ($request->boolean('notify') ? ' They have been notified by email.' : ''));
    }

    public function unsuspend(DeliveryPersonApplication $deliveryPerson): RedirectResponse
    {
        $user = $deliveryPerson->user;
        if (! $user) {
            return redirect()->back()->with('error', 'No user linked to this application.');
        }
        if (! $user->isSuspended()) {
            return redirect()->back()->with('error', 'This account is not suspended.');
        }

        $user->update([
            'status' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Delivery person unsuspended. They can log in and be assigned again.');
    }

    public function document(Request $request, DeliveryPersonApplication $deliveryPerson, string $type): mixed
    {
        if (! in_array($type, ['id', 'kra'], true)) {
            abort(404);
        }
        $path = $type === 'id' ? $deliveryPerson->id_copy_path : $deliveryPerson->kra_copy_path;
        if (! $path || ! \Storage::disk('private')->exists($path)) {
            abort(404);
        }
        return \Storage::disk('private')->response($path);
    }

    public function approve(DeliveryPersonApplication $deliveryPerson): RedirectResponse
    {
        if (! $deliveryPerson->isPending()) {
            return redirect()->back()->with('error', 'This application is not pending.');
        }

        $user = $deliveryPerson->user;
        if (! $user) {
            return redirect()->back()->with('error', 'No user linked to this application.');
        }

        DB::beginTransaction();
        try {
            $deliveryPerson->update([
                'status' => DeliveryPersonApplication::STATUS_APPROVED,
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $user->update(['status' => true]);
            $user->assignRole('delivery');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delivery application approval failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to approve. Please try again.');
        }

        $loginUrl = route('delivery.login');
        try {
            Mail::to($user->email)->send(new DeliveryApplicationApproved($user, $loginUrl));
        } catch (\Throwable $e) {
            Log::error('Delivery approval email failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Delivery person approved and notified.');
    }

    public function reject(Request $request, DeliveryPersonApplication $deliveryPerson): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $deliveryPerson->isPending()) {
            return redirect()->back()->with('error', 'This application is not pending.');
        }

        $deliveryPerson->update([
            'status' => DeliveryPersonApplication::STATUS_REJECTED,
            'rejection_reason' => $request->reason,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        try {
            Mail::to($deliveryPerson->email)->send(new DeliveryApplicationRejected($deliveryPerson, $request->reason));
        } catch (\Throwable $e) {
            Log::error('Delivery rejection email failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Application rejected and applicant notified.');
    }
}
