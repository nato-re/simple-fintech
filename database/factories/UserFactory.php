<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = Faker::create('pt_BR');
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => $faker->unique()->cpf(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::CUSTOMER,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    public function storeKeeper(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::STORE_KEEPER,
        ]);
    }
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::CUSTOMER,
        ]);
    }

    public function storeKeeperWithWallet($balance = 0): static {
        return $this->storeKeeper()->afterCreating(function (User $user) use ($balance) {
            $user->wallets()->create([
                'balance' => $balance,
            ]);
        });
    }
    public function customerWithWallet($balance = 0): static {
        return $this->customer()->afterCreating(function (User $user) use ($balance) {
            $user->wallets()->create([
                'balance' => $balance,
            ]); 
        });
    }
}
