<?php

namespace App\Http\Controllers\Api\Child;

use App\Http\Controllers\Controller;
use App\Models\Treasure;
use App\Models\TreasurePurchase;
use Illuminate\Http\Request;

class TreasureController extends Controller
{
    /**
     * Get all available treasures for the child.
     */
    public function index(Request $request)
    {
        $child = \Auth::guard('child')->user();  // explicitly uses 'child' guard

        // Get parent's available treasures
        $treasures = Treasure::where('user_id', $child->user_id)
            ->available()
            ->orderBy('gold_cost', 'asc')
            ->get();

        // Add affordability flag for each treasure
        $treasures->each(function ($treasure) use ($child) {
            $treasure->can_afford = $child->gold_balance >= $treasure->gold_cost;
        });

        return response()->json([
            'success' => true,
            'treasures' => $treasures,
            'child_gold_balance' => $child->gold_balance,
        ]);
    }

    /**
     * Purchase a treasure.
     */
    public function purchase(Request $request, $id)
    {
        $child = \Auth::guard('child')->user();  // explicitly uses 'child' guard

        // Get the treasure
        $treasure = Treasure::where('id', $id)
            ->where('user_id', $child->user_id)
            ->available()
            ->firstOrFail();

        // Check if child has enough gold
        if (!$child->hasEnoughGold($treasure->gold_cost)) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold! You need ' . $treasure->gold_cost . ' gold but only have ' . $child->gold_balance . ' gold.',
                'required' => $treasure->gold_cost,
                'available' => $child->gold_balance,
                'short' => $treasure->gold_cost - $child->gold_balance,
            ], 400);
        }

        // Attempt to purchase
        if ($treasure->purchaseFor($child)) {
            // Refresh child to get updated balance
            $child->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Treasure purchased successfully!',
                'treasure' => $treasure,
                'gold_spent' => $treasure->gold_cost,
                'new_balance' => $child->gold_balance,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to purchase treasure. Please try again.',
        ], 500);
    }

    /**
     * Get purchase history for the child.
     */
    public function purchases(Request $request)
    {
        $child = \Auth::guard('child')->user();  // explicitly uses 'child' guard
        $limit = $request->get('limit', 20);

        $purchases = TreasurePurchase::where('child_id', $child->id)
            ->with('treasure:id,title,description,gold_cost')
            ->orderBy('purchased_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'purchases' => $purchases,
        ]);
    }

    /**
     * Get treasure purchase stats for the child.
     */
    public function stats(Request $request)
    {
        $child = \Auth::guard('child')->user();  // explicitly uses 'child' guard

        $stats = [
            'total_purchases' => TreasurePurchase::where('child_id', $child->id)->count(),
            'total_gold_spent' => TreasurePurchase::where('child_id', $child->id)->sum('gold_spent'),
            'most_recent_purchase' => TreasurePurchase::where('child_id', $child->id)
                ->with('treasure:id,title')
                ->orderBy('purchased_at', 'desc')
                ->first(),
            'purchases_this_month' => TreasurePurchase::where('child_id', $child->id)
                ->where('purchased_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Get a specific treasure with details.
     */
    public function show(Request $request, $id)
    {
        $child = \Auth::guard('child')->user();  // explicitly uses 'child' guard

        $treasure = Treasure::where('id', $id)
            ->where('user_id', $child->user_id)
            ->firstOrFail();

        $treasure->can_afford = $child->gold_balance >= $treasure->gold_cost;
        $treasure->times_purchased = TreasurePurchase::where('child_id', $child->id)
            ->where('treasure_id', $treasure->id)
            ->count();

        return response()->json([
            'success' => true,
            'treasure' => $treasure,
            'child_gold_balance' => $child->gold_balance,
        ]);
    }
}
