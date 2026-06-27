<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    public function index(Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorize('view', $ticket);

        return ActivityResource::collection(
            $ticket->activities()->with('actor')->latest('created_at')->get()
        );
    }
}
