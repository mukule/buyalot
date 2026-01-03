<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\POS\PosRegister;
use App\Models\POS\PosSetting;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosSettingsController extends Controller
{
    public function index()
    {
        $settings = PosSetting::getSettings();
        $registers = PosRegister::with('warehouse')->get();
        $warehouses = Warehouse::where('status', 'active')->get();

        return Inertia::render('Admin/POS/Settings', [
            'settings' => $settings,
            'registers' => $registers,
            'warehouses' => $warehouses,
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

        return back()->with('success', 'Global POS settings updated.');
    }

    public function storeRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'required|in:active,inactive',
            'receipt_type' => 'required|in:thermal,standard',
            'invoice_type' => 'required|in:standard,simplified',
            'auto_print_receipt' => 'required|boolean',
        ]);

        PosRegister::create($validated);

        return back()->with('success', 'POS Register created.');
    }

    public function updateRegister(Request $request, PosRegister $register)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'status' => 'required|in:active,inactive',
            'receipt_type' => 'required|in:thermal,standard',
            'invoice_type' => 'required|in:standard,simplified',
            'auto_print_receipt' => 'required|boolean',
        ]);

        $register->update($validated);

        return back()->with('success', 'POS Register updated.');
    }

    public function destroyRegister(PosRegister $register)
    {
        if ($register->sessions()->where('status', 'open')->exists()) {
            return back()->with('error', 'Cannot delete a register with an active session.');
        }

        $register->delete();

        return back()->with('success', 'POS Register deleted.');
    }
}
