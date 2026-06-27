<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Agent = 'agent';
    case Customer = 'customer';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admin, self::Agent], true);
    }
}
