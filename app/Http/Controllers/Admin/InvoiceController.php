<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices in the Admin panel.
     */
    public function index(Request $request)
    {
        $filters = [
            'seller_id' => $request->integer('seller_id') ?: null,
            'status' => $request->string('status') ?: null,
            'type' => $request->string('type') ?: null,
            'search' => $request->string('search') ?: null,
        ];

        $query = Invoice::query()
            ->when($filters['seller_id'], fn ($q, $v) => $q->where('seller_id', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['type'], fn ($q, $v) => $q->where('type', $v))
            ->when($filters['search'], function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $term = "%" . $v . "%";
                    $qq->where('number', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('customer_po', 'like', $term);
                });
            })
            ->orderByDesc('id');

        $invoices = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => array_filter($filters, fn ($v) => $v !== null && $v !== ''),
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => '/admin/dashboard'],
                ['title' => 'Invoices', 'href' => '/admin/invoices'],
            ],
        ]);
    }
}
