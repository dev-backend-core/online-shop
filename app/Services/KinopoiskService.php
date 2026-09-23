<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class KinopoiskService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.kinopoisk.key');
        $this->baseUrl = config('services.kinopoisk.url');
    }

    public function getPremieres()
    {

        $premieresResponse = Http::timeout(5)->withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/v2.2/films/premieres", [
            'year' => Carbon::now()->year,
            'month' => strtoupper(Carbon::now()->format('F')),
        ]);

        if($premieresResponse ->failed()){
            return [];
        }

        $shows = collect($premieresResponse->json('items'))->take(15);
        $responses = Http::pool(fn (Pool $pool) => 
            $shows->map(fn ($show) => 
                $pool->withHeaders(['X-API-KEY' => $this->apiKey])
                    ->get("{$this->baseUrl}/v2.2/films/{$show['kinopoiskId']}")
            )->toArray()
        );

        return $shows->map(function ($show, $index) use ($responses) {
        
            $response = $responses[$index] ?? null;

            $details = ($response instanceof Response && $response->successful()) 
            ? $response->json() 
            : [];

            return self::fromKinopoiskArray($show, $details);

            // $rating = $details['ratingImdb'] ?? $details['ratingKinopoisk'] ?? null;
            // $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);

            // $title = head(array_filter([
            //     $show['nameEn'] ?? null,
            //     $show['nameRu'] ?? null,
            // ], 'filled')) ?: 'Без Названия';

            // $baseSlug = Str::slug($title, '-', 'ru');

            // if (empty($baseSlug)) {
            //     $baseSlug = 'movie';
            // }

            // $slug = "{$baseSlug}-{$show['kinopoiskId']}";

            // return [
            //     'kinopoisk_id'       => $show['kinopoiskId'],
            //     'title'              => $title,
            //     'slug'               => $slug,
            //     'description'        => $details['shortDescription'] ?? $details['description'] ?? 'Описание отсутствует',
            //     'duration_min'       => $show['duration'] ?? null,
            //     'poster_url'         => $show['posterUrl'] ?? null,
            //     'poster_preview_url' => $show['posterUrlPreview'] ?? null,
            //     'rating'             => $finalRating,
            //     'year'               => $show['year'] ?? null,
            //     'genres'             => array_column($show['genres'] ?? [], 'genre'),
            // ];

        })->toArray();
    }

    public function searchMovies(string $keyword)
    {
        $premieresResponse = Http::timeout(5)->withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/v2.1/films/search-by-keyword", [
            'keyword' => $keyword,
            'page'    => 1,
        ]);

        if($premieresResponse ->failed()){
            return [];
        }

        $data = $premieresResponse->json();
        $films = $data['films'] ?? [];

        if (empty($films)) {
            return [];
        }

        return collect($films)->take(5)->map(function ($show)
        {
            return self::fromKinopoiskArray($show);

            // $rating = $show['rating'] ?? null;
            // $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);

            // $title = head(array_filter([
            //     $show['nameEn'] ?? null,
            //     $show['nameRu'] ?? null,
            // ], 'filled')) ?: 'Без названия';

            // $baseSlug = Str::slug($show['nameEn'], '-', 'ru');

            // if (empty($baseSlug)) {
            //     $baseSlug = 'movie';
            // }

            // $slug = "{$baseSlug}-{$show['filmId']}";

            // return 
            // [
            //     'kinopoisk_id'       => $show['filmId'],
            //     'title'              => $title,
            //     'slug'               => $slug,
            //     'duration_min'       => $this->parseDurationToMinutes($show['filmLength'] ?? null),
            //     'poster_url'         => $show['posterUrl'] ?? null,
            //     'poster_preview_url' => $show['posterUrlPreview'] ?? null,
            //     'rating'             => $finalRating,
            //     'year'               => is_numeric($show['year']) ? (int) $show['year'] : 'год не указан',
            //     'genres'             => array_column($show['genres'] ?? [], 'genre'),
            // ];

        })->toArray();
    }

    public function searchDetails(int $id)
    {
        $response = Http::timeout(5)->withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/v2.2/films/{$id}");

        if($response ->failed()){
            return null;
        }
        return $response->json();
    }

    private static function parseDurationToMinutes($rawDuration): ?int
    {
        if (empty($rawDuration)) {
            return 135;
        }

        if (is_numeric($rawDuration)) {
            return (int) $rawDuration;
        }

        if (str_contains($rawDuration, ':')) {
            $parts = explode(':', $rawDuration);
            $hours = (int) ($parts[0] ?? 0);
            $minutes = (int) ($parts[1] ?? 0);

            return ($hours * 60) + $minutes;
        }

        return 135;
    }

    public static function fromKinopoiskArray(array $show, array $details = []): array
    {
        // Объединяем детали и основные данные
        $data = array_merge($show, $details);
        $kinopoiskId = $data['kinopoiskId'] ?? $data['filmId'] ?? null;

        // 1. Вычисляем Title
        $title = head(array_filter([
            $data['nameEn'] ?? null,
            $data['nameRu'] ?? null,
        ], 'filled')) ?: null;

        // 2. Вычисляем Slug
        $baseSlug = Str::slug($title, '-', 'ru');
        if (empty($baseSlug)) {
            $baseSlug = 'movie';
        }
        $slug = "{$baseSlug}-{$kinopoiskId}";

        // 3. Вычисляем Rating
        $rating = $data['ratingImdb'] ?? $data['ratingKinopoisk'] ?? $data['rating'] ?? null;
        $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);

        // 4. Длительность фильма
        $rawDuration = $data['filmLength'] ?? $data['duration'] ?? null;
        $durationMin = self::parseDurationToMinutes($rawDuration);

        return [
            'kinopoisk_id'       => $kinopoiskId,
            'title'              => $title ?? 'Без названия',
            'slug'               => $slug,
            'description'        => $data['shortDescription'] ?? $data['description'] ?? 'Описание отсутствует',
            'duration_min'       => $durationMin,
            'poster_url'         => $data['posterUrl'] ?? null,
            'poster_preview_url' => $data['posterUrlPreview'] ?? null,
            'rating'             => $finalRating,
            'year'               => is_numeric($data['year'] ?? null) ? (int) $data['year'] : null,
            'genres'             => array_column($data['genres'] ?? [], 'genre'),
        ];
    }
}
