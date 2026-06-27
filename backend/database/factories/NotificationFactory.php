<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'type' => fake()->randomElement(['ticket_replied', 'ticket_assigned', 'internal_note_added']),
            'data' => ['subject' => fake()->sentence(4)],
            'read_at' => null,
        ];
    }
}
