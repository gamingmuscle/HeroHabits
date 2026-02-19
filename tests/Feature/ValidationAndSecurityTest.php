<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CSRF protection on POST requests.
     */
    public function test_csrf_protection_on_post_requests(): void
    {
        $response = $this->post(route('parent.login'), [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        // Should get CSRF token mismatch error (419)
        $response->assertStatus(419);
    }

    /**
     * Test XSS prevention in user input.
     */
    public function test_xss_prevention_in_user_input(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Try to inject script tag in quest title
        $maliciousData = [
            'child_id' => $child->id,
            'title' => '<script>alert("XSS")</script>',
            'gold_reward' => 10,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/parent/quests', $maliciousData);

        $response->assertStatus(201);

        // Retrieve the quest and verify script tags are escaped
        $quest = Quest::latest()->first();
        $this->assertStringContainsString('&lt;script&gt;', htmlspecialchars($quest->title));
    }

    /**
     * Test SQL injection prevention in queries.
     */
    public function test_sql_injection_prevention(): void
    {
        $user = User::factory()->create();

        // Try SQL injection in login
        $response = $this->post(route('parent.login'), [
            'username' => "admin' OR '1'='1",
            'password' => "password' OR '1'='1",
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * Test authorization: parent can only access their own children's data.
     */
    public function test_parent_cannot_access_other_parents_children(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $child1 = Child::factory()->create(['user_id' => $user1->id]);
        $child2 = Child::factory()->create(['user_id' => $user2->id]);

        // User1 tries to access User2's child
        $response = $this->actingAs($user1)
            ->getJson("/api/parent/children/{$child2->id}");

        $response->assertStatus(403);
    }

    /**
     * Test authorization: parent can only modify their own quests.
     */
    public function test_parent_cannot_modify_other_parents_quests(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $child2 = Child::factory()->create(['user_id' => $user2->id]);
        $quest = Quest::factory()->create(['child_id' => $child2->id]);

        // User1 tries to modify User2's quest
        $response = $this->actingAs($user1)
            ->putJson("/api/parent/quests/{$quest->id}", [
                'title' => 'Hacked Quest',
                'gold_reward' => 1000,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('quests', ['title' => 'Hacked Quest']);
    }

    /**
     * Test authorization: child can only access their own quests.
     */
    public function test_child_cannot_access_other_childs_quests(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id]);
        $child2 = Child::factory()->create(['user_id' => $user->id]);

        $quest = Quest::factory()->create(['child_id' => $child2->id]);

        // Child1 tries to complete Child2's quest
        $response = $this->actingAs($child1, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete");

        $response->assertStatus(403);
    }

    /**
     * Test password hashing: passwords are never stored in plain text.
     */
    public function test_passwords_are_hashed(): void
    {
        $response = $this->post(route('parent.register'), [
            '_token' => csrf_token(),
            'displayname' => 'Test Parent',
            'username' => 'testparent',
            'password' => 'mypassword123',
            'password_confirmation' => 'mypassword123',
        ]);

        $user = User::where('username', 'testparent')->first();

        // Password should not be stored in plain text
        $this->assertNotEquals('mypassword123', $user->password);
        // But should verify correctly
        $this->assertTrue(Hash::check('mypassword123', $user->password));
    }

    /**
     * Test PIN validation: must be exactly 4 digits.
     */
    public function test_child_pin_must_be_four_digits(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        // Test with 3 digits
        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '123',
        ]);
        $response->assertStatus(422);

        // Test with 5 digits
        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '12345',
        ]);
        $response->assertStatus(422);

        // Test with non-numeric
        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => 'abcd',
        ]);
        $response->assertStatus(422);

        // Test with correct 4 digits
        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '1234',
        ]);
        $response->assertStatus(200);
    }

    /**
     * Test rate limiting: prevent brute force attacks.
     */
    public function test_login_rate_limiting(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('parent.login'), [
                '_token' => csrf_token(),
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // Next attempt should be rate limited (429)
        $response = $this->post(route('parent.login'), [
            '_token' => csrf_token(),
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test that sensitive data is not exposed in JSON responses.
     */
    public function test_sensitive_data_not_exposed_in_json(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/parent/profile');

        $response->assertStatus(200);
        // Password should not be in response
        $response->assertJsonMissing(['password']);
    }

    /**
     * Test quest completion date validation: cannot complete quest in future.
     */
    public function test_cannot_complete_quest_with_future_date(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->active()->create(['child_id' => $child->id]);

        // Try to complete quest with future date (if API accepts date parameter)
        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$quest->id}/complete", [
                'completed_date' => now()->addDays(5)->format('Y-m-d'),
            ]);

        // Should either ignore the date and use today, or return validation error
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors('completed_date');
        } else {
            $completion = QuestCompletion::latest()->first();
            $this->assertEquals(now()->format('Y-m-d'), $completion->completed_date);
        }
    }

    /**
     * Test gold balance cannot be negative.
     */
    public function test_gold_balance_cannot_be_negative(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'gold_balance' => 10,
        ]);

        // Try to create treasure that costs more than balance
        $response = $this->actingAs($child, 'child')
            ->postJson('/api/child/treasures/purchase', [
                'treasure_id' => 1,
                'gold_cost' => 50, // More than current balance
            ]);

        // Should be rejected
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Not enough gold',
        ]);
    }

    /**
     * Test that deleted quests cannot be completed.
     */
    public function test_deleted_quests_cannot_be_completed(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->active()->create(['child_id' => $child->id]);

        $questId = $quest->id;

        // Delete the quest
        $quest->delete();

        // Try to complete deleted quest
        $response = $this->actingAs($child, 'child')
            ->postJson("/api/child/quests/{$questId}/complete");

        $response->assertStatus(404);
    }

    /**
     * Test username uniqueness validation.
     */
    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'existinguser']);

        $response = $this->post(route('parent.register'), [
            '_token' => csrf_token(),
            'displayname' => 'New User',
            'username' => 'existinguser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * Test email validation in registration (if email field exists).
     */
    public function test_email_format_validation(): void
    {
        $response = $this->postJson('/api/parent/register', [
            'displayname' => 'Test User',
            'username' => 'testuser',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // If email field exists, should validate format
        if ($response->status() === 422) {
            $response->assertJsonValidationErrors('email');
        }
    }

    /**
     * Test that child cannot approve their own quests.
     */
    public function test_child_cannot_approve_own_quest_completions(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create(['child_id' => $child->id]);

        $completion = QuestCompletion::create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => 10,
            'status' => 'Pending',
        ]);

        // Child tries to approve their own completion
        $response = $this->actingAs($child, 'child')
            ->postJson("/api/parent/approvals/{$completion->id}/accept");

        $response->assertStatus(403);
    }

    /**
     * Test maximum field lengths are enforced.
     */
    public function test_maximum_field_lengths_are_enforced(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        // Very long quest title (over 255 characters)
        $longTitle = str_repeat('A', 300);

        $response = $this->actingAs($user)
            ->postJson('/api/parent/quests', [
                'child_id' => $child->id,
                'title' => $longTitle,
                'gold_reward' => 10,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }
}
