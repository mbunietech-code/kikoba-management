<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_number' => 'MBR-'.fake()->unique()->numerify('######'),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('071#######'),
            'registration_date' => now()->toDateString(),
            'status' => 'active',
            'avatar_color' => '#0f766e',
        ];
    }
}
