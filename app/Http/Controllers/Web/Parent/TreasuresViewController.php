<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TreasuresViewController extends Controller
{
    /**
     * Display the treasures management page.
     */
    public function index(Request $request)
    {
        return view('parent.treasures', [
            'pageTitle' => 'Manage Treasures',
            'currentPage' => 'treasures'
        ]);
    }
}
