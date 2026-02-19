<?php

namespace App\Http\Controllers\Web\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TreasuresViewController extends Controller
{
    /**
     * Display the child treasures page.
     */
    public function index(Request $request)
    {
        // Use the 'child' guard to get the authenticated child
        $child = \Auth::guard('child')->user();

        return view('child.treasures', [
            'pageTitle' => 'Treasure Shop',
            'currentPage' => 'treasures',
            'child' => $child
        ]);
    }
}
