<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);

Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Закрытые маршруты (требуют заголовок Authorization)

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Твой CRUD блога теперь защищен
    // Route::apiResource('posts', PostController::class);
});

Route::get('/show/all', function () {
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


Route::get('/booking/seats/{id}', function () {
    $occupiedSeats = ["A1","A2","A3"];

    // Возвращаем данные. Laravel сам превратит этот массив в JSON
    return response()->json([
        'success' => true,
        'occupiedSeats' => $occupiedSeats
    ]);
});