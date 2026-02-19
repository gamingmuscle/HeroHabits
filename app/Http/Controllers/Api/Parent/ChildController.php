<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\StoreChildRequest;
use App\Http\Requests\Parent\UpdateChildRequest;
use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ChildController extends Controller
{
    /**
     * Get all children for the authenticated parent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $children = Child::where('user_id', $user->id)
            ->withCount(['questCompletions as completed_quests' => function ($query) {
                $query->where('status', 'Accepted');
            }])
            ->withCount(['questCompletions as pending_approvals' => function ($query) {
                $query->where('status', 'Pending');
            }])
            ->withCount(['questCompletions as completed_today' => function ($query) {
                $query->where('completion_date', today())
                    ->where('status', 'Accepted');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        // Load traits with progress for each child
        foreach ($children as $child) {
            $traits = $child->traits()->get()->map(function ($trait) {
                $xpForNextLevel = \App\Models\CharacterTrait::experienceForLevel($trait->pivot->level + 1);
                $progressPercentage = $xpForNextLevel > 0
                    ? ($trait->pivot->experience_points / $xpForNextLevel) * 100
                    : 0;

                return [
                    'id' => $trait->id,
                    'name' => $trait->name,
                    'icon' => $trait->icon,
                    'level' => $trait->pivot->level,
                    'experience_points' => $trait->pivot->experience_points,
                    'xp_to_next_level' => $xpForNextLevel - $trait->pivot->experience_points,
                    'progress_percentage' => min(100, max(0, $progressPercentage)),
                ];
            });

            $child->traits = $traits;
        }

        return response()->json([
            'success' => true,
            'children' => $children,
        ]);
    }

    /**
     * Store a new child profile.
     */
    public function store(StoreChildRequest $request)
    {
        $child = $request->user()->children()->create($request->validated());

        // Update children cookie
        $this->updateChildrenCookie($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Child profile created successfully!',
            'child' => $child,
        ], 201);
    }

    /**
     * Update an existing child profile.
     */
    public function update(UpdateChildRequest $request, $id)
    {
        $user = $request->user();

        $child = Child::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $child->update($request->validated());

        // Update children cookie
        $this->updateChildrenCookie($user);

        return response()->json([
            'success' => true,
            'message' => 'Child profile updated successfully!',
            'child' => $child->fresh(),
        ]);
    }

    /**
     * Delete a child profile.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $child = Child::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $childName = $child->name;
        $child->delete();

        // Update children cookie
        $this->updateChildrenCookie($user);

        return response()->json([
            'success' => true,
            'message' => "Child profile '{$childName}' deleted successfully!",
        ]);
    }

    /**
     * Get a specific child with detailed stats.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $child = Child::where('id', $id)
            ->where('user_id', $user->id)
            ->withCount(['questCompletions as total_completions'])
            ->withCount(['questCompletions as accepted_completions' => function ($query) {
                $query->where('status', 'Accepted');
            }])
            ->withCount(['questCompletions as pending_completions' => function ($query) {
                $query->where('status', 'Pending');
            }])
            ->withCount('treasurePurchases')
            ->firstOrFail();

        // Get total gold earned
        $totalGoldEarned = $child->questCompletions()
            ->where('status', 'Accepted')
            ->sum('gold_earned');

        // Get total gold spent
        $totalGoldSpent = $child->treasurePurchases()
            ->sum('gold_spent');

        $child->total_gold_earned = $totalGoldEarned;
        $child->total_gold_spent = $totalGoldSpent;

        return response()->json([
            'success' => true,
            'child' => $child,
        ]);
    }

    /**
     * Get quest history for a specific child.
     */
    public function questHistory(Request $request, $id)
    {
        $user = $request->user();

        $child = Child::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $completions = $child->questCompletions()
            ->with('quest:id,title,gold_reward')
            ->orderBy('completion_date', 'desc')
            ->orderBy('completed_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
            ],
            'completions' => $completions,
        ]);
    }

    /**
     * Get list of available avatar images.
     */
    public function avatars()
    {
        $avatarPath = public_path('Assets/Profile');
        $avatars = [];

        if (is_dir($avatarPath)) {
            $files = scandir($avatarPath);
            foreach ($files as $file) {
                // Skip directory markers
                if ($file === '.' || $file === '..') {
                    continue;
                }

                // Prevent path traversal attacks
                if (strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
                    continue;
                }

                // Validate filename format (alphanumeric, underscore, dash, dot only)
                if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
                    continue;
                }

                // Only include image files
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                    $avatars[] = $file;
                }
            }
        }

        return response()->json([
            'success' => true,
            'avatars' => $avatars,
        ]);
    }

    /**
     * Update the children cookie with latest data.
     */
    protected function updateChildrenCookie($user)
    {
        $children = $user->children()
            ->select('id', 'name', 'avatar_image')
            ->get()
            ->toArray();

        Cookie::queue('hero_children', json_encode($children), 60 * 24 * 30);
    }
}
