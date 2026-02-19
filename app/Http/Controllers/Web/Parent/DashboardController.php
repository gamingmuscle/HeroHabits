<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the parent dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get chart period from query parameter (default to 7 days)
        $chartPeriod = $request->get('period', 7);
        if (!in_array($chartPeriod, [7, 30])) {
            $chartPeriod = 7;
        }

        // Get all children with their stats
        $children = Child::where('user_id', $user->id)
            ->withCount([
                'questCompletions as completed_quests' => function ($query) {
                    $query->where('status', 'Accepted');
                },
                'questCompletions as pending_approvals' => function ($query) {
                    $query->where('status', 'Pending');
                },
                'questCompletions as completed_today' => function ($query) {
                    $query->where('status', 'Accepted')
                          ->where('completion_date', today());
                }
            ])
            ->orderBy('name', 'asc')
            ->get();

        // Get total pending approvals across all children
        $totalPending = QuestCompletion::whereHas('child', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('status', 'Pending')
        ->count();

        // Prepare chart data
        $chartData = [];
        $dateLabels = [];

        // Generate date labels for the period
        for ($i = $chartPeriod - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateLabels[] = $date->format('M j');
        }

        // Get quest completion data for each child
        foreach ($children as $child) {
            $childData = [
                'id' => $child->id,
                'name' => $child->name,
                'data' => []
            ];

            // For each date in the period, count accepted quests
            for ($i = $chartPeriod - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');

                $count = QuestCompletion::where('child_id', $child->id)
                    ->where('completion_date', $date)
                    ->where('status', 'Accepted')
                    ->count();

                $childData['data'][] = $count;
            }

            $chartData[] = $childData;
        }

        return view('parent.dashboard', [
            'pageTitle' => 'Dashboard',
            'currentPage' => 'dashboard',
            'children' => $children,
            'totalPending' => $totalPending,
            'chartData' => $chartData,
            'dateLabels' => $dateLabels,
            'chartPeriod' => $chartPeriod
        ]);
    }
}
