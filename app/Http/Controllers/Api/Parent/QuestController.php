<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\StoreQuestRequest;
use App\Http\Requests\Parent\UpdateQuestRequest;
use App\Models\Quest;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    /**
     * Get all quests for the authenticated parent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get quests with pending completion count and traits
        $quests = Quest::where('user_id', $user->id)
            ->with('traits:id,name,icon')
            ->withCount(['completions as pending_count' => function ($query) {
                $query->where('status', 'Pending');
            }])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'quests' => $quests,
        ]);
    }

    /**
     * Store a new quest.
     */
    public function store(StoreQuestRequest $request)
    {
        $validated = $request->validated();

        // Create the quest
        $quest = $request->user()->quests()->create($validated);

        // Sync traits if provided
        if (isset($validated['trait_ids'])) {
            $quest->traits()->sync($validated['trait_ids']);
        }

        // Reload with traits
        $quest->load('traits:id,name,icon');

        return response()->json([
            'success' => true,
            'message' => 'Quest created successfully!',
            'quest' => $quest,
        ], 201);
    }

    /**
     * Update an existing quest.
     */
    public function update(UpdateQuestRequest $request, $id)
    {
        $user = $request->user();
        $validated = $request->validated();

        $quest = Quest::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Update quest attributes
        $quest->update($validated);

        // Sync traits if provided
        if (isset($validated['trait_ids'])) {
            $quest->traits()->sync($validated['trait_ids']);
        }

        // Reload with traits
        $quest->load('traits:id,name,icon');

        return response()->json([
            'success' => true,
            'message' => 'Quest updated successfully!',
            'quest' => $quest,
        ]);
    }

    /**
     * Delete a quest.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $quest = Quest::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Note: Cascading delete will remove all related completions
        $quest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quest deleted successfully!',
        ]);
    }

    /**
     * Toggle quest active status.
     */
    public function toggle(Request $request, $id)
    {
        $user = $request->user();

        $quest = Quest::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $quest->toggleActive();

        return response()->json([
            'success' => true,
            'message' => 'Quest status updated!',
            'quest' => $quest->fresh(),
        ]);
    }

    /**
     * Get a specific quest with details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $quest = Quest::where('id', $id)
            ->where('user_id', $user->id)
            ->withCount(['completions as total_completions'])
            ->withCount(['completions as pending_count' => function ($query) {
                $query->where('status', 'Pending');
            }])
            ->withCount(['completions as accepted_count' => function ($query) {
                $query->where('status', 'Accepted');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'quest' => $quest,
        ]);
    }
}
