<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_all_read_marks_all_unread_notifications(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        Notification::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_replied',
            'data' => ['subject' => 'Test'],
        ]);
        Notification::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigned',
            'data' => ['subject' => 'Test'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/notifications/read-all')
            ->assertOk();

        // Verify they're all read
        $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
        $this->assertSame(0, $unreadCount);
    }

    public function test_mark_read_only_works_for_own_notifications(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $otherUser = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $otherUser->id,
        ]);

        $foreignNotification = Notification::create([
            'organization_id' => $org->id,
            'user_id' => $otherUser->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_replied',
            'data' => ['subject' => 'Test'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$foreignNotification->id}/read")
            ->assertForbidden();
    }

    public function test_listing_only_returns_current_user_notifications(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $otherUser = User::factory()->create(['organization_id' => $org->id]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        // Own notifications
        Notification::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_replied',
            'data' => ['subject' => 'Mine'],
        ]);

        // Someone else's
        Notification::create([
            'organization_id' => $org->id,
            'user_id' => $otherUser->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_assigned',
            'data' => ['subject' => 'Theirs'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
