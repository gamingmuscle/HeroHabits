<?php

namespace Database\Seeders;

use App\Models\CharacterTrait;
use Illuminate\Database\Seeder;

class TraitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $traits = [
            [
                'name' => 'Bravery',
                'description' => 'The courage to face challenges and take risks despite fear.',
                'icon' => '🦁',
                'sort_order' => 1,
            ],
            [
                'name' => 'Kindness',
                'description' => 'Showing compassion and empathy towards others.',
                'icon' => '💖',
                'sort_order' => 2,
            ],
            [
                'name' => 'Responsibility',
                'description' => 'Being accountable for one\'s actions and duties.',
                'icon' => '🎯',
                'sort_order' => 3,
            ],
            [
                'name' => 'Perseverance',
                'description' => 'Finishing tasks, not giving up.',
                'icon' => '💪',
                'sort_order' => 4,
            ],
            [
                'name' => 'Curiosity',
                'description' => 'Asking questions, learning and exploring.',
                'icon' => '🔍',
                'sort_order' => 5,
            ],
        ];

        foreach ($traits as $trait) {
            CharacterTrait::firstOrCreate(
                ['name' => $trait['name']],
                $trait
            );
        }

        $this->command->info('✅ 5 traits seeded successfully!');
    }
}

