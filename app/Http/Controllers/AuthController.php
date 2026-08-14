<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 1. РЕГИСТРАЦИЯ
    public function register(Request $request) 
    {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Создаем токен 
        $token = $user->createToken('register_token', ['*'])->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    // 2. ВХОД (ЛОГИН)
    public function login(Request $request) 
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Неверный логин или пароль'], 401);
        }

        // Знак '*' означает "дать этому токену вообще все права"
        $token = $user->createToken('login_token', ['*'])->plainTextToken;

        return response()->json(['message' => 'Успешный вход!', 'token' => $token], 200);
    }

    // 3. ВЫХОД (ОТЗЫВ ТОКЕНА)
    public function logout(Request $request) 
    {
        // Метод delete() выполняет SQL-запрос DELETE к таблице токенов
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Токен удален. Выход успешен!']);
    }
}
