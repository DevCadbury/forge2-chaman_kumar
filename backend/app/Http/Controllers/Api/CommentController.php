<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function __construct(
        protected ActivityLogger $activity,
        protected NotificationService $notifications,
    ) {}

    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('view', $ticket);

        $query = $ticket->comments()->with('author')->oldest();

        if (! $request->user()->isStaff()) {
            $query->where('is_internal', false);
        }

        return CommentResource::collection($query->get());
    }

    public function store(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('comment', $ticket);

        $isInternal = $request->boolean('is_internal');

        if ($isInternal) {
            $this->authorize('addInternalNote', $ticket);
        }

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'is_internal' => $isInternal,
        ]);

        if (! $isInternal && $request->user()->isStaff() && ! $ticket->first_responded_at) {
            $ticket->forceFill(['first_responded_at' => now()])->save();
        }

        $this->activity->record($ticket, $isInternal ? 'internal_note' : 'replied');

        $this->notifyCounterpart($ticket, $request->user()->id, $isInternal);

        return (new CommentResource($comment->load('author')))
            ->response()
            ->setStatusCode(201);
    }

    protected function notifyCounterpart(Ticket $ticket, int $authorId, bool $isInternal): void
    {
        if ($isInternal) {
            if ($ticket->assignee_id && $ticket->assignee_id !== $authorId) {
                $this->notifications->notify($ticket->assignee_id, $ticket, 'internal_note_added');
            }

            return;
        }

        $recipientId = $authorId === $ticket->requester_id
            ? $ticket->assignee_id
            : $ticket->requester_id;

        if ($recipientId && $recipientId !== $authorId) {
            $this->notifications->notify($recipientId, $ticket, 'ticket_replied');
        }
    }
}
