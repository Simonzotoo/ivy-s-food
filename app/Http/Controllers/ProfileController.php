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
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'profile_photo.required' => 'Choose a photo to upload.',
            'profile_photo.uploaded' => 'That photo is too large or the upload was interrupted. Please choose a photo under 5 MB.',
            'profile_photo.max' => 'That photo is larger than 5 MB. Please choose a smaller photo.',
            'profile_photo.image' => 'That file is not a supported image.',
            'profile_photo.mimes' => 'Please upload a JPG, PNG or WebP photo.',
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
