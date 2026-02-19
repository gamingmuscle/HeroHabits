<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\CharacterTrait;
use Illuminate\Http\Request;

class TraitController extends Controller
{
    /**
     * Get all available traits.
     */
    public function index()
    {
        $traits = CharacterTrait::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'traits' => $traits,
        ]);
    }

    /**
     * Get traits for a specific child with their levels.
     */
    public function childTraits(Request $request, $childId)
    {
        $user = $request->user();

        // Verify child belongs to this parent
        $child = Child::where('id', $childId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Get traits with child's progress
        $traits = $child->traits()->get()->map(function ($trait) {
            return [
                'id' => $trait->id,
                'name' => $trait->name,
                'description' => $trait->description,
                'icon' => $trait->icon,
                'level' => $trait->pivot->level,
                'experience_points' => $trait->pivot->experience_points,
                'xp_to_next_level' => CharacterTrait::experienceForLevel($trait->pivot->level + 1) - $trait->pivot->experience_points,
                'progress_percentage' => ($trait->pivot->experience_points / CharacterTrait::experienceForLevel($trait->pivot->level + 1)) * 100,
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

