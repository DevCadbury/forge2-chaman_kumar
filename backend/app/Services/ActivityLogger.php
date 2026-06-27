<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function record(Ticket $ticket, string $action, array $meta = []): ActivityLog
    {
        return ActivityLog::create([
            'organization_id' => $ticket->organization_id,
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
