<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'last_login_at',
        'password_change_required',
        'suspended_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->suspended_at === null;
    }

    public function isOwner(): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->is_owner);
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->relationLoaded('roles') === false) {
            $this->load('roles.permissions');
        }

        if ($this->roles->contains(fn (Role $role) => $role->is_owner)) {
            return true;
        }

        return $this->roles->contains(
            fn (Role $role) => $role->permissions->contains('slug', $permission)
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password_change_required' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
