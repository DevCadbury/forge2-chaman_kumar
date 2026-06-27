<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_ticket_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/tickets/{$foreignTicket->id}")
            ->assertNotFound();
    }

    public function test_index_only_returns_own_org_tickets(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        Ticket::factory()->count(3)->create([
            'organization_id' => $orgA->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgA->id])->id,
        ]);
        Ticket::factory()->count(5)->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_update_ticket_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->patchJson("/api/tickets/{$foreignTicket->id}", ['status' => 'closed'])
            ->assertNotFound();
    }

    public function test_new_ticket_is_scoped_to_actor_org(): void
    {
        $org = Organization::factory()->create();
        $customer = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'Need help',
                'description' => 'Something broke',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Need help',
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);
    }
}
