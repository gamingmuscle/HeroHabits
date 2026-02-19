<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display the account settings page.
     */
    public function index(Request $request)
    {
        return view('parent.account', [
            'pageTitle' => 'Account Settings',
            'currentPage' => 'account'
        ]);
    }
}
