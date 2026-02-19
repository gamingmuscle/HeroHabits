<?php

namespace Tests\Feature\API\Child;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildQuestAPITest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that child can get their available quests.
     */
    public function test_child_can_get_available_quests(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest1 = Quest::factory()->active()->create([
            'child_id' => $child->id,
            'title' => 'Make Your Bed',
        ]);

        $quest2 = Quest::factory()->active()->create([
            'child_id' => $child->id,
            'title' => 'Brush Teeth',
        ]);

        // Inactive quest should not appear
        Quest::factory()->inactive()->create([
            'child_id' => $child->id,
            'title' => 'Inactive Quest',
        ]);

        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/quests');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonCount(2, 'quests');
        $response->assertJsonFragment(['title' => 'Make Your Bed']);
        $response->assertJsonFragment(['title' => 'Brush Teeth']);
        $response->assertJsonMissing(['title' => 'Inactive Quest']);
    }

    /**
     * Test that child can only see their own quests.
     */
    public function test_child_can_only_see_their_own_quests(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id]);
        $child2 = Child::factory()->create(['user_id' => $user->id]);

        Quest::factory()->active()->create([
            'child_id' => $child1->id,
            'title' => 'Child 1 Quest',
        ]);

        Quest::factory()->active()->create([
            'child_id' => $child2->id,
            'title' => 'Child 2 Quest',
        ]);

        $response = $this->actingAs($child1, 'child')
            ->getJson('/api/child/quests');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'quests');
        $response->assertJsonFragment(['title' => 'Child 1 Quest']);
        $response->assertJsonMissing(['title' => 'Child 2 Quest']);
    }

    /**
     * Test that child can complete a quest.
     */
    public function test_child_can_complete_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'gold_balance' => 100,
        ]);

        $quest = Quest::factory()->active()->create([
            'child_id' => $child->id,
            'title' => 'Make Your Bed',
            'gold_reward' => 10,
        ]);

        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quest completed! Waiting for approval.',
        ]);

        $this->assertDatabaseHas('quest_completions', [
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'status' => 'Pending',
        ]);
    }

    /**
     * Test that child cannot complete inactive quest.
     */
    public function test_child_cannot_complete_inactive_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->inactive()->create([
            'child_id' => $child->id,
        ]);

        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'This quest is not active',
        ]);
    }

    /**
     * Test that child cannot complete another child's quest.
     */
    public function test_child_cannot_complete_other_childs_quest(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id]);
        $child2 = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->active()->create([
            'child_id' => $child2->id,
        ]);

        $response = $this->actingAs($child1, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $response->assertStatus(403);
    }

    /**
     * Test that child cannot complete same quest twice on same day.
     */
    public function test_child_cannot_complete_same_quest_twice_same_day(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->active()->create([
            'child_id' => $child->id,
        ]);

        // First completion
        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => $quest->gold_reward,
            'status' => 'Pending',
        ]);

        // Try to complete again
        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Quest already completed today',
        ]);
    }

    /**
     * Test that child can get quest completion history.
     */
    public function test_child_can_get_quest_history(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Make Your Bed',
        ]);

        // Create completions
        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->subDays(2)->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Accepted',
        ]);

        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->subDays(1)->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/quests/history');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonCount(2, 'history');
    }

    /**
     * Test that child can get quest statistics.
     */
    public function test_child_can_get_quest_statistics(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->create([
            'child_id' => $child->id,
        ]);

        // Create various completions
        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Accepted',
        ]);

        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->subDays(1)->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Accepted',
        ]);

        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->subDays(2)->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Rejected',
        ]);

        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/quests/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'total_completed' => 3,
                'total_gold_earned' => 20, // Only accepted
            ],
        ]);
    }

    /**
     * Test that unauthenticated child cannot access quest endpoints.
     */
    public function test_unauthenticated_child_cannot_access_quest_endpoints(): void
    {
        $response = $this->getJson('/api/child/quests');
        $response->assertStatus(401);

        $response = $this->postJson('/api/child/quests/1/complete');
        $response->assertStatus(401);

        $response = $this->getJson('/api/child/quests/history');
        $response->assertStatus(401);

        $response = $this->getJson('/api/child/quests/stats');
        $response->assertStatus(401);
    }

    /**
     * Test quest completion with date filtering.
     */
    public function test_child_can_filter_history_by_date(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create(['child_id' => $child->id]);

        // Old completion
        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->subDays(10)->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Accepted',
        ]);

        // Recent completion
        QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Accepted',
        ]);

        // Filter by recent dates only (last 7 days)
        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/quests/history?days=7');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'history');
    }
}
