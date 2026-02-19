<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard data for the authenticated parent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all children with stats
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
            ->orderBy('name', 'asc')
            ->get();

        // Get total pending approvals
        $totalPending = QuestCompletion::pending()
            ->whereHas('quest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        // Get recent activity (last 10 approvals)
        $recentActivity = QuestCompletion::with(['quest:id,title', 'child:id,name'])
            ->whereHas('quest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('status', ['Accepted', 'Denied'])
            ->orderBy('approved_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'children' => $children,
                'total_pending' => $totalPending,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    /**
     * Get chart data for quest completions over time.
     */
    public function chartData(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 7); // Default 7 days

        // Validate period
        if (!in_array($period, [7, 30])) {
            $period = 7;
        }

        // Generate date labels
        $dateLabels = [];
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateLabels[] = $date->format('M j');
        }

        // Get all children
        $children = Child::where('user_id', $user->id)
            ->orderBy('name', 'asc')
            ->get();

        // Get quest completion data for each child
        $chartData = [];
        foreach ($children as $child) {
            $childData = [
                'id' => $child->id,
                'name' => $child->name,
                'data' => [],
            ];

            // For each date in the period, count accepted quests
            for ($i = $period - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();

                $count = QuestCompletion::where('child_id', $child->id)
                    ->where('completion_date', $date)
                    ->where('status', 'Accepted')
                    ->count();

                $childData['data'][] = $count;
            }

            $chartData[] = $childData;
        }

        return response()->json([
            'success' => true,
            'chart_data' => [
                'labels' => $dateLabels,
                'datasets' => $chartData,
            ],
        ]);
    }

    /**
     * Get summary statistics.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_children' => Child::where('user_id', $user->id)->count(),
            'total_quests' => $user->quests()->count(),
            'active_quests' => $user->quests()->active()->count(),
            'total_treasures' => $user->treasures()->count(),
            'available_treasures' => $user->treasures()->available()->count(),
            'pending_approvals' => QuestCompletion::pending()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->count(),
            'completed_today' => QuestCompletion::accepted()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->whereDate('completion_date', today())
                ->count(),
            'total_gold_distributed' => QuestCompletion::accepted()
                ->whereHas('quest', fn($q) => $q->where('user_id', $user->id))
                ->sum('gold_earned'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Get leaderboard data for children.
     */
    public function leaderboard(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 'all_time'); // all_time, this_month, this_week

        $query = Child::where('user_id', $user->id);

        // Add quest completion counts based on period
        $query->withCount(['questCompletions as quests_completed' => function ($q) use ($period) {
            $q->where('status', 'Accepted');

            if ($period === 'this_week') {
                $q->where('completion_date', '>=', now()->startOfWeek());
            } elseif ($period === 'this_month') {
                $q->where('completion_date', '>=', now()->startOfMonth());
            }
        }]);

        // Add total gold earned based on period
        $query->addSelect([
            'gold_earned' => QuestCompletion::select(DB::raw('COALESCE(SUM(gold_earned), 0)'))
                ->whereColumn('child_id', 'children.id')
                ->where('status', 'Accepted')
                ->when($period === 'this_week', function ($q) {
                    $q->where('completion_date', '>=', now()->startOfWeek());
                })
                ->when($period === 'this_month', function ($q) {
                    $q->where('completion_date', '>=', now()->startOfMonth());
                })
        ]);

        $children = $query->orderBy('quests_completed', 'desc')
            ->orderBy('gold_earned', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'leaderboard' => $children,
            'period' => $period,
        ]);
    }
}
