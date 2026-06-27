<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Notification;
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

    public function test_user_cannot_view_comments_on_ticket_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
        ]);
        Comment::factory()->create([
            'organization_id' => $orgB->id,
            'ticket_id' => $foreignTicket->id,
            'user_id' => $foreignTicket->requester_id,
            'is_internal' => false,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/tickets/{$foreignTicket->id}/comments")
            ->assertNotFound();
    }

    public function test_user_cannot_view_activity_on_ticket_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => User::factory()->create(['organization_id' => $orgB->id])->id,
        ]);
        ActivityLog::create([
            'organization_id' => $orgB->id,
            'ticket_id' => $foreignTicket->id,
            'user_id' => $foreignTicket->requester_id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/tickets/{$foreignTicket->id}/activity")
            ->assertNotFound();
    }

    public function test_user_cannot_view_notification_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => $userB->id,
        ]);

        Notification::create([
            'organization_id' => $orgB->id,
            'user_id' => $userB->id,
            'ticket_id' => $foreignTicket->id,
            'type' => 'ticket_replied',
            'data' => ['subject' => $foreignTicket->subject],
        ]);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_user_cannot_mark_read_notification_from_another_org(): void
    {
        [$orgA, $orgB] = [Organization::factory()->create(), Organization::factory()->create()];

        $userA = User::factory()->admin()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        $foreignTicket = Ticket::factory()->create([
            'organization_id' => $orgB->id,
            'requester_id' => $userB->id,
        ]);

        $foreignNotification = Notification::create([
            'organization_id' => $orgB->id,
            'user_id' => $userB->id,
            'ticket_id' => $foreignTicket->id,
            'type' => 'ticket_replied',
            'data' => ['subject' => $foreignTicket->subject],
        ]);

        $this->actingAs($userA, 'sanctum')
            ->patchJson("/api/notifications/{$foreignNotification->id}/read")
            ->assertNotFound();
    }

    public function test_customer_cannot_patch_ticket(): void
    {
        $org = Organization::factory()->create();
        $customer = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}", ['status' => 'resolved'])
            ->assertForbidden();
    }

    public function test_customer_cannot_assign_ticket(): void
    {
        $org = Organization::factory()->create();
        $customer = User::factory()->create(['organization_id' => $org->id]);
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assignee_id' => $agent->id])
            ->assertForbidden();
    }

    public function test_customer_cannot_delete_ticket(): void
    {
        $org = Organization::factory()->create();
        $customer = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_admin_can_delete_ticket(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => User::factory()->create(['organization_id' => $org->id])->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_notification_created_on_assign(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $org->id]);
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => User::factory()->create(['organization_id' => $org->id])->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assignee_id' => $agent->id])
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $agent->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigned',
        ]);
    }

    public function test_notification_on_assign_only_to_counterpart(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => User::factory()->create(['organization_id' => $org->id])->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assignee_id' => $admin->id])
            ->assertOk();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigned',
        ]);
    }

    public function test_notification_on_reply_to_counterpart(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $customer = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", ['body' => 'Update'])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_replied',
        ]);
    }

    public function test_actor_does_not_receive_reply_notification(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->agent()->create(['organization_id' => $org->id]);
        $customer = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", ['body' => 'Update'])
            ->assertCreated();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $agent->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_replied',
        ]);
    }
}
