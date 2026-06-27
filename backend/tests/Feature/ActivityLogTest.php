<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_create_creates_activity_log_with_action_created(): void
    {
        $org = Organization::factory()->create();
        $customer = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'New issue',
                'description' => 'Something broke',
            ])->assertCreated();

        $ticket = Ticket::latest('id')->first();

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'created',
        ]);
    }

    public function test_ticket_update_creates_activity_log(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $customer = User::factory()->create(['organization_id' => $org->id]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
            'status' => 'open',
        ]);

        $this->actingAs($agent, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'resolved'])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_comment_create_creates_activity_log_with_action_replied(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $customer = User::factory()->create(['organization_id' => $org->id]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Looking into this',
            ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'replied',
        ]);
    }

    public function test_activity_logs_are_tenant_scoped(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        $ticketA = Ticket::factory()->create([
            'organization_id' => $orgA->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgA->id])->id,
        ]);
        $ticketB = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => $userB->id,
        ]);

        ActivityLog::create([
            'organization_id' => $orgA->id,
            'ticket_id' => $ticketA->id,
            'user_id' => $userA->id,
            'action' => 'created',
            'created_at' => now(),
        ]);
        ActivityLog::create([
            'organization_id' => $orgB->id,
            'ticket_id' => $ticketB->id,
            'user_id' => $userB->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        // User from orgA cannot see orgB's ticket activity (404 on cross-org ticket)
        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/tickets/{$ticketB->id}/activity")
            ->assertNotFound();

        // User from orgA can see own ticket activity
        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/tickets/{$ticketA->id}/activity")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
