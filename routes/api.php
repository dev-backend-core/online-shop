<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;


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
    $shows = [
         {
            "kinopoiskId": 4924671,
            "nameRu": "TheatreHD: Зальцбург: Сельская честь / Паяцы",
            "nameEn": "Mascagni: Cavalleria Rusticana / Leoncavallo: Pagliacci",
            "year": 2016,
            "posterUrl": "https://kinopoiskapiunofficial.tech/images/posters/kp/4924671.jpg",
            "posterUrlPreview": "https://kinopoiskapiunofficial.tech/images/posters/kp_small/4924671.jpg",
            "countries": [
                {
                    "country": "Австрия"
                }
            ],
            "genres": [
                {
                    "genre": "драма"
                },
                {
                    "genre": "музыка"
                }
            ],
            "duration": 161,
            "premiereRu": "2026-08-04"
        },
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
            ]
        ];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'shows' => $shows
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

    $dateTime = [
        "2026-12-04"=> [
            [
                "time" => "2026-12-04T18:00:00.000Z",
                "showId" => "696217ce31b14e181b24d3bd"
            ],
            [
                "time" => "2026-12-04T12:00:00.000Z",
                "showId" => "696217ce31b14e181b24d3bd"
            ],
            [
                "time"=> "2026-12-04T21:00:00.000Z",
                "showId"=> "696217ce31b14e181b24d3be"
            ]
        ]
    ];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'movie' => $movie,
        'dateTime' => $dateTime
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

