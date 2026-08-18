<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->with('roles')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => Role::query()->orderBy('name')->get()]);
    }

    public function store(Request $request, UserAdministrationService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'password_change_required' => ['nullable', 'boolean'],
        ]);

        $service->create($data, $request->user());

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'password_change_required' => ['nullable', 'boolean'],
        ]);

        $service->update($user, $data, $request->user());

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function status(Request $request, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->authorize('changeStatus', $user);

        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]]);
        $service->setStatus($user, $data['status'], $request->user());

        return back()->with('status', 'User status updated.');
    }
}
