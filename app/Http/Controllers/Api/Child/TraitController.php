<?php

namespace App\Http\Controllers\Api\Child;

use App\Http\Controllers\Controller;
use App\Models\CharacterTrait;
use Illuminate\Http\Request;

class TraitController extends Controller
{
    /**
     * Get all traits with the authenticated child's progress.
     */
    public function index(Request $request)
    {
        // Get authenticated child
        $child = \Auth::guard('child')->user();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Child not authenticated',
            ], 401);
        }

        // Get ALL traits (ordered by sort_order)
        $allTraits = CharacterTrait::orderBy('sort_order')->get();

        // Map traits to include child's progress (or defaults if not started)
        $traits = $allTraits->map(function ($trait) use ($child) {
            // Check if child has progress on this trait
            $childTrait = $child->childTraits()
                ->where('trait_id', $trait->id)
                ->first();

            if ($childTrait) {
                // Child has progress on this trait
                $level = $childTrait->level;
                $xp = $childTrait->experience_points;
            } else {
                // Child hasn't started this trait yet
                $level = 1;
                $xp = 0;
            }

            $currentLevelXP = CharacterTrait::experienceForLevel($level);
            $nextLevelXP = CharacterTrait::experienceForLevel($level + 1);

            // Calculate progress within current level
            if ($nextLevelXP == 0) {
                // Max level reached
                $progressPercentage = 100;
                $xpToNextLevel = 0;
            } else {
                $xpIntoCurrentLevel = $xp - $currentLevelXP;
                $xpNeededForLevel = $nextLevelXP - $currentLevelXP;
                $progressPercentage = $xpNeededForLevel > 0
                    ? ($xpIntoCurrentLevel / $xpNeededForLevel) * 100
                    : 0;
                $xpToNextLevel = max(0, $nextLevelXP - $xp);
            }

            return [
                'id' => $trait->id,
                'name' => $trait->name,
                'description' => $trait->description,
                'icon' => $trait->icon,
                'level' => $level,
                'experience_points' => $xp,
                'xp_to_next_level' => $xpToNextLevel,
                'progress_percentage' => min(100, max(0, $progressPercentage)),
            ];
        });

        return response()->json([
            'success' => true,
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'level' => $child->level,
                'experience_points' => $child->experience_points,
                'xp_to_next_level' => $child->experienceToNextLevel(),
                'progress_percentage' => $child->progressPercentage(),
            ],
            'traits' => $traits,
        ]);
    }
}
