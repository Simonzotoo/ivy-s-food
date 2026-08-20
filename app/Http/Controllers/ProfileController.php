<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'password_change_required' => false,
        ])->save();

        $audit->record('profile.password_changed', $request->user());

        return back()->with('status', 'Password updated.');
    }

    public function updatePhoto(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $newPath = $data['profile_photo']->store('profile-photos', 'public');

        $user->forceFill([
            'profile_photo_path' => $newPath,
        ])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $audit->record('profile.photo_updated', $user, [
            'profile_photo_path' => $oldPath,
        ], [
            'profile_photo_path' => $newPath,
        ]);

        return back()->with('status', 'Profile photo updated.');
    }
}
