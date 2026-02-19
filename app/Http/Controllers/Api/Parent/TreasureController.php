<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\StoreTreasureRequest;
use App\Http\Requests\Parent\UpdateTreasureRequest;
use App\Models\Treasure;
use Illuminate\Http\Request;

class TreasureController extends Controller
{
    /**
     * Get all treasures for the authenticated parent.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $treasures = Treasure::where('user_id', $user->id)
            ->orderBy('is_available', 'desc')
            ->orderBy('gold_cost', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'treasures' => $treasures,
        ]);
    }

    /**
     * Store a new treasure.
     */
    public function store(StoreTreasureRequest $request)
    {
        $treasure = $request->user()->treasures()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Treasure created successfully!',
            'treasure' => $treasure,
        ], 201);
    }

    /**
     * Update an existing treasure.
     */
    public function update(UpdateTreasureRequest $request, $id)
    {
        $user = $request->user();

        $treasure = Treasure::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $treasure->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Treasure updated successfully!',
            'treasure' => $treasure->fresh(),
        ]);
    }

    /**
     * Delete a treasure.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $treasure = Treasure::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $treasure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Treasure deleted successfully!',
        ]);
    }

    /**
     * Toggle treasure availability.
     */
    public function toggle(Request $request, $id)
    {
        $user = $request->user();

        $treasure = Treasure::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $treasure->toggleAvailability();

        return response()->json([
            'success' => true,
            'message' => 'Treasure availability updated!',
            'treasure' => $treasure->fresh(),
        ]);
    }

    /**
     * Get purchase history for treasures.
     */
    public function purchases(Request $request)
    {
        $user = $request->user();

        // Get recent purchases across all treasures owned by this parent
        $purchases = $user->treasures()
            ->join('treasure_purchases', 'treasures.id', '=', 'treasure_purchases.treasure_id')
            ->join('children', 'treasure_purchases.child_id', '=', 'children.id')
            ->select(
                'treasure_purchases.id',
                'treasures.title as treasure_title',
                'children.name as child_name',
                'treasure_purchases.gold_spent',
                'treasure_purchases.purchased_at'
            )
            ->orderBy('treasure_purchases.purchased_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'purchases' => $purchases,
        ]);
    }
}
