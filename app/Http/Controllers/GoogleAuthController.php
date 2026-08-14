<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleAuthController extends Controller
{
    // Перенаправление на страницу Google
    public function redirectToGoogle()
    {
      
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');

        // getTargetUrl() формирует ссылку на Google с нужными параметрами,
        // но не делает редирект сам
        $url = $driver->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    // Обработка ответа от Google
    public function handleGoogleCallback()
    {
        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver('google');
            
            $googleUser = $driver->stateless()->user();

            // Ищем пользователя по google_id или email
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if (!$user) {
                // Если пользователя нет — создаем
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null, // пароль не нужен
                ]);
            } else if (!$user->google_id) {
                // Если пользователь регистрировался по почте, связываем аккаунты
                $user->update(['google_id' => $googleUser->id]);
            }

            // Создаем Sanctum токен
            $token = $user->createToken('auth_token')->plainTextToken;

            // Перенаправляем на фронтенд с токеном
            $frontendUrl = config('app.frontend_url', 'http://localhost:5174');
            return redirect()->to("{$frontendUrl}/oauth/callback?token={$token}");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
