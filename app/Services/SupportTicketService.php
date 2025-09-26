<?php

namespace App\Services;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerSupportTicket;

class SupportTicketService
{
    public function createTicket(Customer $customer, array $data): CustomerSupportTicket
    {
        return CustomerSupportTicket::create(array_merge($data, [
            'customer_id' => $customer->id,
            'ticket_number' => $this->generateTicketNumber(),
            'status' => 'open',
        ]));
    }

    public function assignTicket(CustomerSupportTicket $ticket, int $agentId): void
    {
        $ticket->update([
            'assigned_to' => $agentId,
            'status' => 'in_progress'
        ]);
    }

    public function escalateTicket(CustomerSupportTicket $ticket, string $reason): void
    {
        $ticket->update([
            'priority' => 'high',
            'internal_notes' => ($ticket->internal_notes ?? '') . "\n\nESCALATED: " . $reason,
        ]);
    }

    private function generateTicketNumber(): string
    {
        $prefix = 'TKT-' . now()->format('Ymd');
        $lastTicket = CustomerSupportTicket::where('ticket_number', 'like', $prefix . '%')
            ->orderBy('ticket_number', 'desc')
            ->first();

        if (!$lastTicket) {
            return $prefix . '-001';
        }

        $lastNumber = (int) substr($lastTicket->ticket_number, -3);
        return $prefix . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
