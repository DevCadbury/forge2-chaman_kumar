<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
