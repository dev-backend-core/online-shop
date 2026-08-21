<?php

namespace Database\Factories;

use App\Models\Show;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Movie;

/**
 * @extends Factory<Show>
 */
class ShowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id'   => Movie::factory(),
            'start_time' => fake()->dateTimeBetween('now', '+7 days'),
            'price'      => fake()->randomElement([250.00, 300.00, 350.00, 450.00, 500.00]),
        ];
    }
}
