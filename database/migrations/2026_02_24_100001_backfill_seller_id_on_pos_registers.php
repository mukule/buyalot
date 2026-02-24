<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill seller_id for existing pos_registers from their warehouse's creator.
     */
    public function up(): void
    {
        $registers = DB::table('pos_registers')
            ->whereNull('seller_id')
            ->whereNotNull('warehouse_id')
            ->get(['id', 'warehouse_id']);

        foreach ($registers as $reg) {
            $creatorId = DB::table('warehouses')->where('id', $reg->warehouse_id)->value('created_by');
            if (! $creatorId) {
                continue;
            }

            $sellerId = DB::table('seller_user')
                ->where('user_id', $creatorId)
                ->value('seller_id');

            if ($sellerId) {
                DB::table('pos_registers')->where('id', $reg->id)->update(['seller_id' => $sellerId]);
            }
        }
    }

    public function down(): void
    {
        // No-op: we don't want to null out seller_ids on rollback
    }
};
