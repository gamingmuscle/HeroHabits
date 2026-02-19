<?php

namespace Tests\Performance;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class APIPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Maximum acceptable response time in milliseconds.
     */
    const MAX_RESPONSE_TIME = 500;

    /**
     * Maximum acceptable database queries per request.
     */
    const MAX_QUERIES = 15;

    /**
     * Test parent quest list API performance.
     */
    public function test_parent_quest_list_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(50)->create(['child_id' => $child->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)->getJson('/api/parent/quests');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Assert response time is acceptable
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME,
            $duration,
            "Quest list API took {$duration}ms, expected < " . self::MAX_RESPONSE_TIME . "ms"
        );

        // Assert query count is reasonable
        $this->assertLessThan(
            self::MAX_QUERIES,
            $queryCount,
            "Quest list API executed {$queryCount} queries, expected < " . self::MAX_QUERIES
        );

        echo "\n[Performance] Parent Quest List: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test child quest list API performance.
     */
    public function test_child_quest_list_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(30)->active()->create(['child_id' => $child->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($child, 'child')->getJson('/api/child/quests');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_RESPONSE_TIME,
            $duration,
            "Child quest list took {$duration}ms"
        );

        $this->assertLessThan(
            self::MAX_QUERIES,
            $queryCount,
            "Child quest list executed {$queryCount} queries"
        );

        echo "\n[Performance] Child Quest List: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test quest completion API performance.
     */
    public function test_quest_completion_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->active()->create(['child_id' => $child->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_RESPONSE_TIME,
            $duration,
            "Quest completion took {$duration}ms"
        );

        $this->assertLessThan(
            10,
            $queryCount,
            "Quest completion executed {$queryCount} queries"
        );

        echo "\n[Performance] Quest Completion: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test approval list API performance with many pending items.
     */
    public function test_approval_list_performance_with_many_items(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Create 100 pending completions
        for ($i = 0; $i < 100; $i++) {
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

        $response = $this->actingAs($user)->getJson('/api/parent/approvals');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            1000, // Allow more time for large datasets
            $duration,
            "Approval list with 100 items took {$duration}ms"
        );

        $this->assertLessThan(
            20,
            $queryCount,
            "Approval list executed {$queryCount} queries (possible N+1 issue)"
        );

        echo "\n[Performance] Approval List (100 items): {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test bulk approval performance.
     */
    public function test_bulk_approval_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $completionIds = [];
        for ($i = 0; $i < 20; $i++) {
            $quest = Quest::factory()->create(['child_id' => $child->id]);
            $completion = QuestCompletion::factory()->pending()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
            ]);
            $completionIds[] = $completion->id;
        }

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->postJson('/api/parent/approvals/bulk-accept', [
                'completion_ids' => $completionIds,
            ]);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            1000,
            $duration,
            "Bulk approval of 20 items took {$duration}ms"
        );

        // Bulk operations should be optimized with fewer queries
        $this->assertLessThan(
            30,
            $queryCount,
            "Bulk approval executed {$queryCount} queries"
        );

        echo "\n[Performance] Bulk Approval (20 items): {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test quest creation performance.
     */
    public function test_quest_creation_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($user)->postJson('/api/parent/quests', [
            'child_id' => $child->id,
            'title' => 'Performance Test Quest',
            'description' => 'Testing creation performance',
            'gold_reward' => 10,
            'is_active' => true,
        ]);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);

        $this->assertLessThan(
            300,
            $duration,
            "Quest creation took {$duration}ms"
        );

        echo "\n[Performance] Quest Creation: {$duration}ms\n";
    }

    /**
     * Test children list performance.
     */
    public function test_children_list_performance(): void
    {
        $user = User::factory()->create();
        Child::factory()->count(10)->create(['user_id' => $user->id]);

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($user)->getJson('/api/parent/children');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_RESPONSE_TIME,
            $duration,
            "Children list took {$duration}ms"
        );

        $this->assertLessThan(
            5,
            $queryCount,
            "Children list executed {$queryCount} queries"
        );

        echo "\n[Performance] Children List: {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test quest history performance.
     */
    public function test_quest_history_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create(['child_id' => $child->id]);

        // Create 50 historical completions
        for ($i = 0; $i < 50; $i++) {
            QuestCompletion::factory()->accepted()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
                'completed_date' => now()->subDays($i)->format('Y-m-d'),
            ]);
        }

        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });

        $startTime = microtime(true);

        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/quests/history');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_RESPONSE_TIME,
            $duration,
            "Quest history took {$duration}ms"
        );

        $this->assertLessThan(
            10,
            $queryCount,
            "Quest history executed {$queryCount} queries"
        );

        echo "\n[Performance] Quest History (50 items): {$duration}ms, {$queryCount} queries\n";
    }

    /**
     * Test memory usage stays within acceptable limits.
     */
    public function test_memory_usage_with_large_dataset(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Create large dataset
        Quest::factory()->count(200)->create(['child_id' => $child->id]);

        $memoryBefore = memory_get_usage();

        $response = $this->actingAs($user)->getJson('/api/parent/quests');

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $response->assertStatus(200);

        // Memory usage should not exceed 10MB for this operation
        $this->assertLessThan(
            10,
            $memoryUsed,
            "Quest list used {$memoryUsed}MB of memory"
        );

        echo "\n[Performance] Memory Usage: {$memoryUsed}MB\n";
    }

    /**
     * Test concurrent request handling simulation.
     */
    public function test_multiple_concurrent_operations_performance(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quests = Quest::factory()->count(5)->active()->create(['child_id' => $child->id]);

        $startTime = microtime(true);

        // Simulate multiple operations
        foreach ($quests as $quest) {
            $this->actingAs($child, 'child')
                ->postJson("/api/child/quests/{$quest->id}/complete");
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // Total time for 5 operations should be reasonable
        $this->assertLessThan(
            2000,
            $duration,
            "5 concurrent quest completions took {$duration}ms"
        );

        echo "\n[Performance] 5 Concurrent Completions: {$duration}ms\n";
    }
}
