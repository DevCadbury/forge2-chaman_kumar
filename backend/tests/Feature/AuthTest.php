<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_organization_and_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'organization_name' => 'New Co',
            'name' => 'Owner',
            'email' => 'owner@new.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role', 'organization_id']]);

        $this->assertSame(UserRole::Admin->value, $response->json('user.role'));
        $this->assertDatabaseHas('organizations', ['name' => 'New Co']);
    }

    public function test_login_returns_token(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'user@test.test',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@test.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create([
            'organization_id' => $org->id,
            'email' => 'user@test.test',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@test.test',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_protected_route_requires_auth(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }
}
