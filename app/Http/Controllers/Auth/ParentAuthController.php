<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ParentLoginRequest;
use App\Http\Requests\Auth\ParentRegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class ParentAuthController extends Controller
{
    /**
     * Session timeout in minutes (30 minutes)
     */
    const SESSION_TIMEOUT = 30;

    /**
     * Show the parent login form.
     */
    public function showLogin()
    {
        // Check if parent has logged in before (cookie exists)
        $hasParentCookie = Cookie::has('hero_parent');
        $savedDisplayname = Cookie::get('hero_parent');
        $savedChildren = json_decode(Cookie::get('hero_children', '[]'), true);

        return view('auth.parent-login', [
            'hasParentCookie' => $hasParentCookie,
            'savedDisplayname' => $savedDisplayname,
            'savedChildren' => $savedChildren,
        ]);
    }

    /**
     * Show the parent registration form.
     */
    public function showRegister()
    {
        return view('auth.parent-register');
    }

    /**
     * Handle parent login request.
     */
    public function login(ParentLoginRequest $request)
    {
        $credentials = $request->only('username', 'password');

        // Attempt to authenticate
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Store additional session data
            $request->session()->put('parent_last_activity', time());
            $request->session()->put('parent_displayname', $user->displayname);

            // Load children for cookie storage
            $children = $user->children()->select('id', 'name', 'avatar_image')->get()->toArray();

            // Set cookies (30 days)
            Cookie::queue('hero_parent', $user->displayname, 60 * 24 * 30);
            Cookie::queue('hero_children', json_encode($children), 60 * 24 * 30);

            // Return success response for AJAX or redirect for web
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'displayname' => $user->displayname,
                    ],
                    'redirect' => route('parent.dashboard'),
                ]);
            }

            return redirect()->intended(route('parent.dashboard'));
        }

        // Authentication failed
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password',
            ], 401);
        }

        return back()->withErrors([
            'username' => 'Invalid username or password.',
        ])->onlyInput('username');
    }

    /**
     * Handle parent registration request.
     */
    public function register(ParentRegisterRequest $request)
    {
        // Create the user
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'displayname' => $request->displayname,
        ]);

        // Mark invitation as used if invitation-only mode is enabled
        if (config('herohabits.registration.invitation_only', false) && $request->has('invitation_code')) {
            $invitation = \App\Models\Invitation::where('code', strtoupper($request->invitation_code))->first();
            if ($invitation) {
                $invitation->markAsUsed($user);
            }
        }

        // Log the user in
        Auth::login($user);
        $request->session()->regenerate();

        // Store session data
        $request->session()->put('parent_last_activity', time());
        $request->session()->put('parent_displayname', $user->displayname);

        // Set cookies
        Cookie::queue('hero_parent', $user->displayname, 60 * 24 * 30);

        // Return success response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'displayname' => $user->displayname,
                ],
                'redirect' => route('parent.profiles'),
            ], 201);
        }

        return redirect()->route('parent.profiles')
            ->with('success', 'Account created! Now add your first child profile.');
    }

    /**
     * Handle parent logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear only parent-specific session data (keep cookies for convenience)
        $request->session()->forget('parent_last_activity');
        $request->session()->forget('parent_displayname');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => route('parent.login'),
            ]);
        }

        return redirect()->route('parent.login');
    }

    /**
     * Get remaining session time in seconds.
     */
    public function getSessionTimeRemaining(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['remaining' => 0]);
        }

        $lastActivity = $request->session()->get('parent_last_activity', time());
        $elapsed = time() - $lastActivity;
        $remaining = (self::SESSION_TIMEOUT * 60) - $elapsed;

        return response()->json([
            'remaining' => max(0, $remaining),
            'minutes' => max(0, floor($remaining / 60)),
        ]);
    }

    /**
     * Refresh session activity timestamp.
     */
    public function refreshSession(Request $request)
    {
        if (Auth::check()) {
            $request->session()->put('parent_last_activity', time());

            return response()->json([
                'success' => true,
                'message' => 'Session refreshed',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Not authenticated',
        ], 401);
    }
}
