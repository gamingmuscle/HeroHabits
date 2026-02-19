<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuestManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test parent can view quests page.
     */
    public function test_parent_can_view_quests_page(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        Quest::factory()->count(3)->create(['child_id' => $child->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->assertSee('Quest Management')
                    ->assertVisible('.quest-card');
        });
    }

    /**
     * Test parent can open create quest modal.
     */
    public function test_parent_can_open_create_quest_modal(): void
    {
        $user = User::factory()->create();
        Child::factory()->create(['user_id' => $user->id, 'name' => 'Emma']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->click('#createQuestBtn')
                    ->waitFor('#questModal')
                    ->assertVisible('#questModal')
                    ->assertSee('Create Quest')
                    ->assertVisible('#questForm');
        });
    }

    /**
     * Test parent can create a new quest via modal form.
     */
    public function test_parent_can_create_quest_via_modal(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
        ]);

        $this->browse(function (Browser $browser) use ($user, $child) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->click('#createQuestBtn')
                    ->waitFor('#questModal')
                    ->select('child_id', $child->id)
                    ->type('title', 'Clean Your Room')
                    ->type('description', 'Make your room sparkle!')
                    ->type('gold_reward', '15')
                    ->select('is_active', '1')
                    ->press('Save Quest')
                    ->waitForText('Quest created successfully!')
                    ->assertSee('Quest created successfully!')
                    ->assertSee('Clean Your Room')
                    ->assertDontSee('#questModal');
        });
    }

    /**
     * Test quest form validation displays errors.
     */
    public function test_quest_form_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Child::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->click('#createQuestBtn')
                    ->waitFor('#questModal')
                    ->press('Save Quest')
                    ->waitForText('Failed to save quest')
                    ->assertSee('Failed to save quest');
        });
    }

    /**
     * Test parent can edit existing quest.
     */
    public function test_parent_can_edit_existing_quest(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Old Title',
            'gold_reward' => 10,
        ]);

        $this->browse(function (Browser $browser) use ($user, $quest) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->click("[data-quest-id='{$quest->id}'] .edit-btn")
                    ->waitFor('#questModal')
                    ->assertSee('Edit Quest')
                    ->assertInputValue('title', 'Old Title')
                    ->clear('title')
                    ->type('title', 'Updated Title')
                    ->clear('gold_reward')
                    ->type('gold_reward', '25')
                    ->press('Save Quest')
                    ->waitForText('Quest updated successfully!')
                    ->assertSee('Quest updated successfully!')
                    ->assertSee('Updated Title')
                    ->assertSee('25');
        });
    }

    /**
     * Test parent can delete quest with confirmation.
     */
    public function test_parent_can_delete_quest_with_confirmation(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Quest to Delete',
        ]);

        $this->browse(function (Browser $browser) use ($user, $quest) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->assertSee('Quest to Delete')
                    ->click("[data-quest-id='{$quest->id}'] .delete-btn")
                    ->waitForDialog()
                    ->assertDialogOpened('Are you sure you want to delete this quest?')
                    ->acceptDialog()
                    ->waitForText('Quest deleted successfully!')
                    ->assertSee('Quest deleted successfully!')
                    ->assertDontSee('Quest to Delete');
        });
    }

    /**
     * Test parent can cancel quest deletion.
     */
    public function test_parent_can_cancel_quest_deletion(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Quest to Keep',
        ]);

        $this->browse(function (Browser $browser) use ($user, $quest) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->assertSee('Quest to Keep')
                    ->click("[data-quest-id='{$quest->id}'] .delete-btn")
                    ->waitForDialog()
                    ->dismissDialog()
                    ->pause(500)
                    ->assertSee('Quest to Keep');
        });
    }

    /**
     * Test parent can filter quests by child.
     */
    public function test_parent_can_filter_quests_by_child(): void
    {
        $user = User::factory()->create();
        $child1 = Child::factory()->create(['user_id' => $user->id, 'name' => 'Emma']);
        $child2 = Child::factory()->create(['user_id' => $user->id, 'name' => 'Oliver']);

        Quest::factory()->create(['child_id' => $child1->id, 'title' => 'Emma Quest']);
        Quest::factory()->create(['child_id' => $child2->id, 'title' => 'Oliver Quest']);

        $this->browse(function (Browser $browser) use ($user, $child1) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->assertSee('Emma Quest')
                    ->assertSee('Oliver Quest')
                    ->select('#childFilter', $child1->id)
                    ->pause(1000)
                    ->assertSee('Emma Quest')
                    ->assertDontSee('Oliver Quest');
        });
    }

    /**
     * Test modal closes when clicking outside or on close button.
     */
    public function test_modal_closes_on_close_button_click(): void
    {
        $user = User::factory()->create();
        Child::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->click('#createQuestBtn')
                    ->waitFor('#questModal')
                    ->assertVisible('#questModal')
                    ->click('.close-modal')
                    ->pause(500)
                    ->assertMissing('#questModal');
        });
    }

    /**
     * Test quest cards display correct information.
     */
    public function test_quest_cards_display_correct_information(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id, 'name' => 'Emma']);

        Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Make Your Bed',
            'description' => 'Every morning',
            'gold_reward' => 10,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->assertSee('Make Your Bed')
                    ->assertSee('Every morning')
                    ->assertSee('10')
                    ->assertSee('Emma')
                    ->assertPresent('.quest-card .active-badge');
        });
    }

    /**
     * Test inactive quests are visually distinct.
     */
    public function test_inactive_quests_are_visually_distinct(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Active Quest',
            'is_active' => true,
        ]);

        Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Inactive Quest',
            'is_active' => false,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->waitFor('.quest-card')
                    ->assertSee('Active Quest')
                    ->assertSee('Inactive Quest')
                    ->assertPresent('.quest-card.inactive');
        });
    }

    /**
     * Test notification system displays success messages.
     */
    public function test_notifications_display_and_auto_dismiss(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user, $child) {
            $browser->loginAs($user)
                    ->visit('/parent/quests')
                    ->click('#createQuestBtn')
                    ->waitFor('#questModal')
                    ->select('child_id', $child->id)
                    ->type('title', 'Test Quest')
                    ->type('gold_reward', '10')
                    ->press('Save Quest')
                    ->waitFor('.notification-success')
                    ->assertVisible('.notification-success')
                    ->assertSee('Quest created successfully!')
                    ->pause(5000)
                    ->assertMissing('.notification-success');
        });
    }
}
