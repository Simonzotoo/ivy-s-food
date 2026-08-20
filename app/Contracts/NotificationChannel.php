<?php

namespace App\Contracts;

use App\Models\CreditReminder;

interface NotificationChannel
{
    /**
     * @return array<string, mixed>
     */
    public function send(CreditReminder $reminder): array;
}
