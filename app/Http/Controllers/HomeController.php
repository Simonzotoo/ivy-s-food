<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'settings' => CompanySetting::current(),
            'userCount' => User::query()->count(),
            'roleCount' => Role::query()->count(),
            'auditCount' => AuditLog::query()->count(),
        ]);
    }
}
