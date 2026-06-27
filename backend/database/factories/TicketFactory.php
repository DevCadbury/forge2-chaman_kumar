<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'subject' => rtrim(fake()->sentence(6), '.'),
            'description' => fake()->paragraphs(2, true),
            'status' => fake()->randomElement(TicketStatus::values()),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'tags' => fake()->randomElements(['billing', 'bug', 'how-to', 'account', 'urgent'], rand(0, 2)),
        ];
    }
}
