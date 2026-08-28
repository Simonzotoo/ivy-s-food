<?php

namespace Tests\Feature;

use App\Mail\SystemErrorMail;
use App\Models\CompanySetting;
use App\Services\ExceptionAlertService;
use Database\Seeders\CompanySettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ExceptionAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanySettingsSeeder::class);
    }

    public function test_no_email_is_sent_outside_production(): void
    {
        Mail::fake();
        CompanySetting::current()->update(['email' => 'owner@example.com']);

        app(ExceptionAlertService::class)->notify(new RuntimeException('boom'));

        Mail::assertNothingSent();
    }

    public function test_email_is_sent_to_the_business_address_in_production(): void
    {
        Mail::fake();
        CompanySetting::current()->update(['email' => 'owner@example.com']);
        $this->app['env'] = 'production';

        app(ExceptionAlertService::class)->notify(new RuntimeException('boom'));

        Mail::assertSent(SystemErrorMail::class, fn (SystemErrorMail $mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_no_email_is_sent_when_no_business_email_is_configured(): void
    {
        Mail::fake();
        CompanySetting::current()->update(['email' => null]);
        $this->app['env'] = 'production';

        app(ExceptionAlertService::class)->notify(new RuntimeException('boom'));

        Mail::assertNothingSent();
    }

    public function test_the_same_error_is_only_emailed_once_within_the_throttle_window(): void
    {
        Mail::fake();
        Cache::flush();
        CompanySetting::current()->update(['email' => 'owner@example.com']);
        $this->app['env'] = 'production';

        $service = app(ExceptionAlertService::class);
        $exception = new RuntimeException('repeat');
        $service->notify($exception);
        $service->notify($exception);

        Mail::assertSentCount(1);
    }
}
