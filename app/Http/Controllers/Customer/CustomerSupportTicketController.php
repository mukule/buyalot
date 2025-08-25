<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerSupportTicket;
use Illuminate\Http\Request;

class CustomerSupportTicketController extends Controller
{
    public function __construct(
        private SupportTicketService $supportService
    ) {}

    public function index(Customer $customer)
    {
        $tickets = $customer->supportTickets()
            ->with('assignedAgent')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_tickets' => $customer->supportTickets()->count(),
            'open_tickets' => $customer->supportTickets()->open()->count(),
            'resolved_tickets' => $customer->supportTickets()->where('status', 'resolved')->count(),
            'average_rating' => $customer->supportTickets()->whereNotNull('customer_satisfaction_rating')->avg('customer_satisfaction_rating'),
        ];

        return view('customers.support-tickets.index', compact('customer', 'tickets', 'stats'));
    }

    public function create(Customer $customer)
    {
        return view('customers.support-tickets.create', compact('customer'));
    }

    public function store(SupportTicketRequest $request, Customer $customer)
    {
        $ticket = $this->supportService->createTicket($customer, $request->validated());

        return redirect()->route('customers.support-tickets.show', [$customer, $ticket])
            ->with('success', 'Support ticket created successfully.');
    }

    public function show(Customer $customer, CustomerSupportTicket $ticket)
    {
        $ticket->load(['responses.user', 'assignedAgent']);
        return view('customers.support-tickets.show', compact('customer', 'ticket'));
    }

    public function update(SupportTicketRequest $request, Customer $customer, CustomerSupportTicket $ticket)
    {
        $ticket->update($request->validated());

        return back()->with('success', 'Support ticket updated successfully.');
    }

    public function assign(Request $request, Customer $customer, CustomerSupportTicket $ticket)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $ticket->update(['assigned_to' => $request->assigned_to]);

        return back()->with('success', 'Ticket assigned successfully.');
    }

    public function resolve(Request $request, Customer $customer, CustomerSupportTicket $ticket)
    {
        $request->validate([
            'resolution' => 'required|string'
        ]);

        $ticket->markAsResolved($request->resolution);

        return back()->with('success', 'Ticket resolved successfully.');
    }

    public function close(Customer $customer, CustomerSupportTicket $ticket)
    {
        $ticket->close();
        return back()->with('success', 'Ticket closed successfully.');
    }

    public function rate(Request $request, Customer $customer, CustomerSupportTicket $ticket)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string'
        ]);

        $ticket->update([
            'customer_satisfaction_rating' => $request->rating,
            'customer_feedback' => $request->feedback
        ]);

        return back()->with('success', 'Thank you for your feedback!');
    }
}
