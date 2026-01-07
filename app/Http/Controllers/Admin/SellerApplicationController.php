<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SellerApprovedMail;
use App\Mail\SellerRejectedMail;
use App\Models\Seller\SellerUser;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use phpDocumentor\Reflection\Types\Boolean;
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
        $applicationsQuery->where(function ($query) use ($search) {
            $query->where('company_legal_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%")
                  ->orWhere('owner_first_name', 'like', "%{$search}%")
                  ->orWhere('owner_last_name', 'like', "%{$search}%")
                  ->orWhere('primary_product_category', 'like', "%{$search}%")
                  ->orWhere('business_type', 'like', "%{$search}%");
        });
    }

    $applications = $applicationsQuery
        ->latest()
        ->paginate(20)
        ->through(function ($application) {
            return [
                'id' => $application->id,
                'hashid' => Hashids::encode($application->id),
                'owner_first_name' => $application->owner_first_name,
                'owner_last_name' => $application->owner_last_name,
                'company_legal_name' => $application->company_legal_name,
                'business_type' => $application->business_type,
                'primary_product_category' => $application->primary_product_category,
                'email' => $application->contact_email,
                'phone' => $application->contact_phone,
                'status' => $application->status,
                'is_active' => (int) $application->status === SellerApplication::STATUS_APPROVED,
                'created_at' => optional($application->created_at)->toDateString(),
            ];
        });

    return Inertia::render('Admin/SellerApplications/Index', [
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
    DB::beginTransaction();
        $had_account=false;
         $password = Str::random(8);
     try {
         if (User::where('email', $sellerApplication->contact_email)->exists()) {
             //update secondary email
             $user = User::where('email', $sellerApplication->contact_email)->first();

             // 2. Call update on that specific instance
             $user->update(["secondary_role" => "seller"]);
             $had_account=true;
         }else{
             $user = User::create([
                 'name' => $sellerApplication->first_name . ' ' . $sellerApplication->last_name,
                 'email' => $sellerApplication->contact_email,
                 'password' => bcrypt($password),
                 'seller_application_id' => $sellerApplication->id,
                 'phone' => $sellerApplication->contact_phone,
                 'email_verified_at' => now(),
                 'user_type' => 'seller'
             ]);
         }

        $user->assignRole('seller');
        SellerUser::create([
            'user_id' => $user->id,
            'seller_id' => $sellerApplication->id,
            'role' => 'seller_admin',
        ]);

        $sellerApplication->update([
            'status' => SellerApplication::STATUS_APPROVED,
            'status_reason' => 'Approved',
        ]);

        $loginUrl = route('login');

        if (!$had_account) {
            $password = "User the initial account password";
        }
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
