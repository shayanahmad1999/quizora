<?php
declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
final class ActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->is_active || (int) $request->session()->get('auth_version', $user->auth_version) !== $user->auth_version) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return $request->expectsJson() ? response()->json(['message'=>'Your session ended. Please sign in again.', 'redirect'=>route('login')], 401) : redirect()->route('login');
        }
        if ($user->must_change_password && !$request->routeIs('profile.*', 'logout')) {
            return $request->expectsJson() ? response()->json(['message'=>'Please replace your temporary password before continuing.', 'redirect'=>route('profile.edit')], 409) : redirect()->route('profile.edit');
        }
        return $next($request);
    }
}
