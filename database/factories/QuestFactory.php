<?php

namespace Database\Factories;

use App\Models\Quest;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quest>
 */
class QuestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quest::class;

    /**
     * Quest title examples for children.
     *
     * @var array
     */
    protected $questTitles = [
        'Make Your Bed',
        'Brush Your Teeth',
        'Clean Your Room',
        'Do Homework',
        'Practice Piano',
        'Feed the Pet',
        'Set the Table',
        'Take Out Trash',
        'Tidy Toys',
        'Water Plants',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'title' => fake()->randomElement($this->questTitles),
            'description' => fake()->optional()->sentence(),
            'gold_reward' => fake()->numberBetween(5, 50),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the quest should belong to a specific child.
     */
    public function forChild(Child $child): static
    {
        return $this->state(fn (array $attributes) => [
            'child_id' => $child->id,
        ]);
    }

    /**
     * Indicate that the quest should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the quest should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the quest should have a specific title.
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Indicate that the quest should have a specific gold reward.
     */
    public function withGoldReward(int $gold): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_reward' => $gold,
        ]);
    }
}
