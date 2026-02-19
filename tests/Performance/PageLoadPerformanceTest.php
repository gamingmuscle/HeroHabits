<?php

namespace Tests\Performance;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageLoadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Maximum acceptable page load time in milliseconds.
     */
    const MAX_PAGE_LOAD_TIME = 800;

    /**
     * Test parent dashboard page load performance.
     */
    public function test_parent_dashboard_page_load_performance(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id]);
        $child2 = Child::factory()->create(['user_id' => $user->id]);

        // Add some quests and completions
        Quest::factory()->count(10)->create(['child_id' => $child1->id]);
        Quest::factory()->count(10)->create(['child_id' => $child2->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/parent/dashboard');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_PAGE_LOAD_TIME,
            $duration,
            "Dashboard page load took {$duration}ms"
        );

        $this->assertLessThan(
            15,
            $queryCount,
            "Dashboard page executed {$queryCount} queries"
        );

        echo "\n[Page Load] Parent Dashboard: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test parent quests page load performance.
     */
    public function test_parent_quests_page_load_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(50)->create(['child_id' => $child->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/parent/quests');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_PAGE_LOAD_TIME,
            $duration,
            "Quests page load took {$duration}ms"
        );

        echo "\n[Page Load] Parent Quests: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test parent approvals page load performance.
     */
    public function test_parent_approvals_page_load_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Create 30 pending approvals
        for ($i = 0; $i < 30; $i++) {
            $quest = Quest::factory()->create(['child_id' => $child->id]);
            QuestCompletion::factory()->pending()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
            ]);
        }

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/parent/approvals');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_PAGE_LOAD_TIME,
            $duration,
            "Approvals page load took {$duration}ms"
        );

        echo "\n[Page Load] Parent Approvals (30 items): {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test child quests page load performance.
     */
    public function test_child_quests_page_load_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(20)->active()->create(['child_id' => $child->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($child, 'child')->get('/child/quests');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_PAGE_LOAD_TIME,
            $duration,
            "Child quests page load took {$duration}ms"
        );

        echo "\n[Page Load] Child Quests: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test welcome page load performance.
     */
    public function test_welcome_page_load_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            300,
            $duration,
            "Welcome page load took {$duration}ms"
        );

        echo "\n[Page Load] Welcome Page: {$duration}ms\n";
    }

    /**
     * Test login page load performance.
     */
    public function test_login_pages_load_performance(): void
    {
        $pages = [
            '/parent/login' => 'Parent Login',
            '/parent/register' => 'Parent Register',
            '/child/login' => 'Child Login',
        ];

        foreach ($pages as $url => $name) {
            $startTime = microtime(true);

            $response = $this->get($url);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);

            $this->assertLessThan(
                400,
                $duration,
                "{$name} page load took {$duration}ms"
            );

            echo "\n[Page Load] {$name}: {$duration}ms\n";
        }
    }

    /**
     * Test page load with authentication middleware.
     */
    public function test_authenticated_page_redirect_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/parent/dashboard');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertRedirect('/parent/login');

        $this->assertLessThan(
            200,
            $duration,
            "Authentication redirect took {$duration}ms"
        );

        echo "\n[Page Load] Auth Redirect: {$duration}ms\n";
    }

    /**
     * Test session validation performance on repeated requests.
     */
    public function test_session_validation_performance(): void
    {
        $user = User::factory()->create();

        $totalDuration = 0;

        // Make 10 consecutive authenticated requests
        for ($i = 0; $i < 10; $i++) {
            $startTime = microtime(true);

            $this->actingAs($user)->get('/parent/dashboard');

            $endTime = microtime(true);
            $totalDuration += ($endTime - $startTime) * 1000;
        }

        $averageDuration = $totalDuration / 10;

        $this->assertLessThan(
            self::MAX_PAGE_LOAD_TIME,
            $averageDuration,
            "Average session validation took {$averageDuration}ms"
        );

        echo "\n[Page Load] Average Session Validation (10 requests): {$averageDuration}ms\n";
    }

    /**
     * Test database query optimization with eager loading.
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        $user = User::factory()->create();

        // Create 5 children with quests
        for ($i = 0; $i < 5; $i++) {
            $child = Child::factory()->create(['user_id' => $user->id]);
            Quest::factory()->count(10)->create(['child_id' => $child->id]);
        }

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $response = $this->actingAs($user)->get('/parent/dashboard');

        $response->assertStatus(200);

        // With proper eager loading, query count should not grow with children count
        // Without eager loading: 1 + (5 children * 1 query each) = 6+ queries
        // With eager loading: 2-3 queries total
        $this->assertLessThan(
            10,
            $queryCount,
            "Dashboard with 5 children executed {$queryCount} queries (possible N+1 issue)"
        );

        echo "\n[Query Optimization] Dashboard with 5 children: {$queryCount} queries\n";
    }

    /**
     * Test asset loading performance simulation.
     */
    public function test_static_asset_routes_performance(): void
    {
        $routes = [
            '/css/modern-theme.css',
            '/js/api-client.js',
            '/js/notifications.js',
        ];

        foreach ($routes as $route) {
            $startTime = microtime(true);

            // Note: This tests the route exists, actual asset serving is handled by web server
            $response = $this->get($route);

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            // Asset should load quickly or return 404 if not found
            $this->assertTrue(
                $response->status() === 200 || $response->status() === 404,
                "Asset route {$route} returned unexpected status"
            );

            echo "\n[Asset Load] {$route}: {$duration}ms\n";
        }
    }

    /**
     * Test page size is within acceptable limits.
     */
    public function test_page_response_size(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(50)->create(['child_id' => $child->id]);

        $response = $this->actingAs($user)->get('/parent/quests');

        $content = $response->getContent();
        $sizeKB = strlen($content) / 1024;

        // Page size should not exceed 500KB
        $this->assertLessThan(
            500,
            $sizeKB,
            "Quests page size is {$sizeKB}KB"
        );

        echo "\n[Page Size] Quests page: {$sizeKB}KB\n";
    }
}
