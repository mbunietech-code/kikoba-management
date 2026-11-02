<?php

namespace Database\Factories;

use App\Models\CommunityContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityContribution>
 */
class CommunityContributionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fund' => fake()->randomElement(CommunityContribution::FUNDS),
            'amount' => CommunityContribution::DEFAULT_AMOUNT,
            'period' => now()->format('Y-m'),
            'paid_on' => now()->toDateString(),
            'status' => 'recorded',
        ];
    }

    public function dharura(): static
    {
        return $this->state(fn () => ['fund' => 'dharura']);
    }

    public function jamii(): static
    {
        return $this->state(fn () => ['fund' => 'jamii']);
    }
}
