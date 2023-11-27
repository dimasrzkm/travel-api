<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTravelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function public_user_cannot_access_adding_travel(): void
    {
        $response = $this->postJson('api/v1/admin/travels');
        $response->assertStatus(401);
    }

    /** @test */
    public function non_admin_user_cannot_access_adding_travel(): void
    {
        $this->seed(RoleSeeder::class); // mengeksekusi seeder roles

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels');
        $response->assertStatus(403);
    }

    /** @test */
    public function saves_travel_succesfully_with_valid_data(): void
    {
        $this->seed(RoleSeeder::class); // mengeksekusi seeder roles

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels', [
            'name' => 'Travel Name',
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels', [
            'name' => 'Travel Name',
            'is_public' => 1,
            'description' => 'some description',
            'number_of_days' => 5,
        ]);
        $response->assertStatus(201);

        $response = $this->get('api/v1/travels');
        $response->assertJsonFragment(['name' => 'Travel Name']);
    }
}
