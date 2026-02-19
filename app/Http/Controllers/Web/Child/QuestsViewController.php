<?php

namespace App\Http\Controllers\Web\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuestsViewController extends Controller
{
    /**
     * Display the child quests page.
     */
    public function index(Request $request)
    {
        // Use the 'child' guard to get the authenticated child
        $child = \Auth::guard('child')->user();

        return view('child.quests', [
            'pageTitle' => 'My Quests',
            'currentPage' => 'quests',
            'child' => $child
        ]);
    }
}
