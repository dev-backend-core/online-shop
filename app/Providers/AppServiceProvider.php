<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(10)->by(
                // Считаем лимит по ID пользователя, а если гость — по его IP-адресу
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Слишком много запросов! Пожалуйста, подождите минуту перед следующей попыткой.'
                ], 429);
            });
        });

        // 2. Лимитер для авторизации/регистрации (5 попыток в минуту)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
