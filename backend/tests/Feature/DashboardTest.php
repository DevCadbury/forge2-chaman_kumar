<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->admin = User::factory()->admin()->create(['organization_id' => $this->org->id]);
    }

    public function test_metrics_are_tenant_scoped(): void
    {
        $orgB = Organization::factory()->create();
        User::factory()->admin()->create(['organization_id' => $orgB->id]);

        Ticket::factory()->count(3)->create([
            'organization_id' => $this->org->id,
            'requester_id' => User::factory()->create(['organization_id' => $this->org->id])->id,
            'status' => TicketStatus::Open,
        ]);
        Ticket::factory()->count(5)->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
            'status' => TicketStatus::Resolved,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $response->assertJsonPath('data.total', 3);
        $response->assertJsonPath('data.open', 3);
    }

    public function test_avg_first_response_returns_correct_minutes(): void
    {
        $customer = User::factory()->create(['organization_id' => $this->org->id]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'created_at' => now()->subMinutes(60),
            'first_responded_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $response->assertJsonPath('data.avg_first_response_minutes', 30);
    }

    public function test_sla_breach_rate_is_accurate(): void
    {
        SlaPolicy::factory()->create([
            'organization_id' => $this->org->id,
            'priority' => TicketPriority::Medium,
            'response_minutes' => 60,
            'resolution_minutes' => 240,
        ]);

        $customer = User::factory()->create(['organization_id' => $this->org->id]);

        // breached: first response > 60 min
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'priority' => TicketPriority::Medium,
            'created_at' => now()->subMinutes(120),
            'first_responded_at' => now()->subMinutes(70),
        ]);

        // not breached: first response < 60 min
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'priority' => TicketPriority::Medium,
            'created_at' => now()->subMinutes(120),
            'first_responded_at' => now()->subMinutes(50),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $response->assertJsonPath('data.sla_breach_rate', 50);
    }

    public function test_sla_breach_rate_zero_with_no_tickets(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $response->assertJsonPath('data.sla_breach_rate', 0);
        $this->assertNull($response->json('data.avg_first_response_minutes'));
    }

    public function test_created_per_day_returns_last_7_days(): void
    {
        $customer = User::factory()->create(['organization_id' => $this->org->id]);

        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'created_at' => now()->subDays(2),
        ]);
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'created_at' => now()->subDays(2),
        ]);
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'created_at' => now()->subDays(8), // outside 7-day window
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $perDay = $response->json('data.created_per_day');
        $this->assertCount(1, $perDay); // only 1 distinct day within 7 days
        $this->assertArrayHasKey(now()->subDays(2)->toDateString(), $perDay);
        $this->assertSame(2, $perDay[now()->subDays(2)->toDateString()]);
    }

    public function test_sla_breach_with_null_first_responded_at(): void
    {
        SlaPolicy::factory()->create([
            'organization_id' => $this->org->id,
            'priority' => TicketPriority::High,
            'response_minutes' => 30,
            'resolution_minutes' => 120,
        ]);

        $customer = User::factory()->create(['organization_id' => $this->org->id]);

        // not resolved, no first response, past due -> response breached
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'priority' => TicketPriority::High,
            'created_at' => now()->subMinutes(60),
            'first_responded_at' => null,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        $response->assertJsonPath('data.sla_breach_rate', 100);
    }

    public function test_sla_breach_with_missing_policy(): void
    {
        $customer = User::factory()->create(['organization_id' => $this->org->id]);

        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $customer->id,
            'priority' => TicketPriority::Low,
            'created_at' => now()->subMinutes(120),
            'first_responded_at' => null,
        ]);

        // No SLA policy for Low priority
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/metrics')
            ->assertOk();

        // Without a policy, SLA summary is null, so no breach is counted
        $response->assertJsonPath('data.sla_breach_rate', 0);
    }
}
