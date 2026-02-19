<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildHistoryController extends Controller
{
    /**
     * Display the child quest history page.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Get the child (ensure ownership)
        $child = Child::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('parent.child-history', [
            'pageTitle' => $child->name . ' - Quest History',
            'currentPage' => 'dashboard',
            'child' => $child
        ]);
    }
}
