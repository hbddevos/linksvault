<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'role' => TeamRole::Member,
            'expires_at' => null,
            'accepted_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function expiresIn(int $value, string $unit = 'days'): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->add($unit, $value),
        ]);
    }
}
