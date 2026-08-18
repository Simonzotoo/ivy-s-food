<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_change_required && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.edit')->with('status', 'Please change your temporary password before continuing.');
        }

        return $next($request);
    }
}
