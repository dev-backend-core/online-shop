<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kinopoisk_id' => fake()->unique()->numberBetween(100, 999999),
            'title'        => fake()->sentence(1),
            'description'  => fake()->paragraph(),
            'duration_min' => fake()->numberBetween(90, 180),
            'poster_url'   => fake()->imageUrl(300, 450, 'movies'),
            'rating'       => fake()->randomFloat(1, 5, 9.5),
            'genres'       => [fake()->randomElement(['комедия', 'драма', 'боевик', 'ужасы'])],
            'year' => fake()->numberBetween(2000,2026),
        ];
    }
}
