<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\CompanySettingsSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Module1FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingsSeeder::class);
    }

    public function test_valid_login_updates_session_and_audit(): void
    {
        $user = $this->ownerUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password!234',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login', 'auditable_id' => $user->id]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_login_is_denied_and_audited(): void
    {
        $user = $this->ownerUser();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login_failed']);
    }

    public function test_login_throttling_blocks_repeated_failures(): void
    {
        $user = $this->ownerUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'bad']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'bad'])
            ->assertSessionHasErrors('email');

        $this->assertTrue(RateLimiter::tooManyAttempts(strtolower($user->email).'|127.0.0.1', 5));
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->ownerUser(['status' => 'suspended', 'suspended_at' => now()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password!234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.login_suspended_denied']);
    }

    public function test_logout_invalidates_session_and_audits(): void
    {
        $user = $this->ownerUser();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.logout']);
    }

    public function test_forgot_and_reset_password_workflow(): void
    {
        $user = $this->ownerUser();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        $token = Password::createToken($user);
        $this->post('/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword!234',
            'password_confirmation' => 'NewPassword!234',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword!234', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.password_reset_completed']);
    }

    public function test_unauthenticated_route_denial(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_permission_denial_and_authorized_access(): void
    {
        $cashier = $this->userWithRole('Cashier / Sales Officer');
        $owner = $this->ownerUser(['email' => 'owner2@example.com']);

        $this->actingAs($cashier)->get('/users')->assertForbidden();
        $this->actingAs($owner)->get('/users')->assertOk()->assertSee('Users');
    }

    public function test_user_creation_validation_and_role_assignment(): void
    {
        $owner = $this->ownerUser();
        $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

        $this->actingAs($owner)->post('/users', [])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->actingAs($owner)->post('/users', [
            'name' => 'Ama Manager',
            'email' => 'ama@example.com',
            'phone' => '0240000000',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role_ids' => [$managerRole->id],
        ])->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'ama@example.com')->firstOrFail();
        $this->assertTrue($created->roles()->whereKey($managerRole->id)->exists());
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.created', 'auditable_id' => $created->id]);
    }

    public function test_lower_user_cannot_modify_owner_or_assign_owner_role(): void
    {
        $owner = $this->ownerUser();
        $manager = $this->userWithRole('Manager');
        $manager->roles()->first()->permissions()->sync(Permission::query()->whereIn('slug', ['users.edit', 'roles.assign'])->pluck('id'));

        $this->actingAs($manager)->put(route('users.update', $owner), [
            'name' => 'Changed',
            'email' => $owner->email,
        ])->assertForbidden();
    }

    public function test_user_creation_requires_role_assignment_permission_when_roles_are_submitted(): void
    {
        $manager = $this->userWithRole('Manager');
        $manager->roles()->first()->permissions()->sync(Permission::query()->whereIn('slug', ['users.create'])->pluck('id'));
        $manager = $manager->fresh('roles.permissions');
        $cashierRole = Role::query()->where('name', 'Cashier / Sales Officer')->firstOrFail();

        $this->actingAs($manager)->post('/users', [
            'name' => 'Kofi Cashier',
            'email' => 'kofi@example.com',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
            'role_ids' => [$cashierRole->id],
        ])->assertSessionHasErrors('role_ids');

        $this->assertDatabaseMissing('users', ['email' => 'kofi@example.com']);
    }

    public function test_company_setting_updates_are_audited(): void
    {
        $owner = $this->ownerUser();

        $this->actingAs($owner)->put('/company-settings', [
            'company_name' => 'Ivy Food Company',
            'trading_name' => "Ivy's Food",
            'currency' => 'GHS',
            'time_zone' => 'Africa/Accra',
            'default_credit_period_days' => 30,
        ])->assertRedirect();

        $this->assertSame('Ivy Food Company', CompanySetting::current()->company_name);
        $this->assertDatabaseHas('audit_logs', ['event' => 'company_settings.updated']);
    }

    public function test_sensitive_audit_values_are_redacted(): void
    {
        $log = app(AuditService::class)->record('security.test', new User, [], [
            'password' => 'plain',
            'password_confirmation' => 'plain',
            'name' => 'Safe',
        ]);

        $this->assertSame('[REDACTED]', $log->new_values['password']);
        $this->assertSame('[REDACTED]', $log->new_values['password_confirmation']);
        $this->assertSame('Safe', $log->new_values['name']);
    }

    public function test_seeders_are_idempotent(): void
    {
        $permissionCount = Permission::query()->count();
        $roleCount = Role::query()->count();
        $settingsCount = CompanySetting::query()->count();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(CompanySettingsSeeder::class);

        $this->assertSame($permissionCount, Permission::query()->count());
        $this->assertSame($roleCount, Role::query()->count());
        $this->assertSame($settingsCount, CompanySetting::query()->count());
    }

    public function test_critical_pages_render_for_desktop_and_mobile_user_agents(): void
    {
        $owner = $this->ownerUser();

        $this->actingAs($owner)->withHeader('User-Agent', 'Desktop Browser')->get('/home')
            ->assertOk()
            ->assertSee('Module 1 foundation')
            ->assertSee('images/ivys-logo.png', false)
            ->assertSee('max-h-full max-w-full object-contain', false);

        $this->actingAs($owner)->withHeader('User-Agent', 'Mobile Safari')->get('/company-settings')->assertOk()->assertSee('Company Settings');
    }

    private function ownerUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => Hash::make('Password!234'),
            'status' => 'active',
            'password_change_required' => false,
        ], $attributes));

        $user->roles()->attach(Role::query()->where('is_owner', true)->firstOrFail());

        return $user->fresh('roles.permissions');
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password!234'),
            'status' => 'active',
            'password_change_required' => false,
        ]);
        $user->roles()->attach(Role::query()->where('name', $roleName)->firstOrFail());

        return $user->fresh('roles.permissions');
    }
}
