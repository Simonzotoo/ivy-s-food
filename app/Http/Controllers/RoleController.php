<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request, Role $role, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        DB::transaction(function () use ($role, $data, $audit, $request): void {
            $old = [
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
            ];

            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if (! $role->is_owner) {
                $role->permissions()->sync($data['permission_ids'] ?? []);
            }

            $audit->record('role.permissions_changed', $role, $old, [
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
            ], actor: $request->user());
        });

        return back()->with('status', 'Role updated.');
    }
}
