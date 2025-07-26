<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SellerApprovedMail;
use App\Mail\SellerRejectedMail;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Route; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;




class SellerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $applicationsQuery = SellerApplication::query();

        if ($search) {
            $applicationsQuery->where(function($query) use ($search) {
                $query->where('business_type', 'like', "%{$search}%")
                    ->orWhere('company_legal_name', 'like', "%{$search}%")
                    ->orWhere('primary_product_category', 'like', "%{$search}%");
            });
        }

        $applications = $applicationsQuery->latest()->paginate(20);

        $applications->getCollection()->transform(function ($application) {
            $application->hashid = Hashids::encode($application->id);
            return $application;
        });

        return inertia('Admin/SellerApplications/Index', [
            'applications' => $applications,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(SellerApplication $sellerApplication)
    {
        return inertia('Admin/SellerApplications/Show', [
            'application' => $sellerApplication->load('images')
        ]);
    }

    public function destroy(SellerApplication $sellerApplication)
    {
        $sellerApplication->images()->delete();
        $sellerApplication->delete();

        return redirect()->back()->with('success', 'Application deleted successfully.');
    }

   
    public function approve(SellerApplication $sellerApplication)
{
    if (User::where('email', $sellerApplication->contact_email)->exists()) {
        return redirect()->back()->with('error', 'A user with this email already exists.');
    }

    DB::beginTransaction();

    try {
        $password = Str::random(8);

        $user = User::create([
            'name' => $sellerApplication->first_name . ' ' . $sellerApplication->last_name,
            'email' => $sellerApplication->contact_email,
            'password' => Hash::make($password),
            'seller_application_id' => $sellerApplication->id, 
        ]);

        $user->assignRole('seller');

        $sellerApplication->update([
            'status' => SellerApplication::STATUS_APPROVED,
            'status_reason' => 'Approved',
        ]);

        $loginUrl = route('login');

        try {
            Mail::to($user->email)->send(new SellerApprovedMail($user, $password, $loginUrl));
        } catch (\Exception $e) {
            Log::error('Mail sending failed: ' . $e->getMessage());
            throw $e;
        }

        DB::commit();

        return redirect()->back()->with('success', 'Seller approved, user account created and email sent.');
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Seller approval failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        return redirect()->back()->withErrors([
            'email' => 'Failed to approve seller. Please try again later.',
        ]);
    }
}


    public function reject(Request $request, SellerApplication $sellerApplication)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Send rejection email
        Mail::to($sellerApplication->contact_email)->send(
            new SellerRejectedMail($sellerApplication, $request->reason)
        );

        // Delete images and application
        $sellerApplication->images()->delete();
        $sellerApplication->delete();

        return redirect()->route('admin.applications.index')->with('success', 'Application rejected and deleted.');
    }
}
