<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }
}
