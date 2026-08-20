<?php

namespace App\Services;

use App\Contracts\BroadcastDeliveryChannel;
use App\Models\BroadcastRecipient;

class LocalBroadcastDeliveryChannel implements BroadcastDeliveryChannel
{
    /**
     * @return array<string, mixed>
     */
    public function send(BroadcastRecipient $recipient): array
    {
        return [
            'provider' => 'local',
            'requested_channel' => $recipient->broadcast->channel,
            'external_send' => false,
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}
