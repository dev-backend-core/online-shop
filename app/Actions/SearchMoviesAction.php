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
        $details = $this->kinopoiskService->searchDetails($kinopoiskId);

        if (!$details) {
            return null;
        }

        try{
            $movie = DB::transaction(function () use ($details,$kinopoiskId)
            {
            
                $rating = $details['ratingImdb'] ?? $details['ratingKinopoisk'] ?? null;
                $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);

                $title = head(array_filter([
                    $details['nameEn'] ?? null,
                    $details['nameRu'] ?? null,
                ], 'filled')) ?: 'Без названия';

                $baseSlug = Str::slug($title, '-', 'ru');

                if (empty($baseSlug)) {
                    $baseSlug = 'movie';
                }

                $slug = "{$baseSlug}-{$kinopoiskId}";

                $movie = Movie::updateOrCreate(
                    ['kinopoisk_id' => $kinopoiskId],
                    [ 
                    'title'              => $title,
                    'slug'               => $slug,
                    'description'        => $details['shortDescription'] ?? $details['description'] ?? 'Описание отсутствует',
                    'duration_min'       => $details['filmLength'] ?? null,
                    'poster_url'         => $details['posterUrl'] ?? null,
                    'poster_preview_url' => $details['posterUrlPreview'] ?? null,
                    'rating'             => $finalRating,
                    'year'               => $details['year'] ?? null,
                    'genres'             => array_column($details['genres'] ?? [], 'genre'),
                    ]
                );

                if ($movie->shows()->count() === 0) {
                    $this->generateDefaultShows($movie);
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

    private function generateDefaultShows(Movie $movie): void
    {
        $schedule = [
            now()->format('Y-m-d') => ['10:00'],
            now()->addDay()->format('Y-m-d') => ['18:00', '22:00'],
        ];

        foreach ($schedule as $date => $times) {
            foreach ($times as $time) {
                // Формируем дату и время в формате ISO 8601 (UTC / Z)
                $dateTime = now()->parse("{$date} {$time}")->toIso8601ZuluString();

                Show::create([
                    'movie_id'   => $movie->id,
                    'start_time' => $dateTime, // В БД/JSON уйдет строка вида "2026-12-04T10:00:00.000Z"
                    'price'      => 350.00,
                ]);
            }
        }
    }
}
