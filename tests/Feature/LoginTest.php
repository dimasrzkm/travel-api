<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token']);
    }

    /** @test */
    public function login_returns_errors_with_invalid_credentials(): void
    {
        $response = $this->post('api/v1/login', [
            'email' => 'nonexisting@gmail.com',
            'password' => 'password',
        ]);
        $response->assertStatus(422);
    }
}
