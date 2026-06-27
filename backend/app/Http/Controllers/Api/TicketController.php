<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function __construct(
        protected ActivityLogger $activity,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::query()
            ->with(['requester', 'assignee'])
            ->withCount('comments')
            ->status($request->query('status'))
            ->priority($request->query('priority'))
            ->assignee($request->query('assignee'))
            ->search($request->query('q'))
            ->latest();

        if (! $request->user()->isStaff()) {
            $query->where('requester_id', $request->user()->id);
        }

        return TicketResource::collection($query->paginate(15)->withQueryString());
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create([
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->input('priority', 'medium'),
            'tags' => $request->input('tags', []),
            'requester_id' => $request->user()->id,
            'status' => TicketStatus::Open,
        ]);

        $this->activity->record($ticket, 'created');

        return (new TicketResource($ticket->load(['requester', 'assignee'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket->load(['requester', 'assignee']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $this->authorize('update', $ticket);

        $original = $ticket->only(['status', 'priority']);

        $ticket->fill($request->validated());

        if ($ticket->isDirty('status') && $ticket->status === TicketStatus::Resolved && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();
        }

        $ticket->save();

        if ($ticket->wasChanged('status')) {
            $this->activity->record($ticket, 'status_changed', [
                'from' => $original['status']?->value,
                'to' => $ticket->status?->value,
            ]);
        }

        if ($ticket->wasChanged('priority')) {
            $this->activity->record($ticket, 'priority_changed', [
                'from' => $original['priority']?->value,
                'to' => $ticket->priority?->value,
            ]);
        }

        return new TicketResource($ticket->load(['requester', 'assignee']));
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket): TicketResource
    {
        $this->authorize('assign', $ticket);

        $assigneeId = $request->input('assignee_id');
        $ticket->assignee_id = $assigneeId;
        $ticket->save();

        $this->activity->record($ticket, $assigneeId ? 'assigned' : 'unassigned', [
            'assignee_id' => $assigneeId,
        ]);

        if ($assigneeId && $assigneeId !== $request->user()->id) {
            $this->notifications->notify($assigneeId, $ticket, 'ticket_assigned');
        }

        return new TicketResource($ticket->load(['requester', 'assignee']));
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(null, 204);
    }
}
