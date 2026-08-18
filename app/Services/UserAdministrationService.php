<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $roleIds = $this->authorizedRoleIds($data['role_ids'] ?? [], $actor);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'password_change_required' => (bool) ($data['password_change_required'] ?? true),
            ]);

            $user->roles()->sync($roleIds);
            $user->load('roles');

            $this->audit->record('user.created', $user, [], [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_ids' => $roleIds,
            ], actor: $actor);

            return $user;
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $this->guardOwnerModification($user, $actor);

            $old = $user->only(['name', 'email', 'phone', 'status', 'password_change_required']);
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password_change_required' => (bool) ($data['password_change_required'] ?? false),
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->password_change_required = true;
            }

            $user->save();

            if (array_key_exists('role_ids', $data)) {
                $roleIds = $this->authorizedRoleIds($data['role_ids'] ?? [], $actor);
                $this->guardLastOwnerAccess($user, $roleIds);
                $beforeRoles = $user->roles()->pluck('roles.id')->all();
                $user->roles()->sync($roleIds);
                $this->audit->record('user.roles_changed', $user, ['role_ids' => $beforeRoles], ['role_ids' => $roleIds], actor: $actor);
            }

            $this->audit->record('user.updated', $user, $old, $user->fresh()->only(['name', 'email', 'phone', 'status', 'password_change_required']), actor: $actor);

            return $user->fresh('roles');
        });
    }

    public function setStatus(User $user, string $status, User $actor): void
    {
        DB::transaction(function () use ($user, $status, $actor): void {
            $this->guardOwnerModification($user, $actor);
            $this->guardSelfSuspension($user, $status, $actor);

            $old = $user->only(['status', 'suspended_at']);
            $user->forceFill([
                'status' => $status,
                'suspended_at' => $status === 'suspended' ? now() : null,
            ])->save();

            $this->audit->record("user.{$status}", $user, $old, $user->only(['status', 'suspended_at']), actor: $actor);
        });
    }

    private function authorizedRoleIds(array $roleIds, User $actor): array
    {
        $ids = array_values(array_unique(array_map('intval', $roleIds)));
        $ownerRoleIds = Role::query()->where('is_owner', true)->pluck('id')->all();

        if (! $actor->isOwner() && count(array_intersect($ids, $ownerRoleIds)) > 0) {
            throw ValidationException::withMessages(['role_ids' => 'Only an Owner / Super Admin may assign Owner / Super Admin access.']);
        }

        return $ids;
    }

    private function guardOwnerModification(User $user, User $actor): void
    {
        $user->loadMissing('roles');

        if ($user->isOwner() && ! $actor->isOwner()) {
            throw ValidationException::withMessages(['user' => 'You cannot modify an Owner / Super Admin account.']);
        }
    }

    private function guardSelfSuspension(User $user, string $status, User $actor): void
    {
        if ($user->is($actor) && $status === 'suspended') {
            throw ValidationException::withMessages(['status' => 'You cannot suspend your own account.']);
        }
    }

    private function guardLastOwnerAccess(User $user, array $newRoleIds): void
    {
        $ownerRoleIds = Role::query()->where('is_owner', true)->pluck('id')->all();

        if (count(array_intersect($newRoleIds, $ownerRoleIds)) > 0) {
            return;
        }

        $user->loadMissing('roles');
        if (! $user->isOwner()) {
            return;
        }

        $activeOwners = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('is_owner', true))
            ->count();

        if ($activeOwners <= 1) {
            throw ValidationException::withMessages(['role_ids' => 'You cannot remove the final active Owner / Super Admin access.']);
        }
    }
}
