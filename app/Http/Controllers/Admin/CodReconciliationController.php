<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Orders\CodReconciliation;
use App\Models\Warehouse\WarehouseManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodReconciliationController extends Controller
{
    /**
     * List COD cash reconciliations. Admin sees all; warehouse managers see their warehouse only.
     */
    public function index(Request $request)
    {
        $query = CodReconciliation::with([
            'order:id,ulid,order_code,status,total_amount,currency',
            'deliveryUser:id,name,email',
            'warehouse:id,name,address',
            'confirmedByUser:id,name',
        ])->orderByDesc('updated_at');

        // Filter by warehouse if user is a warehouse manager (not admin/super-admin)
        $user = $request->user();
        if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
            $managedWarehouseIds = WarehouseManager::where('user_id', $user->id)
                ->where('active', true)
                ->pluck('warehouse_id');
            if ($managedWarehouseIds->isEmpty()) {
                $query->whereRaw('1 = 0'); // No warehouse access
            } else {
                $query->whereIn('warehouse_id', $managedWarehouseIds);
            }
        }

        $reconciliations = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/CodReconciliations/Index', [
            'reconciliations' => $reconciliations,
        ]);
    }

    /**
     * Admin/seller confirms they have received the cash from the delivery person.
     */
    public function confirm(Request $request, CodReconciliation $reconciliation)
    {
        if ($reconciliation->confirmed_at) {
            return back()->with('info', 'This reconciliation is already confirmed.');
        }

        $user = $request->user();
        $managedWarehouseIds = WarehouseManager::where('user_id', $user->id)
            ->where('active', true)
            ->pluck('warehouse_id');
        $canConfirm = $user->hasRole('admin') || $user->hasRole('super-admin')
            || $managedWarehouseIds->contains($reconciliation->warehouse_id);

        if (! $canConfirm) {
            abort(403, 'You do not have permission to confirm reconciliations for this warehouse.');
        }

        $reconciliation->update([
            'confirmed_by' => $user->id,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'COD cash receipt confirmed. Amount: ' . $reconciliation->currency . ' ' . number_format($reconciliation->amount, 2));
    }
}
