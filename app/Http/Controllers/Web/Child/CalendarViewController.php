<?php

namespace App\Http\Controllers\Web\Child;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CalendarViewController extends Controller
{
    /**
     * Display the child calendar page.
     */
    public function index(Request $request)
    {
        // Use the 'child' guard to get the authenticated child
        $child = \Auth::guard('child')->user();

        return view('child.calendar', [
            'pageTitle' => 'My Calendar',
            'currentPage' => 'calendar',
            'child' => $child
        ]);
    }
}
