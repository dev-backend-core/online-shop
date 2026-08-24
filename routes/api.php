<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Models\Movie;
use App\Models\Show;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class,'logout'])->middleware(['web', 'auth:sanctum']);;


// Вход через Google
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);

Route::get('/auth/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Закрытые маршруты (требуют заголовок Authorization)

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Твой CRUD блога теперь защищен
    // Route::apiResource('posts', PostController::class);
});

Route::get('/show/all', function () {

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
                'title' => $show['nameEn'] ?? $show['nameRu'] ?? 'Без названия',
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
});

Route::get('/show/{id}', function () {
    $movie = [  
        "_id" => "628847",
        "title" => "Trap House",
        "overview" => "An undercover DEA agent...",
        "poster_path" => "/6tpAPeuuqbVnYWWPoOLEDLSBU7a.jpg",
        "backdrop_path"=> "/oIJjO1CvEdTMFNkWfHaV0RB584G.jpg",
        "release_date"=> "2025-11-14",
        "original_language"=> "en",
        "tagline"=> "This isn't a raid. It's a reckoning.",
        "release_date" => "2025-11-14",
        "genres" => [
            ["id" => 28, "name" => "Action"],
            ["id" => 80, "name" => "Crime"]
        ],
        "casts" => [
            [
                "id" => 543530,
                "name" => "Dave Bautista",
                "character" => "Ray Seale"
            ],
            [
                "id" => 543531,
                "name" => "Dave Bautista",
                "character" => "Ray Seale"
            ],
            [
                "id" => 543532,
                "name" => "Dave Bautista",
                "character" => "Ray Seale"
            ]
        ],
        "vote_average"=> 6.229,
        "runtime"=> 102, 
    ];

    

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'movie' => $movie,
        // 'dateTime' => $dateTime
    ]);
});

Route::get('/user/favorites', function () {
    $shows = [
        [
            "_id" => "628847",
            "title" => "Trap House",
            "overview" => "An undercover DEA agent...",
            "poster_path" => "/6tpAPeuuqbVnYWWPoOLEDLSBU7a.jpg",
            "release_date" => "2025-11-14",
            "genres" => [
                ["id" => 28, "name" => "Action"],
                ["id" => 80, "name" => "Crime"]
            ],
            "casts" => [
                [
                    "id" => 543530,
                    "name" => "Dave Bautista",
                    "character" => "Ray Seale"
                ]
            ],
            "vote_average"=> 6.229,
            "runtime"=> 102,
        ],
        [
            "_id" => "628849",
            "title" => "Trap House 2",
            "overview" => "An undercover DEA agent...",
            "poster_path" => "/6tpAPeuuqbVnYWWPoOLEDLSBU7a.jpg",
            "release_date" => "2025-11-14",
            "genres" => [
                ["id" => 28, "name" => "Action"],
                ["id" => 80, "name" => "Crime"]
            ],
            "casts" => [
                [
                    "id" => 543530,
                    "name" => "Dave Bautista",
                    "character" => "Ray Seale"
                ]
            ],
            "vote_average"=> 6.229,
            "runtime"=> 102,
        ]
    ];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'movies' => $shows
    ]);
});

Route::get('/user/bookings', function () {
    $shows = [
        [    
            "title" => "Trap House",
            "runtime"=> 102,
            "poster_path" => "/6tpAPeuuqbVnYWWPoOLEDLSBU7a.jpg",
            "showDateTime" => '2026-01-10T09:16:44.060Z',
            "isPaid" => true,
            "bookedSeats" => ['4A','3B'],
            "amount" => '$120',
        ]
    ];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'bookings' => $shows
    ]);
});

Route::get('/booking/seats/{id}', function () {
    $occupiedSeats = ["A1","A2","A3"];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'occupiedSeats' => $occupiedSeats
    ]);
});

