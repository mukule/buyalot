<?php

namespace App\Notifications;

use App\Models\ProductReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ProductReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProductReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $product = $this->report->product;
        $reason  = ProductReport::REASONS[$this->report->reason] ?? $this->report->reason;

        return [
            'type'       => 'product_reported',
            'title'      => 'Listing reported: ' . ($product?->name ?? 'a listing'),
            'body'       => $reason . ($this->report->comment ? ' — ' . Str::limit($this->report->comment, 200) : ''),
            'action_url' => $product?->hashid ? '/admin/products/' . $product->hashid : null,
            'report_id'  => $this->report->id,
            'product_id' => $this->report->product_id,
        ];
    }
}
