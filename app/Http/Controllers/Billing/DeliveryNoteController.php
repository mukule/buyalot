<?php

namespace App\Http\Controllers\Billing;

use App\Domains\Invoicing\DTOs\CreateDeliveryNoteDTO;
use App\Domains\Invoicing\Services\DeliveryNoteService;
use App\Http\Controllers\Controller;
use App\Models\Billing\DeliveryNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function __construct(
        private DeliveryNoteService $deliveryNoteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryNote::query()
            ->when($request->invoice_id, fn ($q) => $q->where('invoice_id', $request->integer('invoice_id')))
            ->with(['invoice', 'items'])
            ->orderByDesc('id');

        return response()->json($query->paginate(20));
    }

    public function show(DeliveryNote $deliveryNote): JsonResponse
    {
        $deliveryNote->load(['invoice', 'items.invoiceItem']);
        return response()->json($deliveryNote);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'delivery_address' => 'nullable|array',
            'delivery_address.street' => 'nullable|string',
            'delivery_address.city' => 'nullable|string',
            'delivery_address.region' => 'nullable|string',
            'delivery_address.postal_code' => 'nullable|string',
            'delivery_address.country' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'nullable|exists:invoice_items,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $dto = new CreateDeliveryNoteDTO(
            invoiceId: $data['invoice_id'],
            deliveryAddress: $data['delivery_address'] ?? null,
            items: $data['items'],
            notes: $data['notes'] ?? null,
        );

        try {
            $deliveryNote = $this->deliveryNoteService->create($dto);
            return response()->json($deliveryNote, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }
    }

    public function updateStatus(Request $request, DeliveryNote $deliveryNote): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,dispatched,delivered',
        ]);

        try {
            $deliveryNote = $this->deliveryNoteService->updateStatus($deliveryNote, $data['status']);
            return response()->json($deliveryNote);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
