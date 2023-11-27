<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Travel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTourTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function public_user_cannot_access_adding_tour(): void
    {
        $travel = Travel::factory()->create();

        $response = $this->postJson('api/v1/admin/travels/'.$travel->id.'/tours');
        $response->assertStatus(401);
    }

    /** @test */
    public function non_admin_cannot_access_adding_tour(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'editor')->value('id'));

        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels/'.$travel->id.'/tours');
        $response->assertStatus(403);
    }

    /** @test */
    public function saves_tour_successfully_with_valid_data(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->value('id'));

        $travel = Travel::factory()->create();

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels/'.$travel->id.'/tours', [
            'name' => 'Tour name',
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($user)->postJson('api/v1/admin/travels/'.$travel->id.'/tours', [
            'name' => 'Tour Name',
            'starting_date' => now()->toDateString(),
            'ending_date' => now()->addDays(3)->toDateString(),
            'price' => 123.45,
        ]);
        $response->assertStatus(201);

        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours');
        $response->assertJsonFragment(['name' => 'Tour Name']);
    }
}
