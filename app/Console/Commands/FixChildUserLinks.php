<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Models\User;
use Illuminate\Console\Command;

class FixChildUserLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:child-user-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix children with null user_id by linking them to existing users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orphanedChildren = Child::whereNull('user_id')->get();

        if ($orphanedChildren->isEmpty()) {
            $this->info('✅ No orphaned children found. All children are properly linked to parents.');
            return 0;
        }

        $this->warn("Found {$orphanedChildren->count()} children with no parent link.");
        $this->newLine();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('❌ No parent users found in the database.');
            return 1;
        }

        // If only one user, auto-assign
        if ($users->count() === 1) {
            $user = $users->first();

            if ($this->confirm("Link all {$orphanedChildren->count()} children to user '{$user->displayname}' ({$user->username})?")) {
                foreach ($orphanedChildren as $child) {
                    $child->update(['user_id' => $user->id]);
                    $this->info("✓ Linked '{$child->name}' to '{$user->displayname}'");
                }

                $this->newLine();
                $this->info("✅ Successfully linked {$orphanedChildren->count()} children!");
                return 0;
            } else {
                $this->comment('Operation cancelled.');
                return 0;
            }
        }

        // Multiple users - ask for each child
        $this->info('Multiple parent accounts found. You\'ll need to assign each child.');
        $this->newLine();

        foreach ($orphanedChildren as $child) {
            $this->line("Child: {$child->name} (ID: {$child->id}, Age: {$child->age})");

            $choices = $users->mapWithKeys(function ($user) {
                return [$user->id => "{$user->displayname} ({$user->username})"];
            })->toArray();

            $choices['skip'] = 'Skip this child';

            $selectedUserId = $this->choice(
                'Which parent should this child belong to?',
                $choices,
                'skip'
            );

            if ($selectedUserId !== 'skip') {
                $child->update(['user_id' => $selectedUserId]);
                $user = $users->firstWhere('id', $selectedUserId);
                $this->info("✓ Linked '{$child->name}' to '{$user->displayname}'");
            } else {
                $this->comment("⊘ Skipped '{$child->name}'");
            }

            $this->newLine();
        }

        $remaining = Child::whereNull('user_id')->count();

        if ($remaining === 0) {
            $this->info('✅ All children are now linked to parents!');
        } else {
            $this->warn("⚠️  {$remaining} children still unlinked.");
        }

        return 0;
    }
}
