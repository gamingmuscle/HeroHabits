<?php

use App\Http\Controllers\Auth\ParentAuthController;
use App\Http\Controllers\Auth\ChildAuthController;
use App\Http\Controllers\Api\Parent\QuestController as ParentQuestController;
use App\Http\Controllers\Api\Parent\TreasureController as ParentTreasureController;
use App\Http\Controllers\Api\Parent\ChildController as ParentChildController;
use App\Http\Controllers\Api\Parent\ApprovalController;
use App\Http\Controllers\Api\Parent\DashboardController;
use App\Http\Controllers\Api\TraitController;
use App\Http\Controllers\Api\Child\QuestController as ChildQuestController;
use App\Http\Controllers\Api\Child\TreasureController as ChildTreasureController;
use App\Http\Controllers\Api\Child\CalendarController;
use App\Http\Controllers\Api\Child\TraitController as ChildTraitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Health Check Endpoint
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Parent API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('parent')->name('api.parent.')->group(function () {
    // Authentication endpoints (stricter rate limit for auth)
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [ParentAuthController::class, 'register'])->name('register');
        Route::post('/login', [ParentAuthController::class, 'login'])->name('login');
    });

    // Authenticated endpoints (standard rate limit: 60 requests per minute)
    Route::middleware(['auth', 'parent', 'throttle:60,1'])->group(function () {
        Route::post('/logout', [ParentAuthController::class, 'logout'])->name('logout');
        Route::get('/session/time-remaining', [ParentAuthController::class, 'getSessionTimeRemaining'])->name('session.time');
        Route::post('/session/refresh', [ParentAuthController::class, 'refreshSession'])->name('session.refresh');

        // Quest management
        Route::get('/quests', [ParentQuestController::class, 'index'])->name('quests.index');
        Route::post('/quests', [ParentQuestController::class, 'store'])->name('quests.store');
        Route::get('/quests/{id}', [ParentQuestController::class, 'show'])->name('quests.show');
        Route::put('/quests/{id}', [ParentQuestController::class, 'update'])->name('quests.update');
        Route::delete('/quests/{id}', [ParentQuestController::class, 'destroy'])->name('quests.destroy');
        Route::post('/quests/{id}/toggle', [ParentQuestController::class, 'toggle'])->name('quests.toggle');

        // Treasure management
        Route::get('/treasures', [ParentTreasureController::class, 'index'])->name('treasures.index');
        Route::post('/treasures', [ParentTreasureController::class, 'store'])->name('treasures.store');
        Route::put('/treasures/{id}', [ParentTreasureController::class, 'update'])->name('treasures.update');
        Route::delete('/treasures/{id}', [ParentTreasureController::class, 'destroy'])->name('treasures.destroy');
        Route::post('/treasures/{id}/toggle', [ParentTreasureController::class, 'toggle'])->name('treasures.toggle');
        Route::get('/treasures/purchases', [ParentTreasureController::class, 'purchases'])->name('treasures.purchases');

        // Child profile management
        Route::get('/children', [ParentChildController::class, 'index'])->name('children.index');
        Route::post('/children', [ParentChildController::class, 'store'])->name('children.store');
        Route::get('/children/avatars', [ParentChildController::class, 'avatars'])->name('children.avatars');
        Route::get('/children/{id}', [ParentChildController::class, 'show'])->name('children.show');
        Route::put('/children/{id}', [ParentChildController::class, 'update'])->name('children.update');
        Route::delete('/children/{id}', [ParentChildController::class, 'destroy'])->name('children.destroy');
        Route::get('/children/{id}/quest-history', [ParentChildController::class, 'questHistory'])->name('children.quest-history');

        // Approval management
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{id}/accept', [ApprovalController::class, 'accept'])->name('approvals.accept');
        Route::post('/approvals/{id}/deny', [ApprovalController::class, 'deny'])->name('approvals.deny');
        Route::get('/approvals/stats', [ApprovalController::class, 'stats'])->name('approvals.stats');
        Route::post('/approvals/bulk-accept', [ApprovalController::class, 'bulkAccept'])->name('approvals.bulk-accept');
        Route::post('/approvals/bulk-deny', [ApprovalController::class, 'bulkDeny'])->name('approvals.bulk-deny');

        // Dashboard data
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/leaderboard', [DashboardController::class, 'leaderboard'])->name('dashboard.leaderboard');

        // Trait management
        Route::get('/traits', [TraitController::class, 'index'])->name('traits.index');
        Route::get('/children/{id}/traits', [TraitController::class, 'childTraits'])->name('children.traits');
    });
});

/*
|--------------------------------------------------------------------------
| Child API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('child')->name('api.child.')->group(function () {
    // Authentication endpoints (stricter rate limit for auth)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/login', [ChildAuthController::class, 'login'])->name('login');
    });

    // Authenticated endpoints (standard rate limit: 60 requests per minute)
    Route::middleware(['child', 'throttle:60,1'])->group(function () {
        Route::post('/logout', [ChildAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [ChildAuthController::class, 'profile'])->name('profile');

        // Traits
        Route::get('/traits', [ChildTraitController::class, 'index'])->name('traits.index');

        // Quest completion
        Route::get('/quests', [ChildQuestController::class, 'index'])->name('quests.index');
        Route::post('/quests/{id}/complete', [ChildQuestController::class, 'complete'])->name('quests.complete');
        Route::get('/quests/history', [ChildQuestController::class, 'history'])->name('quests.history');
        Route::get('/quests/pending', [ChildQuestController::class, 'pending'])->name('quests.pending');
        Route::get('/quests/stats', [ChildQuestController::class, 'stats'])->name('quests.stats');
        

        // Treasure purchase
        Route::get('/treasures', [ChildTreasureController::class, 'index'])->name('treasures.index');
        Route::get('/treasures/{id}', [ChildTreasureController::class, 'show'])->name('treasures.show');
        Route::post('/treasures/{id}/purchase', [ChildTreasureController::class, 'purchase'])->name('treasures.purchase');
        Route::get('/treasures/purchases/history', [ChildTreasureController::class, 'purchases'])->name('treasures.purchases');
        Route::get('/treasures/purchases/stats', [ChildTreasureController::class, 'stats'])->name('treasures.stats');

        // Calendar data
        Route::get('/calendar/current', [CalendarController::class, 'current'])->name('calendar.current');
        Route::get('/calendar/{year}/{month}', [CalendarController::class, 'show'])->name('calendar.show');
        Route::get('/calendar/{year}/{month}/{day}', [CalendarController::class, 'day'])->name('calendar.day');
        Route::get('/calendar/range', [CalendarController::class, 'range'])->name('calendar.range');
    });
});
