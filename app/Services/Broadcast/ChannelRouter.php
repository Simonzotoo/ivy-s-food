<?php

namespace App\Services\Broadcast;

use App\Contracts\BroadcastDeliveryChannel;
use App\Models\BroadcastRecipient;

class ChannelRouter implements BroadcastDeliveryChannel
{
    public function __construct(
        private readonly SmsBroadcastChannel $sms,
        private readonly EmailBroadcastChannel $email,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function send(BroadcastRecipient $recipient): array
    {
        return match ($recipient->broadcast->channel) {
            'email' => $this->email->send($recipient),
            default => $this->sms->send($recipient),
        };
    }
}
