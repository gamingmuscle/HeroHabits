<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ParentAuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test parent can view the welcome page and navigate to login.
     */
    public function test_parent_can_view_welcome_page_and_navigate_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Hero Habits')
                    ->assertSee('Parent Portal')
                    ->assertSee('Child Portal')
                    ->assertSee('Turn daily tasks into epic quests!')
                    ->assertVisible('.login-card')
                    ->click('.login-card:first-child .login-btn')
                    ->assertPathIs('/parent/login')
                    ->assertSee('Parent Login');
        });
    }

    /**
     * Test parent registration flow with form validation.
     */
    public function test_parent_can_register_with_valid_information(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/register')
                    ->assertSee('Create Account')
                    ->type('displayname', 'Test Parent')
                    ->type('username', 'testparent123')
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'password123')
                    ->press('Create Parent Account')
                    ->waitForLocation('/parent/profiles')
                    ->assertSee('Now add your first child profile');
        });
    }

    /**
     * Test registration form validation errors display correctly.
     */
    public function test_registration_form_shows_validation_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/register')
                    ->press('Create Parent Account')
                    ->waitForText('The displayname field is required')
                    ->assertSee('The displayname field is required')
                    ->assertSee('The username field is required')
                    ->assertSee('The password field is required');
        });
    }

    /**
     * Test password confirmation mismatch validation.
     */
    public function test_registration_validates_password_confirmation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/register')
                    ->type('displayname', 'Test Parent')
                    ->type('username', 'testparent')
                    ->type('password', 'password123')
                    ->type('password_confirmation', 'differentpassword')
                    ->press('Create Parent Account')
                    ->waitFor('.error-message')
                    ->assertSee('Passwords do not match!');
        });
    }

    /**
     * Test parent login flow with valid credentials.
     */
    public function test_parent_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
            'displayname' => 'Test Parent',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/login')
                    ->assertSee('Parent Login')
                    ->type('username', 'testparent')
                    ->type('password', 'password123')
                    ->press('Login to Parent Portal')
                    ->waitForLocation('/parent/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Test Parent');
        });
    }

    /**
     * Test login with invalid credentials shows error.
     */
    public function test_login_with_invalid_credentials_shows_error(): void
    {
        User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/login')
                    ->type('username', 'testparent')
                    ->type('password', 'wrongpassword')
                    ->press('Login to Parent Portal')
                    ->waitForText('Invalid username or password')
                    ->assertSee('Invalid username or password')
                    ->assertPathIs('/parent/login');
        });
    }

    /**
     * Test parent logout functionality.
     */
    public function test_parent_can_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'testparent',
            'password' => Hash::make('password123'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/dashboard')
                    ->assertSee('Dashboard')
                    ->clickLink('Logout')
                    ->waitForLocation('/parent/login')
                    ->assertSee('Parent Login');
        });
    }

    /**
     * Test authenticated parent cannot access login page.
     */
    public function test_authenticated_parent_redirected_from_login_page(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/login')
                    ->waitForLocation('/parent/dashboard')
                    ->assertPathIs('/parent/dashboard');
        });
    }

    /**
     * Test navigation between login and registration pages.
     */
    public function test_can_navigate_between_login_and_registration(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/login')
                    ->assertSee('Parent Login')
                    ->clickLink('Create one here')
                    ->waitForLocation('/parent/register')
                    ->assertSee('Create Account')
                    ->clickLink('Login here')
                    ->waitForLocation('/parent/login')
                    ->assertSee('Parent Login');
        });
    }

    /**
     * Test link to child login from parent login page.
     */
    public function test_can_navigate_to_child_login_from_parent_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/parent/login')
                    ->clickLink('Child Login')
                    ->waitForLocation('/child/login')
                    ->assertSee('Child Login');
        });
    }

    /**
     * Test session persistence after page refresh.
     */
    public function test_session_persists_after_page_refresh(): void
    {
        $user = User::factory()->create(['displayname' => 'Test Parent']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/dashboard')
                    ->assertSee('Test Parent')
                    ->refresh()
                    ->assertSee('Test Parent')
                    ->assertPathIs('/parent/dashboard');
        });
    }
}
