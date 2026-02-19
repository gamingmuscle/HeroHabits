<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChildLoginRequest;
use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildAuthController extends Controller
{
    /**
     * Show the child login form.
     */
    public function showLogin()
    {
        // Get children from cookie if available
        $savedChildren = json_decode(request()->cookie('hero_children', '[]'), true);

        return view('auth.child-login', [
            'savedChildren' => $savedChildren,
        ]);
    }

    /**
     * Handle child login request.
     */
    public function login(ChildLoginRequest $request)
    {
        $child = Child::find($request->child_id);

        // Verify child exists
        if (!$child) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child profile not found',
                ], 404);
            }

            return back()->withErrors([
                'child_id' => 'Child profile not found.',
            ]);
        }

        // Verify PIN using hashed comparison
        if (!$child->verifyPin($request->pin)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect PIN',
                ], 401);
            }

            return back()->withErrors([
                'pin' => 'Incorrect PIN. Please try again.',
            ])->onlyInput('child_id');
        }

        // Log in the child using Laravel's Auth system
        // We'll use the 'child' guard
        Auth::guard('child')->login($child);
        $request->session()->regenerate();

        // Store additional session data
        $request->session()->put('child_last_activity', time());
        $request->session()->put('child_parent_user_id', $child->user_id);

        // Return success response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'child' => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'avatar_image' => $child->avatar_image,
                    'gold_balance' => $child->gold_balance,
                ],
                'redirect' => route('child.quests'),
            ]);
        }

        return redirect()->route('child.quests')
            ->with('success', "Welcome, {$child->name}!");
    }

    /**
     * Handle child logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('child')->logout();

        // Clear child-specific session data
        $request->session()->forget('child_last_activity');
        $request->session()->forget('child_parent_user_id');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => route('child.login'),
            ]);
        }

        return redirect()->route('child.login');
    }

    /**
     * Get current child profile data.
     */
    public function profile(Request $request)
    {
        $child = Auth::guard('child')->user();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'age' => $child->age,
                'avatar_image' => $child->avatar_image,
                'gold_balance' => $child->gold_balance,
            ],
        ]);
    }

    /**
     * Get all children for the login page.
     */
    public function getAllChildren()
    {
        // Get all children from all users (for login selection)
        // Only return minimal data needed for display
        $children = Child::select('id', 'name', 'avatar_image')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'children' => $children,
        ]);
    }
}
