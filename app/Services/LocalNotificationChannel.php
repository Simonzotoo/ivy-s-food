<?php

namespace App\Services;

use App\Contracts\NotificationChannel;
use App\Models\CreditReminder;

class LocalNotificationChannel implements NotificationChannel
{
    /**
     * @return array<string, mixed>
     */
    public function send(CreditReminder $reminder): array
    {
        return [
            'provider' => 'local',
            'external_send' => false,
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}
