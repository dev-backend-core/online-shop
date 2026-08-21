<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'show_id'   => Show::factory(),
            'seat_id'   => Seat::factory(),
            'status'  => fake()->randomElement(['reserved', 'paid', 'cancelled']),
            'price'      => fake()->randomElement([2250.00, 2300.00, 2350.00, 4250.00, 2500.00]),
        ];
    }
}
