<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a vendor out straight away if an admin suspends their account
 * while they are still logged in.
 */
class EnsureVendorIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = Auth::guard('vendor')->user();

        if ($vendor && ! $vendor->isActive()) {
            Auth::guard('vendor')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akaun anda tidak aktif. Sila hubungi pihak PBT.');
        }

        return $next($request);
    }
}