<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the parent login page can be displayed.
     */
    public function test_parent_login_page_displays(): void
    {
        $response = $this->get(route('parent.login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.parent-login');
        $response->assertSee('Parent Login');
    }

    /**
     * Test that the parent registration page can be displayed.
     */
    public function test_parent_register_page_displays(): void
    {
        $response = $this->get(route('parent.register'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.parent-register');
        $response->assertSee('Create Account');
    }

    /**
     * Test that a parent can register with valid credentials.
     */
    public function test_parent_can_register_with_valid_credentials(): void
    {
        $response = $this->post(route('parent.register'), [
            'displayname' => 'Test Parent',
            'username' => 'testparent',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('parent.profiles'));
        $response->assertSessionHas('success');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'testparent',
            'displayname' => 'Test Parent',
        ]);
    }

    /**
     * Test that parent registration fails with mismatched passwords.
     */
    public function test_parent_registration_fails_with_mismatched_passwords(): void
    {
        $response = $this->post(route('parent.register'), [
            'displayname' => 'Test Parent',
            'username' => 'testparent',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'username' => 'testparent',
        ]);
    }

    /**
     * Test that parent registration fails with duplicate username.
     */
    public function test_parent_registration_fails_with_duplicate_username(): void
    {
        // Create existing user
        User::factory()->create([
            'username' => 'existinguser',
        ]);

        $response = $this->post(route('parent.register'), [
            'displayname' => 'Test Parent',
            'username' => 'existinguser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that parent registration fails with short password.
     */
    public function test_parent_registration_fails_with_short_password(): void
    {
        $response = $this->post(route('parent.register'), [
            'displayname' => 'Test Parent',
            'username' => 'testparent',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /**
     * Test that parent registration fails with missing fields.
     */
    public function test_parent_registration_fails_with_missing_fields(): void
    {
        $response = $this->post(route('parent.register'), [
            'username' => 'testparent',
            // Missing displayname, password, password_confirmation
        ]);

        $response->assertSessionHasErrors(['displayname', 'password']);
        $this->assertGuest();
    }

    /**
     * Test that a parent can login with valid credentials.
     */
    public function test_parent_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
            'displayname' => 'Test Parent',
        ]);

        $response = $this->post(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertAuthenticated();
    }

    /**
     * Test that parent login fails with invalid password.
     */
    public function test_parent_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that parent login fails with non-existent username.
     */
    public function test_parent_login_fails_with_nonexistent_username(): void
    {
        $response = $this->post(route('parent.login'), [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that parent login fails with missing fields.
     */
    public function test_parent_login_fails_with_missing_fields(): void
    {
        $response = $this->post(route('parent.login'), [
            'username' => 'testparent',
            // Missing password
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /**
     * Test that a parent can logout.
     */
    public function test_parent_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('parent.logout'));

        $response->assertRedirect(route('parent.login'));
        $this->assertGuest();
    }

    /**
     * Test that parent session stores correct data on login.
     */
    public function test_parent_session_stores_correct_data_on_login(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
            'displayname' => 'Test Parent',
        ]);

        $this->post(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(session()->has('parent_last_activity'));
        $this->assertEquals('Test Parent', session('parent_displayname'));
    }

    /**
     * Test that children data is stored in cookie on parent login.
     */
    public function test_children_cookie_is_set_on_parent_login(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
        ]);

        // Create children for the user
        $child1 = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'avatar_image' => 'princess_2.png',
        ]);

        $child2 = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Oliver',
            'avatar_image' => 'knight_girl_2.png',
        ]);

        $response = $this->post(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'password123',
        ]);

        $response->assertCookie('hero_parent', 'Test Parent');
        $response->assertCookie('hero_children');
    }

    /**
     * Test that authenticated parent cannot access login page.
     */
    public function test_authenticated_parent_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('parent.login'));

        $response->assertRedirect(route('parent.dashboard'));
    }

    /**
     * Test that authenticated parent cannot access registration page.
     */
    public function test_authenticated_parent_cannot_access_registration_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('parent.register'));

        $response->assertRedirect(route('parent.dashboard'));
    }

    /**
     * Test that unauthenticated user is redirected from dashboard.
     */
    public function test_unauthenticated_user_redirected_from_dashboard(): void
    {
        $response = $this->get(route('parent.dashboard'));

        $response->assertRedirect(route('parent.login'));
    }

    /**
     * Test parent login with JSON request (API mode).
     */
    public function test_parent_login_json_response(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
            'displayname' => 'Test Parent',
        ]);

        $response = $this->postJson(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'username', 'displayname'],
            'redirect',
        ]);
    }

    /**
     * Test parent login with JSON request and invalid credentials.
     */
    public function test_parent_login_json_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson(route('parent.login'), [
            'username' => 'testparent',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid username or password',
        ]);
    }

    /**
     * Test parent registration with JSON request.
     */
    public function test_parent_registration_json_response(): void
    {
        $response = $this->postJson(route('parent.register'), [
            'displayname' => 'Test Parent',
            'username' => 'testparent',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Registration successful',
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'username', 'displayname'],
            'redirect',
        ]);
    }

    /**
     * Test parent logout with JSON request.
     */
    public function test_parent_logout_json_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('parent.logout'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
