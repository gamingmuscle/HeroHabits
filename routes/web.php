<?php

use App\Http\Controllers\Auth\ParentAuthController;
use App\Http\Controllers\Auth\ChildAuthController;
use App\Http\Controllers\Web\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Web\Parent\QuestsViewController as ParentQuestsViewController;
use App\Http\Controllers\Web\Parent\TreasuresViewController as ParentTreasuresViewController;
use App\Http\Controllers\Web\Parent\ProfilesViewController as ParentProfilesViewController;
use App\Http\Controllers\Web\Parent\ApprovalsViewController as ParentApprovalsViewController;
use App\Http\Controllers\Web\Parent\ChildHistoryController;
use App\Http\Controllers\Web\Parent\AccountController as ParentAccountController;
use App\Http\Controllers\Web\Child\QuestsViewController as ChildQuestsViewController;
use App\Http\Controllers\Web\Child\TreasuresViewController as ChildTreasuresViewController;
use App\Http\Controllers\Web\Child\CalendarViewController as ChildCalendarViewController;
use App\Http\Controllers\Web\Child\CharacterViewController as ChildCharacterViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Landing page - show welcome/splash page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Default login route (required by Laravel auth middleware)
Route::get('/login', function () {
    return redirect()->route('parent.login');
})->name('login');

// Default home route (redirect to parent dashboard)
Route::get('/home', function () {
    return redirect()->route('parent.dashboard');
})->middleware('auth')->name('home');

/*
|--------------------------------------------------------------------------
| Parent Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('parent')->name('parent.')->group(function () {
    // Guest routes (not authenticated)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [ParentAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [ParentAuthController::class, 'login']);
        Route::get('/register', [ParentAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [ParentAuthController::class, 'register']);
    });

    // Authenticated routes
    Route::middleware(['auth', 'parent'])->group(function () {
        Route::post('/logout', [ParentAuthController::class, 'logout'])->name('logout');

        // Parent portal pages
        Route::get('/dashboard', [ParentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/quests', [ParentQuestsViewController::class, 'index'])->name('quests');
        Route::get('/treasures', [ParentTreasuresViewController::class, 'index'])->name('treasures');
        Route::get('/profiles', [ParentProfilesViewController::class, 'index'])->name('profiles');
        Route::get('/approvals', [ParentApprovalsViewController::class, 'index'])->name('approvals');
        Route::get('/children/{id}/history', [ChildHistoryController::class, 'show'])->name('child-history');
        Route::get('/account', [ParentAccountController::class, 'index'])->name('account');
    });
});

/*
|--------------------------------------------------------------------------
| Child Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('child')->name('child.')->group(function () {
    // Public route for getting all children (for login page)
    Route::get('/all', [ChildAuthController::class, 'getAllChildren'])->name('all');

    // Guest routes (not authenticated)
    Route::middleware('guest:child')->group(function () {
        Route::get('/login', [ChildAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [ChildAuthController::class, 'login']);
    });

    // Authenticated routes
    Route::middleware(['child'])->group(function () {
        Route::post('/logout', [ChildAuthController::class, 'logout'])->name('logout');

        // Child portal pages
        Route::get('/character', [ChildCharacterViewController::class, 'index'])->name('character');
        Route::get('/quests', [ChildQuestsViewController::class, 'index'])->name('quests');
        Route::get('/journeys', [ChildCalendarViewController::class, 'index'])->name('quests');//TEMPORARY
        Route::get('/treasures', [ChildTreasuresViewController::class, 'index'])->name('treasures');
        
    });
});
