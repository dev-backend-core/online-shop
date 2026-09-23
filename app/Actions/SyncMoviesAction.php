<?php

namespace App\Actions;

use App\Models\Movie;
use App\Models\Show;
use App\Services\KinopoiskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMoviesAction
{
    private array $dateTime;

   // Подключаем KinopoiskService через автоматический DI Laravel
    public function __construct(
        private readonly KinopoiskService $kinopoiskService
    ) {
        $this->dateTime = [
            "2026-12-04"=> [
                ["time" => "2026-12-04T10:00:00.000Z"],
                ["time" => "2026-12-04T12:00:00.000Z"],
                ["time"=> "2026-12-04T21:00:00.000Z"]
            ]
        ];
    }

   
    public function execute(){

        $movieData = $this->kinopoiskService->getPremieres();

        if (empty($movieData)) {
            Log::warning('Премьеры не загружены, пустой ответ от API');
            return false;
        }

        // Log::warning('Колич.фильмов: ' . count($movieData));

        try{
            DB::transaction(function () use ($movieData) {
                foreach ($movieData as $item) {
                    $movie = Movie::updateOrCreate(
                        ['kinopoisk_id' => $item['kinopoisk_id']],
                        $item
                    );

                    // Log::warning('Фильм создан/найден ID: ' . $movie->id);

                    foreach ($this->dateTime as $date => $sessions) {
                        foreach ($sessions as $session) {
                            $startTime = Carbon::parse($session['time']);

                            $show = Show::updateOrCreate(
                                [
                                    'movie_id'   => $movie->id,
                                    'start_time' => $startTime,
                                ],
                                [
                                    'price'      => 350.00,
                                ]
                            );
                            
                            // Log::warning('Сеанс создан ID: ' . $show->id);
                        }
                    }
                }
            });

            return true;

        }catch(\Throwable $e){
            Log::error("Сбой транзакции при загрузки всех фильмов" . $e->getMessage());

            return false;
        }
    }
}    
    
