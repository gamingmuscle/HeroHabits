<?php

namespace App\Http\Controllers\Api\Child;

use App\Http\Controllers\Controller;
use App\Http\Requests\Child\CompleteQuestRequest;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    /**
     * Get all active quests available to the child.
     */
    public function index(Request $request)
    {
        // Use the 'child' guard to get the authenticated child
        $child = \Auth::guard('child')->user();

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => 'Child not authenticated',
            ], 401);
        }

        // Get the parent's active quests
        $quests = Quest::where('user_id', $child->user_id)
            ->active()
            ->with(['completions' => function ($query) use ($child) {
                $query->where('child_id', $child->id)
                    ->where('completion_date', today());
            }])
            ->orderBy('title', 'asc')
            ->get();

        // Add completion status for today
        $quests->each(function ($quest) use ($child) {
            $todayCompletion = $quest->completions->first();
            $quest->completed_today = $todayCompletion !== null;
            $quest->today_status = $todayCompletion ? $todayCompletion->status : null;
            unset($quest->completions); // Remove the completions collection
        });

        return response()->json([
            'success' => true,
            'quests' => $quests,
        ]);
    }

    /**
     * Complete a quest for the current date.
     */
    public function complete(CompleteQuestRequest $request, $id)
    {
        $child = \Auth::guard('child')->user();

        // Get the quest
        $quest = Quest::where('id', $id)
            ->where('user_id', $child->user_id)
            ->active()
            ->firstOrFail();

        // Check if already completed today
        $existingCompletion = QuestCompletion::where('quest_id', $quest->id)
            ->where('child_id', $child->id)
            ->where('completion_date', today())
            ->first();

        if ($existingCompletion) {
            return response()->json([
                'success' => false,
                'message' => 'You have already completed this quest today!',
                'status' => $existingCompletion->status,
            ], 400);
        }

        // Create the completion
        $completion = QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completion_date' => today(),
            'gold_earned' => $quest->gold_reward,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quest submitted for approval! You will earn ' . $quest->gold_reward . ' gold when approved.',
            'completion' => $completion,
        ], 201);
    }

    /**
     * Get quest completion history for the child.
     */
    public function history(Request $request)
    {
        $child = \Auth::guard('child')->user();
        $limit = $request->get('limit', 20);

        $completions = QuestCompletion::where('child_id', $child->id)
            ->with('quest:id,title,gold_reward')
            ->orderBy('completion_date', 'desc')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'completions' => $completions,
        ]);
    }

    /**
     * Get pending quest completions for the child.
     */
    public function pending(Request $request)
    {
        $child = \Auth::guard('child')->user();

        $pending = QuestCompletion::where('child_id', $child->id)
            ->pending()
            ->with('quest:id,title,gold_reward')
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'pending' => $pending,
            'count' => $pending->count(),
        ]);
    }

    /**
     * Get quest completion stats for the child.
     */
    public function stats(Request $request)
    {
        $child = \Auth::guard('child')->user();

        $stats = [
            'total_completed' => QuestCompletion::where('child_id', $child->id)
                ->accepted()
                ->count(),
            'pending' => QuestCompletion::where('child_id', $child->id)
                ->pending()
                ->count(),
            'completed_today' => QuestCompletion::where('child_id', $child->id)
                ->where('completion_date', today())
                ->accepted()
                ->count(),
            'completed_this_week' => QuestCompletion::where('child_id', $child->id)
                ->where('completion_date', '>=', now()->startOfWeek())
                ->accepted()
                ->count(),
            'total_gold_earned' => QuestCompletion::where('child_id', $child->id)
                ->accepted()
                ->sum('gold_earned'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
