<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Menggantikan factory bawaan Laravel agar cocok dengan skema users KasKu.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'username' => 'user_' . fake()->unique()->numerify('#####'),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('Passw0rd123'),
        ];
    }

    // role dan is_active tidak fillable, tetapi Factory membuat model dalam mode unguarded,
    // jadi state di bawah ini tetap tersimpan.
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
