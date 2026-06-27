<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\Organization;
use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'response_minutes' => fake()->numberBetween(30, 240),
            'resolution_minutes' => fake()->numberBetween(120, 720),
        ];
    }
}
