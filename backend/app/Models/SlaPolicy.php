<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'priority',
        'response_minutes',
        'resolution_minutes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
        ];
    }
}
