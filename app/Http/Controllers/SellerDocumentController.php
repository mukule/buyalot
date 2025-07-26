<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\SellerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SellerDocumentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $documents = DocumentType::all()->map(function ($type) use ($user) {
            $existing = $user->sellerDocuments()->where('document_type_id', $type->id)->first();

            return [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'submitted' => !!$existing,
                'status' => $existing?->status ?? 'pending',
                'file_url' => $existing?->file_path ? Storage::url($existing->file_path) : null,
                'rejection_reason' => $existing?->rejection_reason,
            ];
        });

        return Inertia::render('Seller/Documents/Index', [
            'documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $path = $request->file('document')->store('seller_documents', 'public');

        $sellerDoc = SellerDocument::updateOrCreate(
            [
                'user_id' => $user->id,
                'document_type_id' => $request->document_type_id,
            ],
            [
                'file_path' => $path,
                'status' => 'pending',
                'rejection_reason' => null,
            ]
        );

        return back()->with('success', 'Document submitted successfully.');
    }

    public function verify(Request $request, SellerDocument $sellerDocument)
    {
        $this->authorize('verify', $sellerDocument); // Optional: policy check

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $sellerDocument->update([
            'status' => $request->status,
            'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
        ]);

        return back()->with('success', 'Document status updated.');
    }
}
