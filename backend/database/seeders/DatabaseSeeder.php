<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $acme = $this->seedOrganization('Acme Inc', 'acme.test', primary: true);
        $this->seedOrganization('Globex Corp', 'globex.test', primary: false);

        $this->command?->info('Seeded demo data. Primary admin: admin@acme.test / password');
    }

    protected function seedOrganization(string $name, string $domain, bool $primary): Organization
    {
        $org = Organization::factory()->create(['name' => $name]);

        $this->slaPolicies($org->id);

        $admin = User::factory()->admin()->create([
            'organization_id' => $org->id,
            'name' => 'Admin User',
            'email' => "admin@{$domain}",
        ]);

        $agents = collect(['Alice Agent', 'Bob Agent'])->map(fn ($n, $i) => User::factory()->agent()->create([
            'organization_id' => $org->id,
            'name' => $n,
            'email' => 'agent'.($i + 1)."@{$domain}",
        ]));

        $customers = collect(['Carol Customer', 'Dave Customer'])->map(fn ($n, $i) => User::factory()->role(UserRole::Customer)->create([
            'organization_id' => $org->id,
            'name' => $n,
            'email' => 'customer'.($i + 1)."@{$domain}",
        ]));

        $count = $primary ? 12 : 6;

        for ($i = 0; $i < $count; $i++) {
            $requester = $customers->random();
            $assignee = fake()->boolean(70) ? $agents->random() : null;
            $status = fake()->randomElement(TicketStatus::values());
            $createdAt = Carbon::now()->subDays(rand(0, 6))->subHours(rand(0, 23));

            $ticket = Ticket::factory()->create([
                'organization_id' => $org->id,
                'requester_id' => $requester->id,
                'assignee_id' => $assignee?->id,
                'status' => $status,
                'priority' => fake()->randomElement(TicketPriority::values()),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'first_responded_at' => $assignee ? $createdAt->copy()->addMinutes(rand(15, 240)) : null,
                'resolved_at' => in_array($status, [TicketStatus::Resolved->value, TicketStatus::Closed->value], true)
                    ? $createdAt->copy()->addHours(rand(2, 48))
                    : null,
            ]);

            Comment::factory()->create([
                'organization_id' => $org->id,
                'ticket_id' => $ticket->id,
                'user_id' => $requester->id,
                'is_internal' => false,
            ]);

            if ($assignee) {
                Comment::factory()->create([
                    'organization_id' => $org->id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $assignee->id,
                    'is_internal' => fake()->boolean(40),
                ]);
            }
        }

        return $org;
    }

    protected function slaPolicies(int $orgId): void
    {
        $targets = [
            TicketPriority::Urgent->value => [15, 240],
            TicketPriority::High->value => [60, 480],
            TicketPriority::Medium->value => [240, 1440],
            TicketPriority::Low->value => [480, 2880],
        ];

        foreach ($targets as $priority => [$response, $resolution]) {
            SlaPolicy::create([
                'organization_id' => $orgId,
                'priority' => $priority,
                'response_minutes' => $response,
                'resolution_minutes' => $resolution,
            ]);
        }
    }
}
