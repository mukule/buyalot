<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\SellerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\SellerDocumentsSubmittedAdminAlert;

class SellerAccountController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        $application = $user->sellerApplication;

        if (!$application) {
            return redirect()->back()->with('error', 'Resource not found.');
        }

        $documentTypes = DocumentType::all();
        $sellerDocuments = $user->sellerDocuments->keyBy('document_type_id');

        return Inertia::render('Seller/Account', [
            'application' => $application,
            'documentTypes' => $documentTypes,
            'sellerDocuments' => $sellerDocuments,
        ]);
    }

    
   
public function submitDocument(Request $request)
{
    $request->validate([
        'document_type_id' => 'required|exists:document_types,id',
        'file' => 'required|file|mimes:pdf|max:2048',
    ]);

    $user = Auth::user();
    $documentTypeId = $request->input('document_type_id');

    $existingDocument = SellerDocument::where('user_id', $user->id)
        ->where('document_type_id', $documentTypeId)
        ->first();

    // Delete old file if it exists
    if ($existingDocument && $existingDocument->file_path) {
        $oldFile = str_replace('/storage/', '', $existingDocument->file_path);
        Storage::disk('public')->delete($oldFile);
    }

    // Store new file
    $filePath = $request->file('file')->store('seller_documents', 'public');

    // Save or update document record
    $document = SellerDocument::updateOrCreate(
        [
            'user_id' => $user->id,
            'document_type_id' => $documentTypeId,
        ],
        [
            'file_path' => Storage::url($filePath),
            'status' => 'pending',
            'rejection_reason' => null,
        ]
    );

    // Get the seller application
    $application = $user->sellerApplication;

    // Get the document type (for name)
    $documentType = DocumentType::find($documentTypeId);

    // Try sending email, log if fails
    try {
        // Parse and validate admin emails from config
        $adminEmails = array_filter(array_map('trim', explode(',', config('mail.admin_address'))), function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        if (!empty($adminEmails)) {
            Mail::to($adminEmails)
                ->send(new SellerDocumentsSubmittedAdminAlert($application, collect([$document])));
        } else {
            Log::error('No valid admin emails to send Seller Document Submission Email.', [
                'user_id' => $user->id,
                'document_type_id' => $documentTypeId,
            ]);
        }
    } catch (\Throwable $e) {
        Log::error('Failed to send Seller Document Submission Email: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'document_type_id' => $documentTypeId,
        ]);
    }

    return redirect()->back()->with('success', 'Document submitted successfully.');
}


}
