<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\SellerApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Models\SellerDocument;
use Illuminate\Validation\Rule;
use App\Mail\SellerDocumentReviewNotification;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellerVerificationStatusChanged;

class SellerVerificationController extends Controller
{
   
   
    public function show(SellerApplication $sellerApplication)
{
    if (!$sellerApplication->isApproved()) {
        return redirect()
            ->back()
            ->with('info', 'Please approve the applicant first before proceeding to verification.');
    }

    $application = $sellerApplication->load('user');

    $documentTypes = DocumentType::all();

    $sellerDocuments = $application->user
        ->sellerDocuments()
        ->get()
        ->keyBy('document_type_id');

    return Inertia::render('Admin/SellerApplications/Verification', [
        'application' => $application,
        'documentTypes' => $documentTypes,
        'sellerDocuments' => $sellerDocuments,
    ]);
}



public function review(Request $request, SellerDocument $sellerDocument)
{
    $validated = $request->validate([
        'status' => ['required', Rule::in(['approved', 'rejected'])],
        'rejection_reason' => ['nullable', 'string', 'max:1000'],
    ]);

    if ($validated['status'] === 'rejected' && empty($validated['rejection_reason'])) {
        return back()->withErrors(['rejection_reason' => 'Please provide a reason for rejection.']);
    }

    $sellerDocument->status = $validated['status'];
    $sellerDocument->rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
    $sellerDocument->save();

    Log::info("Seller document ID {$sellerDocument->id} reviewed with status {$validated['status']}");

    // Send notification email to applicant
    try {
        Mail::to($sellerDocument->user->email)
            ->send(new SellerDocumentReviewNotification($sellerDocument));
    } catch (\Throwable $e) {
        Log::error("Failed to send document review notification email for SellerDocument ID {$sellerDocument->id}: " . $e->getMessage());
    }

    return redirect()->back()->with('success', 'Document review updated successfully.');
}


public function verify(Request $request, SellerApplication $sellerApplication)
{
    $validated = $request->validate([
        'verified' => ['required', 'boolean'],
    ]);

    $sellerApplication->verified = $validated['verified'];
    $sellerApplication->save();

    Log::info("Seller application ID {$sellerApplication->id} verification updated to: " . ($validated['verified'] ? 'verified' : 'unverified'));

    // Notify the applicant
    try {
        Mail::to($sellerApplication->contact_email)
            ->send(new SellerVerificationStatusChanged($sellerApplication));
    } catch (\Throwable $e) {
        Log::error("Failed to send verification status email for SellerApplication ID {$sellerApplication->id}: " . $e->getMessage());
    }

    return redirect()->back()->with('success', 'Seller verification status updated.');
}



}
