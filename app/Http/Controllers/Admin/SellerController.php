<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller\Seller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        // Note: App\Models\Seller\Seller maps to 'seller_applications' table.
        // Enrich the Vendors list with application fields and robust search.
        $query = Seller::query()->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%")
                  ->orWhere('company_legal_name', 'like', "%{$search}%")
                  ->orWhere('business_type', 'like', "%{$search}%")
                  ->orWhere('primary_product_category', 'like', "%{$search}%");
            });
        }

        $sellers = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function ($app) {
                return [
                    'id' => $app->id,
                    'first_name' => $app->first_name,
                    'last_name' => $app->last_name,
                    'company_legal_name' => $app->company_legal_name,
                    'business_type' => $app->business_type,
                    'primary_product_category' => $app->primary_product_category,
                    'owner_email' => $app->owner_email ?? $app->contact_email,
                    'owner_phone' => $app->owner_phone ?? $app->contact_phone,
                    'status' => $app->status,
                    'is_active' => (int)($app->status) === 1,
                    'created_at' => optional($app->created_at)->toDateString(),
                ];
            });

        return Inertia::render('Admin/Sellers/Index', [
            'sellers' => $sellers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Seller $seller)
    {
        // Load related users with pivot info
        $seller->load(['users' => function ($q) {
            $q->withPivot(['role', 'is_owner']);
        }]);

        $owner = $seller->users->firstWhere('pivot.is_owner', true) ?? $seller->users->first();

        $business = [
            'company_legal_name' => $seller->company_legal_name,
            'business_type' => $seller->business_type,
            'primary_product_category' => $seller->primary_product_category,
            'contact_email' => $seller->contact_email ?? $seller->owner_email,
            'contact_phone' => $seller->contact_phone ?? $seller->owner_phone,
            'country' => $seller->country,
            'nationality' => $seller->nationality,
            'website' => $seller->product_website ?? $seller->website,
            'owned_brands' => $seller->owned_brands,
            'licensed_brands' => $seller->licensed_brands,
            'business_summary' => $seller->business_summary,
            'status' => $seller->status,
            'is_active' => (int) $seller->status === 1,
        ];

        $ownerUser = $owner ? [
            'id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => $owner->phone,
        ] : null;

        $users = $seller->users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->pivot->role ?? null,
            ];
        });

        return Inertia::render('Admin/Sellers/Show', [
            'seller' => [
                'id' => $seller->id,
                'display_name' => $seller->company_legal_name ?? ($seller->first_name && $seller->last_name ? $seller->first_name.' '.$seller->last_name : 'Vendor #'.$seller->id),
                'is_active' => (int) $seller->status === 1,
                'created_at' => optional($seller->created_at)->toDateString(),
            ],
            'business' => $business,
            'owner_user' => $ownerUser,
            'role' => $owner?->pivot?->role,
            'users' => $users,
        ]);
    }
}
