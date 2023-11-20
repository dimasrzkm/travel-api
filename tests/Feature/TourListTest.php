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

    /** @test */
    public function tours_list_sorts_by_starting_date_correctly(): void
    {
        // membuat data dummy untuk travel
        $travel = Travel::factory()->create();
        // membuat data dummy untuk tour
        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        // membuat data dummy untuk tour
        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $earlierTour->id);
        $response->assertJsonPath('data.1.id', $laterTour->id);
    }

    /** @test */
    public function tours_list_sorts_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapLaterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $cheapEarlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $response = $this->get('api/v1/travels/'.$travel->slug.'/tours?sortBy=price&sortOrder=asc');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $cheapEarlierTour->id);
        $response->assertJsonPath('data.1.id', $cheapLaterTour->id);
        $response->assertJsonPath('data.2.id', $expensiveTour->id);
    }

    /** @test */
    public function tours_list_filter_by_price_correctly(): void
    {
        $travel = Travel::factory()->create();
        $expensiveTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 200,
        ]);
        $cheapTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'price' => 100,
        ]);

        $endpoint = 'api/v1/travels/'.$travel->slug.'/tours';

        $response = $this->get($endpoint.'?priceFrom=100');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceFrom=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceFrom=250');
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?priceTo=200');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceTo=150');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $cheapTour->id]);
        $response->assertJsonMissing(['id' => $expensiveTour->id]);

        $response = $this->get($endpoint.'?priceTo=50');
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?priceFrom=150&priceTo=250');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $cheapTour->id]);
        $response->assertJsonFragment(['id' => $expensiveTour->id]);
    }

    /** @test */
    public function tours_list_filters_by_starting_date_correctly(): void
    {
        $travel = Travel::factory()->create();
        $laterTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now()->addDays(2),
            'ending_date' => now()->addDays(3),
        ]);
        $earlierTour = Tour::factory()->create([
            'travel_id' => $travel->id,
            'starting_date' => now(),
            'ending_date' => now()->addDays(1),
        ]);

        $endpoint = 'api/v1/travels/'.$travel->slug.'/tours';

        $response = $this->get($endpoint.'?dateFrom='.now());
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get($endpoint.'?dateFrom='.now()->addDays());
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get($endpoint.'?dateFrom='.now()->addDays(5));
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?dateTo='.now()->addDays(5));
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);

        $response = $this->get($endpoint.'?dateTo='.now()->addDays());
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $earlierTour->id]);
        $response->assertJsonMissing(['id' => $laterTour->id]);

        $response = $this->get($endpoint.'?dateTo='.now()->subDays());
        $response->assertJsonCount(0, 'data');

        $response = $this->get($endpoint.'?dateFrom='.now()->addDays().'&dateTo='.now()->addDays(5));
        $response->assertJsonCount(1, 'data');
        $response->assertJsonMissing(['id' => $earlierTour->id]);
        $response->assertJsonFragment(['id' => $laterTour->id]);
    }

    /** @test */
    public function tour_list_returns_validation_errors(): void
    {
        $travel = Travel::factory()->create();

        $response = $this->getJson('api/v1/travels/'.$travel->slug.'/tours?dateFrom=abcd');
        $response->assertStatus(422);

        $response = $this->getJson('api/v1/travels/'.$travel->slug.'/tours?priceFrom=abcd');
        $response->assertStatus(422);
    }
}
