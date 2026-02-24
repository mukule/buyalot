<?php

namespace App\Events;

use App\Models\POS\PosSetting;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an admin updates POS settings for a seller account.
 *
 * When a broadcasting driver is configured this will push the new
 * settings_version to all POS clients listening on the seller channel
 * so they can invalidate their local cache immediately.
 *
 * Without a broadcasting driver the POS frontend falls back to
 * lightweight polling via /api/pos/settings-version.
 */
class PosSettingsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $sellerId;
    public int $settingsVersion;
    public array $settings;

    public function __construct(PosSetting $posSettings)
    {
        $this->sellerId = $posSettings->seller_id ?? 0;
        $this->settingsVersion = $posSettings->settings_version;
        $this->settings = $posSettings->toArray();
    }

    public function broadcastOn(): array
    {
        $channels = [new Channel('pos-settings')];

        if ($this->sellerId) {
            $channels[] = new Channel("pos-settings.{$this->sellerId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'settings.updated';
    }
}
