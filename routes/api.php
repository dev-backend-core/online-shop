<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SeatController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Show;
use App\Models\Ticket;
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

Route::get('/user/bookings', [BookingController::class,'index']);

Route::get('/booking/seats/{show}', [BookingController::class,'seats']);

Route::post('/booking/create', [BookingController::class,'create']);

