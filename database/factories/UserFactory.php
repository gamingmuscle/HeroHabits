<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'displayname' => fake()->name(),
            'password' => Hash::make('password'), // default password
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user should have a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Indicate that the user should have a specific username.
     */
    public function withUsername(string $username): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => $username,
        ]);
    }

    /**
     * Indicate that the user should have a specific display name.
     */
    public function withDisplayname(string $displayname): static
    {
        return $this->state(fn (array $attributes) => [
            'displayname' => $displayname,
        ]);
    }
}
