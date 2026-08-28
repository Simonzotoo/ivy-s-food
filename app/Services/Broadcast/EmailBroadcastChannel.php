<?php

namespace App\Services\Broadcast;

use App\Contracts\BroadcastDeliveryChannel;
use App\Mail\BroadcastMail;
use App\Models\BroadcastRecipient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailBroadcastChannel implements BroadcastDeliveryChannel
{
    /**
     * @return array<string, mixed>
     */
    public function send(BroadcastRecipient $recipient): array
    {
        $email = $recipient->customer?->email;

        if (! $email) {
            return ['success' => false, 'provider' => 'mail', 'error' => 'Customer has no email address on file.'];
        }

        try {
            Mail::to($email)->send(new BroadcastMail($recipient->broadcast));

            return ['success' => true, 'provider' => 'mail', 'to' => $email];
        } catch (Throwable $e) {
            Log::warning('broadcast.email_send_failed', ['recipient_id' => $recipient->id, 'error' => $e->getMessage()]);

            return ['success' => false, 'provider' => 'mail', 'error' => $e->getMessage()];
        }
    }
}
