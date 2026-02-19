<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ChildAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the child login page can be displayed.
     */
    public function test_child_login_page_displays(): void
    {
        $response = $this->get(route('child.login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.child-login');
        $response->assertSee('Child Login');
    }

    /**
     * Test that child can login with valid PIN.
     */
    public function test_child_can_login_with_valid_pin(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
        ]);

        $response = $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '1234',
        ]);

        $response->assertRedirect(route('child.quests'));
        $response->assertSessionHas('success');
        $this->assertAuthenticatedAs($child, 'child');
    }

    /**
     * Test that child login fails with invalid PIN.
     */
    public function test_child_login_fails_with_invalid_pin(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
        ]);

        $response = $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '9999',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest('child');
    }

    /**
     * Test that child login fails with non-existent child ID.
     */
    public function test_child_login_fails_with_nonexistent_child_id(): void
    {
        $response = $this->post(route('child.login'), [
            'child_id' => 99999,
            'pin' => '1234',
        ]);

        $response->assertSessionHasErrors('child_id');
        $this->assertGuest('child');
    }

    /**
     * Test that child login fails with missing fields.
     */
    public function test_child_login_fails_with_missing_fields(): void
    {
        $response = $this->post(route('child.login'), [
            'child_id' => 1,
            // Missing PIN
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertGuest('child');
    }

    /**
     * Test that child login validates PIN format (4 digits).
     */
    public function test_child_login_validates_pin_format(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        // Test with non-numeric PIN
        $response = $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => 'abcd',
        ]);

        $response->assertSessionHasErrors('pin');

        // Test with short PIN
        $response = $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '123',
        ]);

        $response->assertSessionHasErrors('pin');

        // Test with long PIN
        $response = $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '12345',
        ]);

        $response->assertSessionHasErrors('pin');
    }

    /**
     * Test that child session stores correct data on login.
     */
    public function test_child_session_stores_correct_data_on_login(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
        ]);

        $this->post(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '1234',
        ]);

        $this->assertAuthenticatedAs($child, 'child');
        $this->assertTrue(session()->has('child_last_activity'));
        $this->assertEquals($user->id, session('child_parent_user_id'));
    }

    /**
     * Test that a child can logout.
     */
    public function test_child_can_logout(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($child, 'child')->post(route('child.logout'));

        $response->assertRedirect(route('child.login'));
        $this->assertGuest('child');
    }

    /**
     * Test that authenticated child cannot access login page.
     */
    public function test_authenticated_child_cannot_access_login_page(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($child, 'child')->get(route('child.login'));

        $response->assertRedirect(route('child.quests'));
    }

    /**
     * Test that unauthenticated child is redirected from quests page.
     */
    public function test_unauthenticated_child_redirected_from_quests(): void
    {
        $response = $this->get(route('child.quests'));

        $response->assertRedirect(route('child.login'));
    }

    /**
     * Test child login with JSON request (API mode).
     */
    public function test_child_login_json_response(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
            'avatar_image' => 'princess_2.png',
            'gold_balance' => 100,
        ]);

        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'child' => ['id', 'name', 'avatar_image', 'gold_balance'],
            'redirect',
        ]);
    }

    /**
     * Test child login with JSON request and invalid PIN.
     */
    public function test_child_login_json_fails_with_invalid_pin(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        $response = $this->postJson(route('child.login'), [
            'child_id' => $child->id,
            'pin' => '9999',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Incorrect PIN',
        ]);
    }

    /**
     * Test child login with JSON request and non-existent child.
     */
    public function test_child_login_json_fails_with_nonexistent_child(): void
    {
        $response = $this->postJson(route('child.login'), [
            'child_id' => 99999,
            'pin' => '1234',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Child profile not found',
        ]);
    }

    /**
     * Test child logout with JSON request.
     */
    public function test_child_logout_json_response(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($child, 'child')->postJson(route('child.logout'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Test child profile API endpoint.
     */
    public function test_child_profile_endpoint_returns_correct_data(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'age' => 8,
            'avatar_image' => 'princess_2.png',
            'gold_balance' => 150,
        ]);

        $response = $this->actingAs($child, 'child')
            ->getJson('/api/child/profile');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'child' => [
                'id' => $child->id,
                'name' => 'Emma',
                'age' => 8,
                'avatar_image' => 'princess_2.png',
                'gold_balance' => 150,
            ],
        ]);
    }

    /**
     * Test child profile endpoint requires authentication.
     */
    public function test_child_profile_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/child/profile');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Not authenticated',
        ]);
    }

    /**
     * Test that parent cannot access child routes.
     */
    public function test_parent_cannot_access_child_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('child.quests'));

        $response->assertStatus(403);
    }

    /**
     * Test that child cannot access parent routes.
     */
    public function test_child_cannot_access_parent_routes(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($child, 'child')->get(route('parent.dashboard'));

        $response->assertStatus(403);
    }

    /**
     * Test child login page displays children from cookie.
     */
    public function test_child_login_page_receives_children_from_cookie(): void
    {
        $childrenData = [
            ['id' => 1, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
            ['id' => 2, 'name' => 'Oliver', 'avatar_image' => 'knight_girl_2.png'],
        ];

        $response = $this->withCookie('hero_children', json_encode($childrenData))
            ->get(route('child.login'));

        $response->assertStatus(200);
        $response->assertViewHas('savedChildren', $childrenData);
    }

    /**
     * Test that multiple children can login independently.
     */
    public function test_multiple_children_can_login_independently(): void
    {
        $user = User::factory()->create();

        $child1 = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1111',
        ]);

        $child2 = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Oliver',
            'pin' => '2222',
        ]);

        // Login as first child
        $response1 = $this->post(route('child.login'), [
            'child_id' => $child1->id,
            'pin' => '1111',
        ]);

        $response1->assertRedirect(route('child.quests'));
        $this->assertAuthenticatedAs($child1, 'child');

        // Logout first child
        $this->post(route('child.logout'));
        $this->assertGuest('child');

        // Login as second child
        $response2 = $this->post(route('child.login'), [
            'child_id' => $child2->id,
            'pin' => '2222',
        ]);

        $response2->assertRedirect(route('child.quests'));
        $this->assertAuthenticatedAs($child2, 'child');
    }
}
