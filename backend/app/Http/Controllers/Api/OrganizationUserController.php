<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationUserController extends Controller
{
    public function agents(Request $request): AnonymousResourceCollection
    {
        $agents = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->whereIn('role', [UserRole::Admin->value, UserRole::Agent->value])
            ->orderBy('name')
            ->get();

        return UserResource::collection($agents);
    }
}
