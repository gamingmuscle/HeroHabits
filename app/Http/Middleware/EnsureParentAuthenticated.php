<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentAuthenticated
{
    /**
     * Session timeout in seconds (30 minutes)
     */
    const SESSION_TIMEOUT = 1800; // 30 minutes

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if parent is authenticated
        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        // Check for session timeout
        $lastActivity = $request->session()->get('parent_last_activity');

        if ($lastActivity) {
            $elapsed = time() - $lastActivity;

            if ($elapsed > self::SESSION_TIMEOUT) {
                // Session timed out
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session timed out. Please log in again.',
                        'redirect' => route('parent.login'),
                    ], 401);
                }

                return redirect()->route('parent.login')
                    ->with('error', 'Your session has timed out. Please log in again.');
            }
        }

        // Update last activity time
        $request->session()->put('parent_last_activity', time());

        return $next($request);
    }

    /**
     * Redirect to login page.
     */
    protected function redirectToLogin(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'redirect' => route('parent.login'),
            ], 401);
        }

        return redirect()->route('parent.login')
            ->with('error', 'Please log in to continue.');
    }
}
