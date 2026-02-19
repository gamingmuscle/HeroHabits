<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Child;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ChildAuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test child login page displays with profile selection.
     */
    public function test_child_login_page_displays_profiles(): void
    {
        $user = User::factory()->create();
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

        // Set cookie with children data
        $childrenData = [
            ['id' => $child1->id, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
            ['id' => $child2->id, 'name' => 'Oliver', 'avatar_image' => 'knight_girl_2.png'],
        ];

        $this->browse(function (Browser $browser) use ($childrenData) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($childrenData))
                    ->refresh()
                    ->assertSee('Child Login')
                    ->assertSee('Emma')
                    ->assertSee('Oliver')
                    ->assertVisible('.child-selector');
        });
    }

    /**
     * Test child can select their profile.
     */
    public function test_child_can_select_profile(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
        ]);

        $childrenData = [
            ['id' => $child->id, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
        ];

        $this->browse(function (Browser $browser) use ($childrenData) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($childrenData))
                    ->refresh()
                    ->click('.child-option')
                    ->pause(500)
                    ->assertHasClass('.child-option', 'selected')
                    ->assertVisible('.pin-container.active');
        });
    }

    /**
     * Test child can enter PIN using visual keypad.
     */
    public function test_child_can_enter_pin_with_keypad(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
            'avatar_image' => 'princess_2.png',
        ]);

        $childrenData = [
            ['id' => $child->id, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
        ];

        $this->browse(function (Browser $browser) use ($childrenData) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($childrenData))
                    ->refresh()
                    ->click('.child-option')
                    ->pause(300)
                    // Click PIN digits 1-2-3-4
                    ->click('button[onclick="addDigit(\'1\')"]')
                    ->pause(200)
                    ->assertHasClass('#dot1', 'filled')
                    ->click('button[onclick="addDigit(\'2\')"]')
                    ->pause(200)
                    ->assertHasClass('#dot2', 'filled')
                    ->click('button[onclick="addDigit(\'3\')"]')
                    ->pause(200)
                    ->assertHasClass('#dot3', 'filled')
                    ->click('button[onclick="addDigit(\'4\')"]')
                    ->pause(200)
                    ->assertHasClass('#dot4', 'filled')
                    ->waitForLocation('/child/quests', 5)
                    ->assertPathIs('/child/quests');
        });
    }

    /**
     * Test child login with incorrect PIN shows error.
     */
    public function test_child_login_with_incorrect_pin_shows_error(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'pin' => '1234',
            'avatar_image' => 'princess_2.png',
        ]);

        $childrenData = [
            ['id' => $child->id, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
        ];

        $this->browse(function (Browser $browser) use ($childrenData) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($childrenData))
                    ->refresh()
                    ->click('.child-option')
                    ->pause(300)
                    // Enter wrong PIN: 9-9-9-9
                    ->click('button[onclick="addDigit(\'9\')"]')
                    ->pause(100)
                    ->click('button[onclick="addDigit(\'9\')"]')
                    ->pause(100)
                    ->click('button[onclick="addDigit(\'9\')"]')
                    ->pause(100)
                    ->click('button[onclick="addDigit(\'9\')"]')
                    ->waitForText('Incorrect PIN', 3)
                    ->assertSee('Incorrect PIN')
                    ->assertPathIs('/child/login');
        });
    }

    /**
     * Test clear button resets PIN entry.
     */
    public function test_clear_button_resets_pin_entry(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'avatar_image' => 'princess_2.png',
        ]);

        $childrenData = [
            ['id' => $child->id, 'name' => 'Emma', 'avatar_image' => 'princess_2.png'],
        ];

        $this->browse(function (Browser $browser) use ($childrenData) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($childrenData))
                    ->refresh()
                    ->click('.child-option')
                    ->pause(300)
                    // Enter some digits
                    ->click('button[onclick="addDigit(\'1\')"]')
                    ->pause(100)
                    ->click('button[onclick="addDigit(\'2\')"]')
                    ->pause(100)
                    ->assertHasClass('#dot1', 'filled')
                    ->assertHasClass('#dot2', 'filled')
                    // Click clear
                    ->click('button[onclick="clearPin()"]')
                    ->pause(200)
                    ->assertMissing('#dot1.filled')
                    ->assertMissing('#dot2.filled');
        });
    }

    /**
     * Test child can logout and return to login page.
     */
    public function test_child_can_logout(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
        ]);

        $this->browse(function (Browser $browser) use ($child) {
            $browser->loginAs($child, 'child')
                    ->visit('/child/quests')
                    ->assertSee('My Quests')
                    ->clickLink('Logout')
                    ->waitForLocation('/child/login')
                    ->assertSee('Child Login');
        });
    }

    /**
     * Test navigation from child login to parent login.
     */
    public function test_can_navigate_from_child_to_parent_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/child/login')
                    ->assertSee('Child Login')
                    ->clickLink('Parent Login')
                    ->waitForLocation('/parent/login')
                    ->assertSee('Parent Login');
        });
    }

    /**
     * Test authenticated child cannot access login page.
     */
    public function test_authenticated_child_redirected_from_login_page(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($child) {
            $browser->loginAs($child, 'child')
                    ->visit('/child/login')
                    ->waitForLocation('/child/quests')
                    ->assertPathIs('/child/quests');
        });
    }

    /**
     * Test multiple children can be displayed on login page.
     */
    public function test_multiple_children_displayed_on_login_page(): void
    {
        $user = User::factory()->create();

        $children = [];
        for ($i = 1; $i <= 4; $i++) {
            $child = Child::factory()->create([
                'user_id' => $user->id,
                'name' => "Child {$i}",
            ]);
            $children[] = [
                'id' => $child->id,
                'name' => "Child {$i}",
                'avatar_image' => 'princess_2.png',
            ];
        }

        $this->browse(function (Browser $browser) use ($children) {
            $browser->visit('/child/login')
                    ->plainCookie('hero_children', json_encode($children))
                    ->refresh()
                    ->assertSee('Child 1')
                    ->assertSee('Child 2')
                    ->assertSee('Child 3')
                    ->assertSee('Child 4');
        });
    }

    /**
     * Test PIN keypad has all number buttons.
     */
    public function test_pin_keypad_has_all_number_buttons(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/child/login')
                    ->assertPresent('button[onclick="addDigit(\'0\')"]')
                    ->assertPresent('button[onclick="addDigit(\'1\')"]')
                    ->assertPresent('button[onclick="addDigit(\'2\')"]')
                    ->assertPresent('button[onclick="addDigit(\'3\')"]')
                    ->assertPresent('button[onclick="addDigit(\'4\')"]')
                    ->assertPresent('button[onclick="addDigit(\'5\')"]')
                    ->assertPresent('button[onclick="addDigit(\'6\')"]')
                    ->assertPresent('button[onclick="addDigit(\'7\')"]')
                    ->assertPresent('button[onclick="addDigit(\'8\')"]')
                    ->assertPresent('button[onclick="addDigit(\'9\')"]')
                    ->assertPresent('button[onclick="clearPin()"]');
        });
    }
}
