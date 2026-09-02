<?php

namespace App\Actions;

use App\Models\Movie;
use App\Models\Show;
use App\Services\KinopoiskService;
use Carbon\Carbon;

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

    /**
     * Главный метод выполнения бизнес-логики
     */
    public function execute(){

        $movieData = $this->kinopoiskService->getPremieres();

        foreach($movieData as $item){
            $movie = Movie::updateOrCreate(
                ['kinopoisk_id' => $item['kinopoisk_id']],
                $item
            );
        }

        foreach ($this->dateTime as $date => $sessions) {
            foreach ($sessions as $session) {
                // 1. Приводим дату из формата ISO (2026-12-04T18:00:00.000Z) в формат Carbon/MySQL
                $startTime = Carbon::parse($session['time']);
            
                Show::updateOrCreate([
                    'movie_id'   => $movie->id,  
                    'start_time' => $startTime, 
                ],
                [
                    'price'      => 350.00,
                ]);
            }
        }
     
    }
}
