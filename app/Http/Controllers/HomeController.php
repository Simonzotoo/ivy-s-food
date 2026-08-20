<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\OperationalDashboardService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', ['settings' => CompanySetting::current()] + app(OperationalDashboardService::class)->data());
    }
}
