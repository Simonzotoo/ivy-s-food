<?php

namespace App\Services;

use App\Mail\SystemErrorMail;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the business owner when an unhandled error happens in production, so
 * problems surface even though there is no third-party monitoring service
 * (Sentry/Flare/etc.) configured. Deliberately dependency-free: it reuses the
 * mail system already wired up for receipts and broadcasts, so it works the
 * moment real SMTP credentials are in place, with no separate account needed.
 *
 * The same error is only emailed once per 15 minutes so a repeating failure
 * (e.g. a bug hit on every request in a loop) can't flood the inbox.
 */
class ExceptionAlertService
{
    private const THROTTLE_MINUTES = 15;

    public function notify(Throwable $e): void
    {
        if (! app()->environment('production')) {
            return;
        }

        try {
            $key = 'exception-alert:'.md5($e::class.$e->getFile().$e->getLine());

            if (Cache::has($key)) {
                return;
            }

            Cache::put($key, true, now()->addMinutes(self::THROTTLE_MINUTES));

            $email = CompanySetting::current()->email;

            if (! $email) {
                return;
            }

            Mail::to($email)->send(SystemErrorMail::forException($e));
        } catch (Throwable) {
            // Alerting must never itself crash the request or mask the original error.
        }
    }
}
