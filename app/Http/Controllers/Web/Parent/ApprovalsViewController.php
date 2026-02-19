<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApprovalsViewController extends Controller
{
    /**
     * Display the approvals page.
     */
    public function index(Request $request)
    {
        return view('parent.approvals', [
            'pageTitle' => 'Approvals',
            'currentPage' => 'approvals'
        ]);
    }
}
