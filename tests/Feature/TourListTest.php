<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\Travel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tours_list_by_travel_slug_returns_correct_tours(): void
    {
        // membuat data dummy untuk travel
        $travel = Travel::factory()->create();
        // membuat data dummy untuk tour beserta foreign id pada travel
        $tour = Tour::factory()->create(['travel_id' => $travel->id]);

        // Mengakses end point travel yang menyediakan tour
        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours');

        // mengecek bahwasannya end point berhasil dengan status 200
        $response->assertStatus(200);
        // mengecek bahwasannya data yang dikembalikan hanya terdapat 1 data
        $response->assertJsonCount(1, 'data');
        // mengecek bahwasannya data pada id sama dengan id tour yang dibuat
        $response->assertJsonFragment(['id' => $tour->id]);
    }

    /** @test */
    public function tour_price_is_shown_correctly(): void
    {
        // membuat data dummy untuk travel
        $travel = Travel::factory()->create();
        // membuat data dummy untuk tour beserta foreign id pada travel dan price custom
        $tour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 123.45,
        ]);

        // Mengakses end point travel yang menyediakan tour
        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours');

        // mengecek bahwasannya end point berhasil dengan status 200
        $response->assertStatus(200);
        // mengecek bahwasannya data yang dikembalikan hanya terdapat 1 data
        $response->assertJsonCount(1, 'data');
        // mengecek bahwasannya data pada price sama dengan price tour
        $response->assertJsonFragment(['price' => '123.45']);
    }

    /** @test */
    public function tours_list_returns_pagination(): void
    {
        // membuat data dummy untuk travel
        $travel = Travel::factory()->create();
        // membuat data dummy untuk tour beserta foreign id pada travel dan price custom
        $tour = Tour::factory(16)->create([
            'travel_id' => $travel->id,
        ]);

        // Mengakses end point travel yang menyediakan tour
        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours');

        // mengecek bahwasannya end point berhasil dengan status 200
        $response->assertStatus(200);
        // mengecek bahwasannya data yang dikembalikan hanya terdapat 1 data
        $response->assertJsonCount(15, 'data');
        // mengecek bahwasannya data yang dikembalikan pada meta.last_page sesuai dengan yang diinginkan
        $response->assertJsonPath('meta.last_page', 2);
    }
}
