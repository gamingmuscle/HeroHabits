<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Child>
 */
class ChildFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Child::class;

    /**
     * Available avatar images for children.
     *
     * @var array
     */
    protected $avatars = [
        'princess_2.png',
        'princess_3.png',
        'princess_3tr.png',
        'princess_laugh.png',
        'knight_girl_2.png',
        'knight_girl_3.png',
        'knight_girl_4.png',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'age' => fake()->numberBetween(4, 12),
            'avatar_image' => fake()->randomElement($this->avatars),
            'pin' => sprintf('%04d', fake()->numberBetween(0, 9999)),
            'gold_balance' => fake()->numberBetween(0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the child should belong to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the child should have a specific PIN.
     */
    public function withPin(string $pin): static
    {
        return $this->state(fn (array $attributes) => [
            'pin' => $pin,
        ]);
    }

    /**
     * Indicate that the child should have a specific name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Indicate that the child should have a specific age.
     */
    public function withAge(int $age): static
    {
        return $this->state(fn (array $attributes) => [
            'age' => $age,
        ]);
    }

    /**
     * Indicate that the child should have a specific gold balance.
     */
    public function withGoldBalance(int $gold): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_balance' => $gold,
        ]);
    }

    /**
     * Indicate that the child should have a specific avatar.
     */
    public function withAvatar(string $avatar): static
    {
        return $this->state(fn (array $attributes) => [
            'avatar_image' => $avatar,
        ]);
    }

    /**
     * Indicate that the child should have zero gold balance.
     */
    public function withNoGold(): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_balance' => 0,
        ]);
    }
}
