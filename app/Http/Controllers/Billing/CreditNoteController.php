<?php

namespace App\Http\Controllers\Billing;

use App\Domains\Invoicing\DTOs\CreateCreditNoteDTO;
use App\Domains\Invoicing\Services\CreditNoteService;
use App\Http\Controllers\Controller;
use App\Models\Billing\CreditNote;
use App\Models\Billing\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function __construct(
        private CreditNoteService $creditNoteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CreditNote::query()
            ->when($request->invoice_id, fn ($q) => $q->where('invoice_id', $request->integer('invoice_id')))
            ->with('invoice')
            ->orderByDesc('id');

        return response()->json($query->paginate(20));
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        $creditNote->load('invoice');
        return response()->json($creditNote);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'reason' => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $dto = new CreateCreditNoteDTO(
            invoiceId: $data['invoice_id'],
            amountMinor: $data['amount_minor'],
            currency: strtoupper($data['currency']),
            reason: $data['reason'] ?? null,
        );

        try {
            $creditNote = $this->creditNoteService->create($dto);
            return response()->json($creditNote, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }
    }
}
