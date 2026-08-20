<?php

namespace App\Contracts;

use App\Models\BroadcastRecipient;

interface BroadcastDeliveryChannel
{
    /**
     * @return array<string, mixed>
     */
    public function send(BroadcastRecipient $recipient): array;
}
