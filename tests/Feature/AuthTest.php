<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'created_at',
                'updated_at',
            ],
            'token',
        ]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => bcrypt('invalidpassword'),
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_logout_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/auth/logout');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function test_logout_with_invalid_token(): void
    {
        $token = 'this_is_an_invalid_token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/auth/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_me_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/auth/me');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_me_with_invalid_token(): void
    {
        $token = 'this_is_an_invalid_token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('api/auth/me');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_register_with_valid_data(): void
    {
        $response = $this->postJson('api/auth/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_register_with_invalid_data(): void
    {
        $response = $this->postJson('api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertJsonStructure([
            "success",
            "error" => [
                "message",
                "fields" => [
                    "name",
                    "email",
                    "password",
                ],
            ],
        ]);
        $this->assertFalse($response->json('success'));
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_register_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'testuser@example.com',
        ]);

        $response = $this->postJson('api/auth/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure([
            "success",
            "error" => [
                "message",
                "fields" => [
                    "email",
                ],
            ],
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('Validation Error', $response->json('error.message'));
        $this->assertEquals('The email has already been taken.', $response->json('error.fields.email.0'));
    }

    public function test_login_with_admin_user(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'created_at',
                'updated_at',
            ],
            'token',
        ]);
        $this->assertEquals('admin', $response->json('user.role'));
    }
}
