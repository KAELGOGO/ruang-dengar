<?php

namespace Database\Factories;

use App\Models\ListenerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_USER,
            'birth_date' => now()->subYears(20)->toDateString(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function age(int $years): static
    {
        return $this->state(fn () => ['birth_date' => now()->subYears($years)->subMonths(1)->toDateString()]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    /** A listener with a profile; pass a credential level to make it admin-approved. */
    public function listener(?string $credential = null): static
    {
        return $this->state(fn () => ['role' => User::ROLE_LISTENER])
            ->afterCreating(function (User $user) use ($credential) {
                $profile = $user->listenerProfile()->create(['bio' => fake()->sentence()]);
                if ($credential) {
                    $profile->forceFill([
                        'credential_level' => $credential,
                        'verification_status' => ListenerProfile::APPROVED,
                        'verified_at' => now(),
                    ])->save();
                }
            });
    }
}
