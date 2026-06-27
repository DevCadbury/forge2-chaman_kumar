<?php

namespace App\Http\Resources;

use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'tags' => $this->tags ?? [],
            'requester' => new UserResource($this->whenLoaded('requester')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'requester_id' => $this->requester_id,
            'assignee_id' => $this->assignee_id,
            'comments_count' => $this->whenCounted('comments'),
            'first_responded_at' => $this->first_responded_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'sla' => $this->when(
                $request->routeIs('tickets.show'),
                fn () => app(SlaService::class)->summary($this->resource)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
