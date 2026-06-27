<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $admin;

    private User $agent;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->admin = User::factory()->admin()->create(['organization_id' => $this->org->id]);
        $this->agent = User::factory()->agent()->create(['organization_id' => $this->org->id]);
        $this->customer = User::factory()->create(['organization_id' => $this->org->id]);
    }

    public function test_customer_can_create_ticket(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'Cannot log in',
                'description' => 'Password reset fails',
                'priority' => 'high',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_filters_and_search_apply(): void
    {
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Billing problem',
            'status' => 'open',
            'priority' => 'urgent',
        ]);
        Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'How to export',
            'status' => 'closed',
            'priority' => 'low',
        ]);

        $this->actingAs($this->agent, 'sanctum')
            ->getJson('/api/tickets?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->agent, 'sanctum')
            ->getJson('/api/tickets?q=export')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'How to export');
    }

    public function test_agent_can_assign_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assignee_id' => $this->agent->id])
            ->assertOk()
            ->assertJsonPath('data.assignee_id', $this->agent->id);

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'assigned',
        ]);
    }

    public function test_customer_cannot_update_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
        ]);

        $this->actingAs($this->customer, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'resolved'])
            ->assertForbidden();
    }

    public function test_resolving_ticket_sets_resolved_at(): void
    {
        $ticket = Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'status' => 'open',
            'resolved_at' => null,
        ]);

        $this->actingAs($this->agent, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertNotNull($ticket->fresh()->resolved_at);
    }
}
