<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Show;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $moviesInDb = Movie::all();

        if ($moviesInDb->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'shows' => $moviesInDb
            ]);
        }

        $apiKey = config('services.kinopoisk.key');
        $baseUrl = config('services.kinopoisk.url');

        // 1. Получаем список премьер
        $premieresResponse = Http::timeout(5)->withHeaders([
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/v2.2/films/premieres", [
            'year' => Carbon::now()->year,
            'month' => strtoupper(Carbon::now()->format('F')),
        ]);

        if ($premieresResponse->failed()) {
            return response()->json(['success' => false, 'error' => 'API Error'], 500);
        }

        $shows = collect($premieresResponse->json('items'))->take(10); // Возьмем первые 10 фильмов

        // 2. Делаем параллельные запросы детализации для каждого фильма из списка
        $responses = Http::pool(fn (Pool $pool) => 
            $shows->map(fn ($show) => 
                $pool->withHeaders(['X-API-KEY' => $apiKey])
                    ->get("{$baseUrl}/v2.2/films/{$show['kinopoiskId']}")
            )->toArray()
        );
        
        $dateTime = [
            "2026-12-04"=> [
                [
                    "time" => "2026-12-04T10:00:00.000Z",
                ],
                [
                    "time" => "2026-12-04T12:00:00.000Z",
                ],
                [
                    "time"=> "2026-12-04T21:00:00.000Z",
                ]
            ]
        ];

        // 3. Объединяем данные премьеры с полученным рейтингом
        $shows->each(function ($show, $index) use ($responses,$dateTime) { 
            $details = $responses[$index]?->successful() ? $responses[$index]->json() : [];

            $rating = $details['ratingImdb'] ?? $details['ratingKinopoisk'] ?? null;
            $finalRating = ($rating > 0) ? (float) $rating : round(mt_rand(50, 71) / 10, 1);
        
            $description = $details['shortDescription'] ?? $details['description'] ?? 'Описание отсутствует';
        
            $genresArray = array_column($show['genres'] ?? [], 'genre'); 
                // На выходе получим: ['драма', 'музыка']

            $movie = Movie::updateOrCreate(
                ['kinopoisk_id' => $show['kinopoiskId']],
                [
                   'title' => head(array_filter([
                        $show['nameEn'] ?? null,
                        $show['nameRu'] ?? null,
                    ], 'filled')) ?: 'Без названия',
                    'description' => $description,
                    'duration_min' => $show['duration'] ?? null,
                    'poster_url' => $show['posterUrl'] ?? null,
                    'poster_preview_url' => $show['posterUrlPreview'] ?? null,
                    'rating' => $finalRating,
                    'year' => $show['year'] ?? null,

                    'genres' => $genresArray,
                ]
            );

            foreach ($dateTime as $date => $sessions) {
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
        });

        

        return response()->json([
            'success' => true,
            'shows' => Movie::all()
        ]);
    }

    public function movieDetails(Movie $movie){
         // $movie = Movie::findOrFail($id);
        // $shows = $movie->shows;
        $movie->load('shows');

        // Возвращаем данные. Laravel сам превратит этот массив в JSON
        return response()->json([
            'success' => true,
            'movie' => $movie,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
