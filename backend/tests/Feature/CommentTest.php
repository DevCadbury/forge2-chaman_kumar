<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $agent;

    private User $customer;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->agent = User::factory()->agent()->create(['organization_id' => $this->org->id]);
        $this->customer = User::factory()->create(['organization_id' => $this->org->id]);
        $this->ticket = Ticket::factory()->create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'assignee_id' => $this->agent->id,
        ]);
    }

    public function test_internal_notes_are_hidden_from_customer(): void
    {
        Comment::factory()->create([
            'organization_id' => $this->org->id,
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'is_internal' => true,
        ]);
        Comment::factory()->create([
            'organization_id' => $this->org->id,
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent->id,
            'is_internal' => false,
        ]);

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->agent, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_cannot_create_internal_note(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'secret',
                'is_internal' => true,
            ])
            ->assertForbidden();
    }

    public function test_public_staff_reply_sets_first_response(): void
    {
        $this->assertNull($this->ticket->first_responded_at);

        $this->actingAs($this->agent, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'On it!',
            ])
            ->assertCreated();

        $this->assertNotNull($this->ticket->fresh()->first_responded_at);
    }

    public function test_reply_notifies_counterpart(): void
    {
        $this->actingAs($this->agent, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", ['body' => 'Update'])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'type' => 'ticket_replied',
        ]);
    }
}
