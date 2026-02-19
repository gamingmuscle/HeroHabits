<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Child;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ApprovalWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test parent can view approvals page with pending completions.
     */
    public function test_parent_can_view_approvals_page(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id, 'name' => 'Emma']);
        $quest = Quest::factory()->create(['child_id' => $child->id, 'title' => 'Make Bed']);

        QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->assertSee('Quest Approvals')
                    ->assertSee('Make Bed')
                    ->assertSee('Emma')
                    ->assertVisible('.approval-card');
        });
    }

    /**
     * Test parent can approve single quest completion.
     */
    public function test_parent_can_approve_single_quest_completion(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'gold_balance' => 100,
        ]);

        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Clean Room',
            'gold_reward' => 15,
        ]);

        $completion = QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'gold_earned' => 15,
        ]);

        $this->browse(function (Browser $browser) use ($user, $completion) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->assertSee('Clean Room')
                    ->click("[data-completion-id='{$completion->id}'] .accept-btn")
                    ->waitForText('Quest approved!')
                    ->assertSee('Quest approved!')
                    ->assertDontSee('Clean Room');
        });
    }

    /**
     * Test parent can reject single quest completion.
     */
    public function test_parent_can_reject_single_quest_completion(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Brush Teeth',
        ]);

        $completion = QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
        ]);

        $this->browse(function (Browser $browser) use ($user, $completion) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->click("[data-completion-id='{$completion->id}'] .reject-btn")
                    ->waitForDialog()
                    ->acceptDialog()
                    ->waitForText('Quest rejected')
                    ->assertSee('Quest rejected')
                    ->assertDontSee('Brush Teeth');
        });
    }

    /**
     * Test parent can select multiple completions with checkboxes.
     */
    public function test_parent_can_select_multiple_completions(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest1 = Quest::factory()->create(['child_id' => $child->id, 'title' => 'Quest 1']);
        $quest2 = Quest::factory()->create(['child_id' => $child->id, 'title' => 'Quest 2']);

        $completion1 = QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest1->id,
            'child_id' => $child->id,
        ]);

        $completion2 = QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest2->id,
            'child_id' => $child->id,
        ]);

        $this->browse(function (Browser $browser) use ($user, $completion1, $completion2) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->check("[data-completion-id='{$completion1->id}'] input[type='checkbox']")
                    ->check("[data-completion-id='{$completion2->id}'] input[type='checkbox']")
                    ->pause(300)
                    ->assertSee('2 selected')
                    ->assertVisible('.bulk-actions');
        });
    }

    /**
     * Test parent can bulk approve selected completions.
     */
    public function test_parent_can_bulk_approve_completions(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $completions = [];
        for ($i = 1; $i <= 3; $i++) {
            $quest = Quest::factory()->create([
                'child_id' => $child->id,
                'title' => "Quest {$i}",
            ]);

            $completions[] = QuestCompletion::factory()->pending()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
            ]);
        }

        $this->browse(function (Browser $browser) use ($user, $completions) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card');

            foreach ($completions as $completion) {
                $browser->check("[data-completion-id='{$completion->id}'] input[type='checkbox']");
            }

            $browser->pause(300)
                    ->assertSee('3 selected')
                    ->click('#bulkAcceptBtn')
                    ->waitForDialog()
                    ->acceptDialog()
                    ->waitForText('quests approved!')
                    ->assertSee('quests approved!')
                    ->assertDontSee('Quest 1');
        });
    }

    /**
     * Test parent can bulk reject selected completions.
     */
    public function test_parent_can_bulk_reject_completions(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        $completions = [];
        for ($i = 1; $i <= 2; $i++) {
            $quest = Quest::factory()->create(['child_id' => $child->id]);
            $completions[] = QuestCompletion::factory()->pending()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
            ]);
        }

        $this->browse(function (Browser $browser) use ($user, $completions) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card');

            foreach ($completions as $completion) {
                $browser->check("[data-completion-id='{$completion->id}'] input[type='checkbox']");
            }

            $browser->pause(300)
                    ->click('#bulkRejectBtn')
                    ->waitForDialog()
                    ->acceptDialog()
                    ->waitForText('quests rejected')
                    ->assertSee('quests rejected');
        });
    }

    /**
     * Test select all checkbox functionality.
     */
    public function test_select_all_checkbox_works(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);

        for ($i = 1; $i <= 3; $i++) {
            $quest = Quest::factory()->create(['child_id' => $child->id]);
            QuestCompletion::factory()->pending()->create([
                'quest_id' => $quest->id,
                'child_id' => $child->id,
            ]);
        }

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->check('#selectAll')
                    ->pause(300)
                    ->assertSee('3 selected')
                    ->uncheck('#selectAll')
                    ->pause(300)
                    ->assertDontSee('selected');
        });
    }

    /**
     * Test approvals page shows stats banner.
     */
    public function test_approvals_page_shows_stats_banner(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create(['child_id' => $child->id]);

        QuestCompletion::factory()->count(5)->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'gold_earned' => 10,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.stats-banner')
                    ->assertSee('5')
                    ->assertSee('Pending')
                    ->assertSee('50')
                    ->assertSee('Gold');
        });
    }

    /**
     * Test empty state when no pending approvals.
     */
    public function test_shows_empty_state_when_no_pending_approvals(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->assertSee('No pending approvals')
                    ->assertSee('All caught up!');
        });
    }

    /**
     * Test approval cards show child name and avatar.
     */
    public function test_approval_cards_show_child_info(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create([
            'user_id' => $user->id,
            'name' => 'Emma',
            'avatar_image' => 'princess_2.png',
        ]);

        $quest = Quest::factory()->create(['child_id' => $child->id]);

        QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->assertSee('Emma')
                    ->assertSourceHas('princess_2.png');
        });
    }

    /**
     * Test approval cards show completion date and gold amount.
     */
    public function test_approval_cards_show_completion_details(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create(['user_id' => $user->id]);
        $quest = Quest::factory()->create([
            'child_id' => $child->id,
            'title' => 'Test Quest',
        ]);

        QuestCompletion::factory()->pending()->create([
            'quest_id' => $quest->id,
            'child_id' => $child->id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => 25,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/parent/approvals')
                    ->waitFor('.approval-card')
                    ->assertSee('Test Quest')
                    ->assertSee('25')
                    ->assertSee(now()->format('M d, Y'));
        });
    }
}
