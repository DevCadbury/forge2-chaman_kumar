<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected SlaService $sla) {}

    public function metrics(): JsonResponse
    {
        $byStatus = Ticket::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byPriority = Ticket::query()
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $open = Ticket::query()
            ->whereIn('status', [TicketStatus::Open->value, TicketStatus::Pending->value])
            ->count();

        return response()->json([
            'data' => [
                'total' => Ticket::count(),
                'open' => $open,
                'by_status' => $byStatus,
                'by_priority' => $byPriority,
                'avg_first_response_minutes' => $this->avgFirstResponseMinutes(),
                'sla_breach_rate' => $this->slaBreachRate(),
                'created_per_day' => $this->createdPerDay(),
            ],
        ]);
    }

    protected function avgFirstResponseMinutes(): ?float
    {
        $tickets = Ticket::query()->whereNotNull('first_responded_at')->get(['created_at', 'first_responded_at']);

        if ($tickets->isEmpty()) {
            return null;
        }

        $total = $tickets->sum(fn ($t) => $t->created_at->diffInMinutes($t->first_responded_at));

        return round($total / $tickets->count(), 1);
    }

    protected function slaBreachRate(): float
    {
        $tickets = Ticket::query()->get();

        if ($tickets->isEmpty()) {
            return 0.0;
        }

        $breached = $tickets->filter(function (Ticket $ticket) {
            $summary = $this->sla->summary($ticket);

            return $summary && ($summary['response_breached'] || $summary['resolution_breached']);
        })->count();

        return round(($breached / $tickets->count()) * 100, 1);
    }

    protected function createdPerDay(): array
    {
        return Ticket::query()
            ->where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($t) => $t->created_at->toDateString())
            ->map->count()
            ->toArray();
    }
}
