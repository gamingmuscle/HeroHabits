<?php

namespace Tests\Feature\API\Parent;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestAPITest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that parent can retrieve all quests.
     */
    public function test_parent_can_get_all_quests(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $quest1 = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Make Your Bed',
            'gold_reward' => 10,
        ]);

        $quest2 = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Brush Teeth',
            'gold_reward' => 5,
        ]);

        $response = $this->actingAs($user)->getJson('/api/parent/quests');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonCount(2, 'quests');
        $response->assertJsonFragment(['title' => 'Make Your Bed']);
        $response->assertJsonFragment(['title' => 'Brush Teeth']);
    }

    /**
     * Test that parent can filter quests by child.
     */
    public function test_parent_can_filter_quests_by_child(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id]);
        $child2 = Child::factory()->create(['user_id' => $user->id]);

        Quest::factory()->create(['child_id' => $child1->id, 'title' => 'Quest 1']);
        Quest::factory()->create(['child_id' => $child2->id, 'title' => 'Quest 2']);

        $response = $this->actingAs($user)
            ->getJson("/api/parent/quests?child_id={$child1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'quests');
        $response->assertJsonFragment(['title' => 'Quest 1']);
        $response->assertJsonMissing(['title' => 'Quest 2']);
    }

    /**
     * Test that parent can create a new quest.
     */
    public function test_parent_can_create_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $questData = [
            'child_id' => $child->id,
            'title' => 'Clean Your Room',
            'description' => 'Make your room sparkle!',
            'gold_reward' => 15,
            'is_active' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/parent/quests', $questData);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Quest created successfully',
        ]);
        $response->assertJsonFragment([
            'title' => 'Clean Your Room',
            'gold_reward' => 15,
        ]);

        $this->assertDatabaseHas('quests', [
            'child_id' => $child->id,
            'title' => 'Clean Your Room',
            'gold_reward' => 15,
        ]);
    }

    /**
     * Test that quest creation validates required fields.
     */
    public function test_quest_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/parent/quests', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['child_id', 'title', 'gold_reward']);
    }

    /**
     * Test that parent can update a quest.
     */
    public function test_parent_can_update_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Old Title',
            'gold_reward' => 10,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'gold_reward' => 20,
            'is_active' => false,
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/parent/quests/{$quest->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quest updated successfully',
        ]);

        $this->assertDatabaseHas('quests', [
            'id' => $quest->id,
            'title' => 'Updated Title',
            'gold_reward' => 20,
            'is_active' => false,
        ]);
    }

    /**
     * Test that parent cannot update another user's quest.
     */
    public function test_parent_cannot_update_other_users_quest(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $child2 = Child::factory()->create(['user_id' => $user2->id]);
        $quest = Quest::factory()->create(['child_id' => $child2->id]);

        $response = $this->actingAs($user1)
            ->putJson("/api/parent/quests/{$quest->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that parent can delete a quest.
     */
    public function test_parent_can_delete_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create(['child_id' => $child->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/parent/quests/{$quest->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quest deleted successfully',
        ]);

        $this->assertDatabaseMissing('quests', ['id' => $quest->id]);
    }

    /**
     * Test that parent cannot delete another user's quest.
     */
    public function test_parent_cannot_delete_other_users_quest(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $child2 = Child::factory()->create(['user_id' => $user2->id]);
        $quest = Quest::factory()->create(['child_id' => $child2->id]);

        $response = $this->actingAs($user1)
            ->deleteJson("/api/parent/quests/{$quest->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('quests', ['id' => $quest->id]);
    }

    /**
     * Test that unauthenticated user cannot access quest endpoints.
     */
    public function test_unauthenticated_user_cannot_access_quest_endpoints(): void
    {
        $response = $this->getJson('/api/parent/quests');
        $response->assertStatus(401);

        $response = $this->postJson('/api/parent/quests', []);
        $response->assertStatus(401);

        $response = $this->putJson('/api/parent/quests/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/parent/quests/1');
        $response->assertStatus(401);
    }

    /**
     * Test gold reward validation.
     */
    public function test_quest_validates_gold_reward(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Negative gold
        $response = $this->actingAs($user)->postJson('/api/parent/quests', [
            'child_id' => $child->id,
            'title' => 'Test Quest',
            'gold_reward' => -10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gold_reward');

        // Non-integer gold
        $response = $this->actingAs($user)->postJson('/api/parent/quests', [
            'child_id' => $child->id,
            'title' => 'Test Quest',
            'gold_reward' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gold_reward');
    }
}
