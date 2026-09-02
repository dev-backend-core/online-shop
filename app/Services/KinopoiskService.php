<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;

class KinopoiskService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.kinopoisk.key');
        $this->baseUrl = config('services.kinopoisk.url');
    }

    public function getPremieres(){

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

        $shows = collect($premieresResponse->json('items'))->take(10);
        $responses = Http::pool(fn (Pool $pool) => 
            $shows->map(fn ($show) => 
                $pool->withHeaders(['X-API-KEY' => $this->apiKey])
                    ->get("{$this->baseUrl}/v2.2/films/{$show['kinopoiskId']}")
            )->toArray()
        );

        return $shows->map(function ($show, $index) use ($responses) {
            $details = $responses[$index]?->successful() ? $responses[$index]->json() : [];
            $rating = $details['ratingImdb'] ?? $details['ratingKinopoisk'] ?? null;
            $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);

            $title = head(array_filter([
                        $show['nameEn'] ?? null,
                        $show['nameRu'] ?? null,
                    ], 'filled')) ?: null;

            $baseSlug = Str::slug($title, '-', 'ru');

            if (empty($baseSlug)) {
                $baseSlug = 'movie';
            }

            $slug = "{$baseSlug}-{$show['kinopoiskId']}";

            return [
                'kinopoisk_id'       => $show['kinopoiskId'],
                'title'              => $title,
                'slug'               => $slug,
                'description'        => $details['shortDescription'] ?? $details['description'] ?? 'Описание отсутствует',
                'duration_min'       => $show['duration'] ?? null,
                'poster_url'         => $show['posterUrl'] ?? null,
                'poster_preview_url' => $show['posterUrlPreview'] ?? null,
                'rating'             => $finalRating,
                'year'               => $show['year'] ?? null,
                'genres'             => array_column($show['genres'] ?? [], 'genre'),
            ];
        })->toArray();
    }
}
