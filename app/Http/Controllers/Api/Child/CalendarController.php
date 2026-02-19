<?php

namespace App\Http\Controllers\Api\Child;

use App\Http\Controllers\Controller;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Get calendar data for a specific month.
     */
    public function show(Request $request, $year, $month)
    {
        $child = $request->user();

        // Validate year and month
        if (!checkdate($month, 1, $year)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid year or month.',
            ], 400);
        }

        // Get accepted completions for the month
        $completions = QuestCompletion::where('child_id', $child->id)
            ->where('status', 'Accepted')
            ->whereYear('completion_date', $year)
            ->whereMonth('completion_date', $month)
            ->selectRaw('completion_date, COUNT(*) as quest_count, SUM(gold_earned) as total_gold')
            ->groupBy('completion_date')
            ->get()
            ->keyBy(function ($item) {
                return date('j', strtotime($item->completion_date));
            });

        // Get pending count for the entire period
        $pendingCount = QuestCompletion::where('child_id', $child->id)
            ->where('status', 'Pending')
            ->count();

        // Calculate calendar metadata
        $firstDayOfMonth = date('w', strtotime("$year-$month-01"));
        $totalDays = date('t', strtotime("$year-$month-01"));
        $monthName = date('F Y', strtotime("$year-$month-01"));

        return response()->json([
            'success' => true,
            'calendar' => [
                'year' => (int)$year,
                'month' => (int)$month,
                'month_name' => $monthName,
                'first_day_of_month' => $firstDayOfMonth,
                'total_days' => $totalDays,
                'completions' => $completions,
                'pending_count' => $pendingCount,
            ],
        ]);
    }

    /**
     * Get current month calendar data (convenience method).
     */
    public function current(Request $request)
    {
        $year = date('Y');
        $month = date('m');

        return $this->show($request, $year, $month);
    }

    /**
     * Get calendar data for a date range.
     */
    public function range(Request $request)
    {
        $child = $request->user();

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $completions = QuestCompletion::where('child_id', $child->id)
            ->where('status', 'Accepted')
            ->whereBetween('completion_date', [$request->start_date, $request->end_date])
            ->with('quest:id,title')
            ->orderBy('completion_date', 'desc')
            ->get();

        $summary = [
            'total_quests' => $completions->count(),
            'total_gold' => $completions->sum('gold_earned'),
            'unique_days' => $completions->pluck('completion_date')->unique()->count(),
        ];

        return response()->json([
            'success' => true,
            'completions' => $completions,
            'summary' => $summary,
        ]);
    }

    /**
     * Get daily summary for a specific date.
     */
    public function day(Request $request, $year, $month, $day)
    {
        $child = $request->user();

        // Validate date
        if (!checkdate($month, $day, $year)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date.',
            ], 400);
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $completions = QuestCompletion::where('child_id', $child->id)
            ->where('completion_date', $date)
            ->with('quest:id,title,gold_reward')
            ->get();

        $summary = [
            'date' => $date,
            'total_quests' => $completions->count(),
            'total_gold' => $completions->sum('gold_earned'),
            'accepted' => $completions->where('status', 'Accepted')->count(),
            'pending' => $completions->where('status', 'Pending')->count(),
            'denied' => $completions->where('status', 'Denied')->count(),
        ];

        return response()->json([
            'success' => true,
            'completions' => $completions,
            'summary' => $summary,
        ]);
    }
}
