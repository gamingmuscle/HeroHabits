<?php

namespace Database\Factories;

use App\Models\QuestCompletion;
use App\Models\Quest;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestCompletion>
 */
class QuestCompletionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = QuestCompletion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quest = Quest::factory()->create();

        return [
            'quest_id' => $quest->id,
            'child_id' => $quest->child_id,
            'completed_date' => now()->format('Y-m-d'),
            'gold_earned' => $quest->gold_reward,
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the completion should be for a specific quest.
     */
    public function forQuest(Quest $quest): static
    {
        return $this->state(fn (array $attributes) => [
            'quest_id' => $quest->id,
            'child_id' => $quest->child_id,
            'gold_earned' => $quest->gold_reward,
        ]);
    }

    /**
     * Indicate that the completion should be for a specific child.
     */
    public function forChild(Child $child): static
    {
        return $this->state(fn (array $attributes) => [
            'child_id' => $child->id,
        ]);
    }

    /**
     * Indicate that the completion should be accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Accepted',
        ]);
    }

    /**
     * Indicate that the completion should be rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Rejected',
        ]);
    }

    /**
     * Indicate that the completion should be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Pending',
        ]);
    }

    /**
     * Indicate that the completion should be on a specific date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_date' => $date,
        ]);
    }

    /**
     * Indicate that the completion should have a specific gold amount.
     */
    public function withGold(int $gold): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_earned' => $gold,
        ]);
    }
}
