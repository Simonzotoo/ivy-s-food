<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class Module13SecurityDeploymentTest extends TestCase
{
    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_branded_error_pages_render_without_authenticated_layout(): void
    {
        $this->withoutVite();

        $this->get('/definitely-missing-module-13-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee("Ivy's Food", false);
    }

    public function test_session_security_defaults_are_documented_and_configurable(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', File::get(base_path('.env.example')));
    }

    public function test_module_statuses_are_tested_or_planned_and_not_approved_by_system(): void
    {
        $roadmap = File::get(base_path('docs/modules-roadmap.md'));

        $this->assertStringNotContainsString('| Approved |', $roadmap);
        $this->assertStringContainsString('| 13 | Security, Performance, Deployment and Final Acceptance | Tested |', $roadmap);
    }
}
