<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));
        $audit->record('auth.password_reset_requested', metadata: ['email' => $request->email, 'status' => $status]);

        return back()->with('status', __($status === Password::RESET_LINK_SENT ? $status : Password::RESET_LINK_SENT));
    }
}
