<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('event'), fn ($query) => $query->where('event', 'like', '%'.$request->event.'%'))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', ['logs' => $logs]);
    }
}
