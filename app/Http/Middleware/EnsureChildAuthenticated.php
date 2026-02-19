<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureChildAuthenticated
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
        // Check if child is authenticated using the 'child' guard
        if (!Auth::guard('child')->check()) {
            return $this->redirectToLogin($request);
        }

        // Check for session timeout
        $lastActivity = $request->session()->get('child_last_activity');

        if ($lastActivity) {
            $elapsed = time() - $lastActivity;

            if ($elapsed > self::SESSION_TIMEOUT) {
                // Session timed out
                Auth::guard('child')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session timed out. Please log in again.',
                        'redirect' => route('child.login'),
                    ], 401);
                }

                return redirect()->route('child.login')
                    ->with('error', 'Your session has timed out. Please log in again.');
            }
        }

        // Update last activity time
        $request->session()->put('child_last_activity', time());

        // Auto-level up the child if they have enough XP
        $child = Auth::guard('child')->user();
        if ($child) {
            $levelUpInfo = $child->checkAndLevelUp();

            // If child leveled up, flash celebration message to session
            if ($levelUpInfo['leveled_up']) {
                $request->session()->flash('child_level_up', [
                    'levels_gained' => $levelUpInfo['levels_gained'],
                    'old_level' => $levelUpInfo['old_level'],
                    'new_level' => $levelUpInfo['new_level'],
                    'child_name' => $child->name,
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Redirect to child login page.
     */
    protected function redirectToLogin(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'redirect' => route('child.login'),
            ], 401);
        }

        return redirect()->route('child.login')
            ->with('error', 'Please log in to continue.');
    }
}
