<?php

namespace App\Services\Broadcast;

use App\Contracts\BroadcastDeliveryChannel;
use App\Models\BroadcastRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SmsBroadcastChannel implements BroadcastDeliveryChannel
{
    private const ENDPOINT = 'https://api.mnotify.com/api/sms/quick';

    /**
     * @return array<string, mixed>
     */
    public function send(BroadcastRecipient $recipient): array
    {
        $phone = $recipient->customer?->phone;

        if (! $phone) {
            return ['success' => false, 'provider' => 'mnotify', 'error' => 'Customer has no phone number on file.'];
        }

        $key = config('services.mnotify.key');

        if (! $key) {
            return ['success' => false, 'provider' => 'mnotify', 'error' => 'mNotify API key is not configured.'];
        }

        try {
            $response = Http::timeout(10)->post(self::ENDPOINT.'?key='.$key, [
                'recipient' => [$phone],
                'sender' => Str::limit((string) config('services.mnotify.sender_id', "Ivy's Food"), 11, ''),
                'message' => $recipient->broadcast->body,
            ]);

            $body = $response->json();

            return [
                'success' => $response->successful() && ($body['status'] ?? null) === 'success',
                'provider' => 'mnotify',
                'status_code' => $response->status(),
                'response' => $body,
            ];
        } catch (Throwable $e) {
            Log::warning('broadcast.sms_send_failed', ['recipient_id' => $recipient->id, 'error' => $e->getMessage()]);

            return ['success' => false, 'provider' => 'mnotify', 'error' => $e->getMessage()];
        }
    }
}
