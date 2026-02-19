<?php

namespace App\Http\Controllers\Web\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CharacterViewController extends Controller
{
    /**
     * Display the child's character sheet.
     */
    public function index(Request $request)
    {
        // Use the 'child' guard to get the authenticated child
        $child = \Auth::guard('child')->user();

        if (!$child) {
            return redirect()->route('child.login');
        }

        return view('child.character', [
            'pageTitle' => 'My Character',
            'currentPage' => 'character',
            'child' => $child
        ]);
    }
}
