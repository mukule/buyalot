<?php

namespace App\Http\Controllers\POS;

use App\Events\PosSettingsUpdated;
use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSetting;
use App\Models\Seller\Seller;
use App\Models\Warehouse\Warehouse;
use App\Services\SellerContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosSettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $settings = PosSetting::getSettings();
        $registers = PosRegister::forUser($user)->with('warehouse', 'seller')->get();

        $warehouses = Warehouse::withoutGlobalScopes()->where('status', 'active')
            ->when(SellerContext::isSeller($user), function ($q) use ($user) {
                $relatedUserIds = SellerContext::relatedUserIds($user);
                $q->whereIn('created_by', $relatedUserIds);
            })
            ->get();

        $sellers = SellerContext::isAdmin($user)
            ? Seller::orderBy('company_legal_name')->get(['id', 'company_legal_name'])
            : collect();

        $accountSettings = PosSetting::whereNotNull('seller_id')
            ->when(SellerContext::isSeller($user), function ($q) use ($user) {
                $q->whereIn('seller_id', SellerContext::sellerIds($user));
            })
            ->with('seller:id,company_legal_name')
            ->get();

        return Inertia::render('Admin/POS/Settings', [
            'settings' => $settings,
            'registers' => $registers,
            'warehouses' => $warehouses,
            'sellers' => $sellers,
            'isSellerContext' => SellerContext::isSeller($user),
            'accountSettings' => $accountSettings,
        ]);
    }

    public function updateGlobal(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string',
            'business_email' => 'nullable|email',
            'tax_number' => 'nullable|string',
            'vat_percentage' => 'required|numeric|min:0|max:100',
            'vat_enabled' => 'required|boolean',
            'show_product_images' => 'required|boolean',
            'max_tabs' => 'required|integer|min:1|max:20',
            'product_display_design' => 'required|string',
            'currency_symbol' => 'required|string|max:10',
            'receipt_header' => 'nullable|string',
            'receipt_footer' => 'nullable|string',
            'invoice_prefix' => 'required|string|max:20',
            'receipt_prefix' => 'required|string|max:20',
            'payment_methods' => 'nullable|array',
            'require_admin_void' => 'required|boolean',
        ]);

        $settings = PosSetting::getSettings();
        $settings->update($validated);
        $settings->bumpVersion();

        event(new PosSettingsUpdated($settings->fresh()));

        return back()->with('success', 'Global POS settings updated.');
    }

    public function storeAccountSettings(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'seller_id' => 'required|exists:seller_applications,id',
            'max_tabs' => 'required|integer|min:1|max:20',
            'show_product_images' => 'required|boolean',
            'product_display_design' => 'required|string|in:grid,small_grid',
            'require_admin_void' => 'required|boolean',
        ];

        if (SellerContext::isSeller($user)) {
            $sellerIds = SellerContext::sellerIds($user);
            if (! $sellerIds->contains($request->seller_id)) {
                abort(403, 'You cannot manage settings for this account.');
            }
        }

        $validated = $request->validate($rules);

        $setting = PosSetting::getForSeller($validated['seller_id']);
        $setting->update($validated);
        $setting->bumpVersion();

        event(new PosSettingsUpdated($setting->fresh()));

        return back()->with('success', 'Account POS settings updated.');
    }

    public function updateAccountSettings(Request $request, PosSetting $accountSetting)
    {
        $user = auth()->user();

        if (! $accountSetting->seller_id) {
            abort(404);
        }

        if (SellerContext::isSeller($user) && ! SellerContext::belongsToSeller($user, $accountSetting->seller_id)) {
            abort(403, 'You cannot manage settings for this account.');
        }

        $validated = $request->validate([
            'max_tabs' => 'required|integer|min:1|max:20',
            'show_product_images' => 'required|boolean',
            'product_display_design' => 'required|string|in:grid,small_grid',
            'require_admin_void' => 'required|boolean',
        ]);

        $accountSetting->update($validated);
        $accountSetting->bumpVersion();

        event(new PosSettingsUpdated($accountSetting->fresh()));

        return back()->with('success', 'Account POS settings updated.');
    }

    public function destroyAccountSettings(PosSetting $accountSetting)
    {
        $user = auth()->user();

        if (! $accountSetting->seller_id) {
            abort(404);
        }

        if (SellerContext::isSeller($user) && ! SellerContext::belongsToSeller($user, $accountSetting->seller_id)) {
            abort(403);
        }

        $accountSetting->delete();

        return back()->with('success', 'Account-specific settings removed. Global defaults will apply.');
    }

    public function storeRegister(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'required|in:active,inactive',
            'receipt_type' => 'required|in:thermal,standard',
            'invoice_type' => 'required|in:standard,simplified',
            'auto_print_receipt' => 'required|boolean',
        ];

        if (SellerContext::isAdmin($user)) {
            $rules['seller_id'] = 'required|exists:seller_applications,id';
        }

        $validated = $request->validate($rules);

        if (SellerContext::isSeller($user)) {
            $sellerIds = SellerContext::sellerIds($user);
            $validated['seller_id'] = $sellerIds->first();
            if (empty($validated['seller_id'])) {
                return back()->with('error', 'You must be linked to a seller account to create terminals.');
            }
        }

        PosRegister::create($validated);

        return back()->with('success', 'POS Terminal created.');
    }

    public function updateRegister(Request $request, PosRegister $register)
    {
        $user = auth()->user();
        $this->authorizeRegisterAccess($user, $register);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'required|in:active,inactive',
            'receipt_type' => 'required|in:thermal,standard',
            'invoice_type' => 'required|in:standard,simplified',
            'auto_print_receipt' => 'required|boolean',
        ]);

        $register->update($validated);

        return back()->with('success', 'POS Terminal updated.');
    }

    public function destroyRegister(PosRegister $register)
    {
        $this->authorizeRegisterAccess(auth()->user(), $register);

        if ($register->sessions()->where('status', 'open')->exists()) {
            return back()->with('error', 'Cannot delete a terminal with an active session.');
        }

        $register->delete();

        return back()->with('success', 'POS Terminal deleted.');
    }

    protected function authorizeRegisterAccess($user, PosRegister $register): void
    {
        if (SellerContext::isAdmin($user)) {
            return;
        }

        if (! SellerContext::belongsToSeller($user, $register->seller_id)) {
            abort(403, 'You cannot manage this terminal.');
        }
    }
}
