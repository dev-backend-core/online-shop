<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. РЕГИСТРАЦИЯ
    public function register(Request $request) 
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ], [
            'email.unique' => 'Вы уже зарегистрированы на сайте. Пожалуйста, выполните вход.',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return response()->json(['message' => 'Регистрация прошла успешно!'], 201);
    }

    // 2. ВХОД (ЛОГИН)
    public function login(Request $request) 
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Неверный логин или пароль'], 401);
        }

        Auth::login($user);

        return response()->json(['message' => 'Успешный вход!'], 200);
    }

    // 3. ВЫХОД (ОТЗЫВ ТОКЕНА)
    public function logout(Request $request) 
    {
        // 2. Выходим из веб-сессии Laravel
        Auth::guard('web')->logout();

        // 3. Инвалидируем сессию и регенерируем CSRF-токен для безопасности
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json(['message' => 'Токен удален. Выход успешен!']);
    }
}
