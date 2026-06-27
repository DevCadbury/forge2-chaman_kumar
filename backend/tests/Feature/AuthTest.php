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

    public function test_logout_revokes_token(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $token = $user->createToken('test')->plainTextToken;

        // Authenticated request works before logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/tickets')
            ->assertOk();

        // Logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();

        // Verify the access token has been deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_me_returns_current_user_with_org(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->admin()->create([
            'organization_id' => $org->id,
            'email' => 'me@test.test',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'me@test.test')
            ->assertJsonPath('data.role', UserRole::Admin->value)
            ->assertJsonPath('data.organization_id', $org->id);
    }

    public function test_register_validation_errors_missing_email(): void
    {
        $this->postJson('/api/register', [
            'organization_name' => 'Co',
            'name' => 'Owner',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validation_password_mismatch(): void
    {
        $this->postJson('/api/register', [
            'organization_name' => 'Co',
            'name' => 'Owner',
            'email' => 'mismatch@test.test',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
