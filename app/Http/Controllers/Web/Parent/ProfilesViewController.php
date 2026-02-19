<?php

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfilesViewController extends Controller
{
    /**
     * Display the profiles management page.
     */
    public function index(Request $request)
    {
        return view('parent.profiles', [
            'pageTitle' => 'Manage Profiles',
            'currentPage' => 'profiles'
        ]);
    }
}
