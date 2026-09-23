<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SeatController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout',[AuthController::class,'logout'])->middleware(['auth:sanctum']);

// Вход через Google
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);

Route::get('/auth/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);



Route::get('/show/all',[MovieController::class,'index']);

Route::get('/show/{id}', [MovieController::class,'movieDetails']);

Route::get('/seats',[SeatController::class,'index']);

Route::get('/user/favorites', [FavoriteController::class,'index']);

Route::post('/user/update-favorite', [FavoriteController::class,'toggle']);

Route::get('/user/bookings', [BookingController::class,'index']);

Route::get('/booking/seats/{show}', [BookingController::class,'seats']);

Route::post('/booking/create', [BookingController::class,'create'])->middleware(['auth:sanctum', 'throttle:booking']);

Route::post('/booking/createStripeSession',[BookingController::class,'createStripeSession']);

Route::post('/movie/search',[MovieController::class,'searchMovie']);

