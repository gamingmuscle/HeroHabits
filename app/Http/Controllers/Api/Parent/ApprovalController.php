<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Get all pending quest completions for the authenticated parent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get pending completions for all children of this parent
        $pending = QuestCompletion::with(['quest:id,title,description,gold_reward', 'child:id,name,avatar_image'])
            ->pending()
            ->whereHas('quest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('completed_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'pending' => $pending,
            'count' => $pending->count(),
        ]);
    }

    /**
     * Accept a quest completion.
     */
    public function accept(Request $request, $id)
    {
        $user = $request->user();

        $completion = QuestCompletion::with(['quest', 'child'])
            ->whereHas('quest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($id);

        // Check if already processed
        if (!$completion->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This quest completion has already been processed.',
            ], 400);
        }

        // Accept the completion (this also updates child's gold balance and awards XP)
        $levelUpInfo = $completion->accept($user);

        return response()->json([
            'success' => true,
            'message' => 'Quest completion accepted!',
            'completion' => $completion->fresh(['quest', 'child']),
            'child_new_balance' => $completion->child->gold_balance,
            'level_ups' => $levelUpInfo,
        ]);
    }

    /**
     * Deny a quest completion.
     */
    public function deny(Request $request, $id)
    {
        $user = $request->user();

        $completion = QuestCompletion::with(['quest', 'child'])
            ->whereHas('quest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($id);

        // Check if already processed
        if (!$completion->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This quest completion has already been processed.',
            ], 400);
        }

        // Deny the completion
        $completion->deny($user);

        return response()->json([
            'success' => true,
            'message' => 'Quest completion denied.',
            'completion' => $completion->fresh(['quest', 'child']),
        ]);
    }

    /**
     * Get approval statistics.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'pending' => QuestCompletion::pending()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->count(),
            'accepted_today' => QuestCompletion::accepted()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->whereDate('approved_at', today())
                ->count(),
            'denied_today' => QuestCompletion::denied()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->whereDate('approved_at', today())
                ->count(),
            'total_accepted' => QuestCompletion::accepted()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Bulk accept multiple completions.
     */
    public function bulkAccept(Request $request)
    {
        $request->validate([
            'completion_ids' => 'required|array',
            'completion_ids.*' => 'integer|exists:quest_completions,id',
        ]);

        $user = $request->user();
        $acceptedCount = 0;
        $allLevelUps = [];

        \DB::transaction(function () use ($request, $user, &$acceptedCount, &$allLevelUps) {
            foreach ($request->completion_ids as $completionId) {
                $completion = QuestCompletion::lockForUpdate()
                    ->whereHas('quest', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->find($completionId);

                if ($completion && $completion->isPending()) {
                    $levelUpInfo = $completion->accept($user);
                    $acceptedCount++;

                    // Collect level up notifications
                    if (!empty($levelUpInfo['child_level_up']['leveled_up']) || !empty($levelUpInfo['trait_level_ups'])) {
                        $allLevelUps[] = [
                            'child_name' => $completion->child->name,
                            'quest_title' => $completion->quest->title,
                            'level_ups' => $levelUpInfo,
                        ];
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$acceptedCount} quest completion(s) accepted!",
            'accepted_count' => $acceptedCount,
            'level_ups' => $allLevelUps,
        ]);
    }

    /**
     * Bulk deny multiple completions.
     */
    public function bulkDeny(Request $request)
    {
        $request->validate([
            'completion_ids' => 'required|array',
            'completion_ids.*' => 'integer|exists:quest_completions,id',
        ]);

        $user = $request->user();
        $deniedCount = 0;

        \DB::transaction(function () use ($request, $user, &$deniedCount) {
            foreach ($request->completion_ids as $completionId) {
                $completion = QuestCompletion::lockForUpdate()
                    ->whereHas('quest', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->find($completionId);

                if ($completion && $completion->isPending()) {
                    $completion->deny($user);
                    $deniedCount++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$deniedCount} quest completion(s) denied.",
            'denied_count' => $deniedCount,
        ]);
    }
}
