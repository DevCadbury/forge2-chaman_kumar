<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignee_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('organization_id', $this->user()->organization_id)
                        ->whereIn('role', [UserRole::Admin->value, UserRole::Agent->value]);
                }),
            ],
        ];
    }
}
