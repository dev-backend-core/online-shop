<?php

namespace App\Actions;

use App\Models\Movie;
use App\Models\Show;
use App\Services\KinopoiskService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SearchMoviesAction
{
    
    public function __construct(
        private readonly KinopoiskService $kinopoiskService)
    {}

    public function execute(int $kinopoiskId)
    {
        try{
            $details = $this->kinopoiskService->searchDetails($kinopoiskId);

            if (!$details) {
                return null;
            }

            $movie = DB::transaction(function () use ($details,$kinopoiskId)
            {
                $movieData = KinopoiskService::fromKinopoiskArray($details);
              
                $movie = Movie::updateOrCreate(
                    ['kinopoisk_id' => $kinopoiskId],
                    $movieData
                );

                if ($movie->shows()->count() === 0) {
                    self::generateDefaultShows($movie);
                }

                return $movie;
            });

            Cache::forget('all_movies');
            return $movie;

        } catch (\Throwable $e) {
            Log::error("Сбой транзакции при добавлении фильма ID {$kinopoiskId}: " . $e->getMessage());

            return null;
        }
    }

    public static function generateDefaultShows(Movie $movie): void
    {
        $schedule = [
            now()->format('Y-m-d') => ['10:00'],
            now()->addDay()->format('Y-m-d') => ['18:00', '22:00'],
        ];

        foreach ($schedule as $date => $times) {
            foreach ($times as $time) {
                // Формируем дату и время в формате ISO 8601 (UTC / Z)
                $dateTime = now()->parse("{$date} {$time}")->toIso8601ZuluString();

               Show::updateOrCreate(
                    [
                        'movie_id'   => $movie->id,
                        'start_time' => $dateTime,
                    ],
                    [
                        'price' => 350.00,
                    ]
                );
            }
        }
    }
}
