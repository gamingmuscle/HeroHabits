<?php

namespace App\Console\Commands;

use App\Models\Child;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugChildData extends Command
{
    protected $signature = 'debug:child-data';
    protected $description = 'Debug child data in database';

    public function handle()
    {
        $this->info('=== USERS TABLE ===');
        $users = User::all();
        foreach ($users as $user) {
            $this->line("ID: {$user->id} | Username: {$user->username} | Display: {$user->displayname}");
        }
        $this->newLine();

        $this->info('=== CHILDREN TABLE (via Eloquent) ===');
        $children = Child::all();
        foreach ($children as $child) {
            $this->line("ID: {$child->id} | Name: {$child->name} | user_id: " . ($child->user_id ?? 'NULL'));
        }
        $this->newLine();

        $this->info('=== CHILDREN TABLE (via DB query) ===');
        $childrenRaw = DB::table('children')->get();
        foreach ($childrenRaw as $child) {
            $this->line("ID: {$child->id} | Name: {$child->name} | user_id: " . ($child->user_id ?? 'NULL'));
        }
        $this->newLine();

        $this->info('=== QUESTS TABLE ===');
        $quests = DB::table('quests')->get();
        foreach ($quests as $quest) {
            $this->line("ID: {$quest->id} | Title: {$quest->title} | user_id: {$quest->user_id} | Active: " . ($quest->is_active ? 'YES' : 'NO'));
        }

        return 0;
    }
}
