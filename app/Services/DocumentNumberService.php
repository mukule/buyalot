<?php

namespace App\Services;

use App\Models\Billing\DocSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate the next formatted document number in a concurrency-safe way.
     *
     * @param int         $sellerId
     * @param string      $docType   e.g., 'invoice', 'receipt'
     * @param int|null    $year      e.g., 2025 for yearly reset (null for global)
     * @param string|null $prefix    e.g., "INV-SELLER-2025-"
     * @param int         $pad       zero padding length (default 5)
     */
    public static function nextNumber(int $sellerId, string $docType, ?int $year = null, ?string $prefix = null, int $pad = 5): string
    {
        return DB::transaction(function () use ($sellerId, $docType, $year, $prefix, $pad) {
            $seq = DocSequence::where('seller_id', $sellerId)
                ->where('doc_type', $docType)
                ->where(function ($q) use ($year) {
                    if ($year === null) {
                        $q->whereNull('year');
                    } else {
                        $q->where('year', $year);
                    }
                })
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = DocSequence::create([
                    'seller_id' => $sellerId,
                    'doc_type'  => $docType,
                    'year'      => $year,
                    'prefix'    => $prefix,
                    'next_seq'  => 1,
                ]);
            }

            $n = $seq->next_seq;
            $seq->next_seq = $n + 1;
            $seq->save();

            $prefixPart = $prefix ?? ($seq->prefix ?? '');
            return $prefixPart . str_pad((string)$n, $pad, '0', STR_PAD_LEFT);
        });
    }
}
