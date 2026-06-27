<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Ticket;

class NotificationService
{
    public function notify(int $userId, Ticket $ticket, string $type, array $data = []): void
    {
        Notification::create([
            'organization_id' => $ticket->organization_id,
            'user_id' => $userId,
            'ticket_id' => $ticket->id,
            'type' => $type,
            'data' => array_merge(['subject' => $ticket->subject], $data) ?: null,
        ]);
    }
}
