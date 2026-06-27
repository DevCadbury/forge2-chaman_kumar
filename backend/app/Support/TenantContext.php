<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class TenantContext
{
    protected static ?int $organizationId = null;

    protected static bool $overridden = false;

    public static function set(?int $organizationId): void
    {
        static::$organizationId = $organizationId;
        static::$overridden = true;
    }

    public static function clear(): void
    {
        static::$organizationId = null;
        static::$overridden = false;
    }

    public static function id(): ?int
    {
        if (static::$overridden) {
            return static::$organizationId;
        }

        return Auth::user()?->organization_id;
    }
}
