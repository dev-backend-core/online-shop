<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Seat;
use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $movies = Movie::factory(5)->create();
       
        // $user = User::factory()->create([
        //     'email' => 'test@example.com',
        // ]);

        // // 4. Создаем сеансы
        // foreach($movies as $item){
        //     Show::factory()->create([
        //         'movie_id' => $item->id,
        //     ]);
        // }

        $seats = collect();
        for ($row = 1; $row <= 10; $row++) {
            for ($seatNum = 1; $seatNum <= 9; $seatNum++) {
                $seats->push(
                    Seat::create([
                        'row_number'  => $row,
                        'seat_number' => $seatNum,
                    ])
                );
            }
        }

        // 5. Покупаем пару пробных билетов для первого сеанса
        // $show = Show::first();
       
        // Ticket::factory()->create([
        //     'user_id' => $user->id,
        //     'show_id' => $show->id,
        //     'seat_id' => $seats->first()->id,
        //     'price'   => $show->price,
        // ]);
        
        // $user->favoriteMovies()->attach(
        //     $movies->random(2)->pluck('id')
        // );
    }
}
