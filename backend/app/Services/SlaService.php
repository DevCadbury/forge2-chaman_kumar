<?php

namespace App\Services;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

class SlaService
{
    public function summary(Ticket $ticket): ?array
    {
        $priority = $ticket->priority?->value;

        $policy = SlaPolicy::where('priority', $priority)->first();

        if (! $policy) {
            return null;
        }

        $responseDue = $ticket->created_at?->copy()->addMinutes($policy->response_minutes);
        $resolutionDue = $ticket->created_at?->copy()->addMinutes($policy->resolution_minutes);

        $now = Carbon::now();
        $responseDone = $ticket->first_responded_at ?? ($ticket->resolved_at);
        $resolutionDone = $ticket->resolved_at;

        return [
            'priority' => $priority,
            'response_minutes' => $policy->response_minutes,
            'resolution_minutes' => $policy->resolution_minutes,
            'response_due_at' => $responseDue?->toIso8601String(),
            'resolution_due_at' => $resolutionDue?->toIso8601String(),
            'response_breached' => $this->breached($responseDue, $responseDone, $now),
            'resolution_breached' => $this->breached($resolutionDue, $resolutionDone, $now),
            'resolution_minutes_remaining' => $resolutionDone
                ? null
                : ($resolutionDue ? $now->diffInMinutes($resolutionDue, false) : null),
        ];
    }

    protected function breached(?Carbon $dueAt, ?Carbon $completedAt, Carbon $now): bool
    {
        if (! $dueAt) {
            return false;
        }

        $reference = $completedAt ?? $now;

        return $reference->greaterThan($dueAt);
    }
}
