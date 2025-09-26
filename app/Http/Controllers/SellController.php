<?php

namespace App\Http\Controllers;

use App\Models\SellerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\SellerApplicationImage;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellerApplicationReceived;
use App\Mail\NewSellerApplicationAdminAlert;


class SellController extends Controller
{
    // Landing Page
   public function index()
{
    \Log::info('SellController@index was called');

    return Inertia::render('/Home', [
        'title' => 'Sell your products online',
    ]);
}


    // Show Application Form
    public function applyForm()
    {
        return Inertia::render('Sell/Apply', [
            'title' => 'Buyalot Marketplace Application Form',
            'prefilled' => session('seller_application', []),
            'savedStep' => session('seller_application_step', 1),
        ]);
    }


public function saveProgress(Request $request)
{
    $data = array_filter(
        $request->except(['current_step']),
        fn ($value) => !is_null($value)
    );

    session([
        'seller_application' => array_merge(session('seller_application', []), $data),
        'seller_application_step' => $request->input('current_step', 1),
    ]);

    return response()->json(['status' => 'saved']);
}




public function clearProgress(Request $request)
{
    Log::info('Clearing session progress', $request->all());

    session()->forget(['seller_application', 'seller_application_step']);

    return response()->json(['status' => 'cleared'], 200);
}


    // Upload individual images and return path
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $path = $request->file('image')->store('seller_images', 'public');

        return response()->json(['path' => Storage::url($path)]);
    }




public function submit(Request $request)
{
    Log::info('SellController@submit called');

    $sessionData = session('seller_application', []);
    Log::debug('Session data before validation', $sessionData);

    if (empty($sessionData)) {
        Log::warning('Submit failed: Session data is empty');
        return redirect()->back()->withErrors([
            'message' => 'Your session has expired or is empty. Please restart your application.',
        ]);
    }

    try {
        $validated = validator($sessionData, [
            'business_type' => 'required|string',
            'agreed_to_privacy' => 'required|boolean',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string',
            'identification_type' => 'nullable|string',
            'id_number' => 'nullable|string',
            'passport_number' => 'nullable|string',
            'product_categories' => 'nullable|array',
            'product_categories.*' => 'string',
            'primary_product_category' => 'nullable|string',
            'description' => 'nullable|string',
            'owner_first_name' => 'nullable|string',
            'owner_last_name' => 'nullable|string',
            'owner_email' => 'nullable|email',
            'owner_phone' => 'nullable|string',
            'vat_registered' => 'nullable|string',
            'vat_number' => 'nullable|string',
            'company_legal_name' => 'nullable|string',
            'ke_business_reg_number' => 'nullable|string',
            'non_ke_business_reg_number' => 'nullable|string',
            'ke_id_number' => 'nullable|string',
            'passport_number_sp' => 'nullable|string',
            'country' => 'nullable|string',
            'nationality' => 'nullable|string',
            'monthly_revenue' => 'nullable|string',
            'owns_physical_store' => 'nullable|string',
            'retail_store_count' => 'nullable|numeric',
            'is_supplier_to_retailers' => 'nullable|string',
            'operates_other_marketplaces' => 'nullable|string',
            'marketplace_details' => 'nullable|string',
            'supplier_retail_count' => 'nullable|numeric',
            'product_count' => 'nullable|numeric',
            'stock_handling' => 'nullable|string',
            'product_website' => 'nullable|string',
            'product_origin' => 'nullable|string',
            'owned_brands' => 'nullable|string',
            'licensed_brands' => 'nullable|string',
            'product_branding' => 'nullable|string',
            'social_media' => 'nullable|string',
            'business_summary' => 'nullable|string',
            'product_images' => 'nullable|array',
            'product_images.*' => 'string',
            'discovery_source' => 'nullable|string',
            'referrer_email' => 'nullable|email',
            'share_with_distributors' => 'nullable|string',
        ])->validate();

        foreach (['retail_store_count', 'supplier_retail_count', 'product_count'] as $field) {
            if (isset($validated[$field]) && is_numeric($validated[$field])) {
                $validated[$field] = (int) $validated[$field];
            }
        }

        $application = SellerApplication::create(
            collect($validated)->except('product_images')->toArray()
        );

        $productImages = collect($validated['product_images'] ?? [])
            ->filter(fn($path) => str_starts_with($path, '/storage/seller_images/'));

        foreach ($productImages as $path) {
            SellerApplicationImage::create([
                'seller_application_id' => $application->id,
                'path' => $path,
                'original_name' => basename($path),
            ]);
        }

            try {

            Mail::to($application->contact_email)
                ->send(new SellerApplicationReceived($application));


            $adminEmails = explode(',', config('mail.admin_address'));
            Mail::to($adminEmails)
                ->send(new NewSellerApplicationAdminAlert($application));

            Log::info("Emails sent for application ID {$application->id}");
        } catch (\Throwable $e) {
            Log::error("Email sending failed for application ID {$application->id}", [
                'error' => $e->getMessage()
            ]);

        }


        return redirect()->route('sell.index')->with('success', 'Your application was submitted successfully!');
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed', $e->errors());
        return redirect()->back()->withErrors($e->errors());
    } catch (\Throwable $e) {
        Log::error('Application submission failed', ['message' => $e->getMessage()]);
        return redirect()->back()->withErrors([
            'message' => 'An unexpected error occurred. Please try again or contact support.',
        ]);
    }
}



}
