<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CharacterLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['level' => 1, 'experience_required' => 0],
            ['level' => 2, 'experience_required' => 25],
            ['level' => 3, 'experience_required' => 35],
            ['level' => 4, 'experience_required' => 50],
            ['level' => 5, 'experience_required' => 75],
            ['level' => 6, 'experience_required' => 110],
            ['level' => 7, 'experience_required' => 165],
            ['level' => 8, 'experience_required' => 245],
            ['level' => 9, 'experience_required' => 365],
            ['level' => 10, 'experience_required' => 545],
            ['level' => 11, 'experience_required' => 815],
            ['level' => 12, 'experience_required' => 1220],
            ['level' => 13, 'experience_required' => 1830],
            ['level' => 14, 'experience_required' => 2745],
            ['level' => 15, 'experience_required' => 4115],
            ['level' => 16, 'experience_required' => 6170],
            ['level' => 17, 'experience_required' => 9255],
            ['level' => 18, 'experience_required' => 13880],
            ['level' => 19, 'experience_required' => 20820],
            ['level' => 20, 'experience_required' => 31230],
        ];

        foreach ($levels as $level) {
            DB::table('character_levels')->updateOrInsert(
                ['level' => $level['level']],
                $level
            );
        }

        $this->command->info('✅ 20 character levels seeded successfully!');
    }
}
