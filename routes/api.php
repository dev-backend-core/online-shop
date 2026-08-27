<?php

use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SeatController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Models\Movie;
use App\Models\Seat;
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

Route::get('/show/all',[MovieController::class,'index']);

Route::get('/show/{movie}', [MovieController::class,'movieDetails']);

Route::get('/seats',[SeatController::class,'index']);

Route::get('/user/favorites', [FavoriteController::class,'index']);

Route::post('/user/update-favorite', [FavoriteController::class,'toggle']);

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

