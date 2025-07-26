<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentTypeController extends Controller
{
   
    public function index()
    {
        $documentTypes = DocumentType::orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Admin/DocumentTypes/Index', [
            'documentTypes' => $documentTypes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/DocumentTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'description' => 'nullable|string|max:1000',
        ]);

        DocumentType::create($validated);

        return redirect()->route('admin.document-types.index')
                         ->with('success', 'Document type created successfully.');
    }

    
    public function show(DocumentType $documentType)
    {
        return Inertia::render('Admin/DocumentTypes/Show', [
            'documentType' => $documentType,
        ]);
    }

   
    public function edit(DocumentType $documentType)
    {
        return Inertia::render('Admin/DocumentTypes/Edit', [
            'documentType' => $documentType,
        ]);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name,' . $documentType->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $documentType->update($validated);

        return redirect()->route('admin.document-types.index')
                         ->with('success', 'Document type updated successfully.');
    }

   
    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return redirect()->route('admin.document-types.index')
                         ->with('success', 'Document type deleted successfully.');
    }
}
