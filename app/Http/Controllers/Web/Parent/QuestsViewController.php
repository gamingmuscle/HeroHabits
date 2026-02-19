<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuestsViewController extends Controller
{
    /**
     * Display the quests management page.
     */
    public function index(Request $request)
    {
        return view('parent.quests', [
            'pageTitle' => 'Manage Quests',
            'currentPage' => 'quests'
        ]);
    }
}
