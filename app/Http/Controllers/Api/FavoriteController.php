<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request){
        $shows = $request->user()->favoriteMovies;
        return response()->json([
            'success' => true,
            'movies' => $shows
        ]);
    }

    public function toggle(Request $request){
        $validated = $request->validate([
        'movieId' => 'required|integer|exists:movies,id',
        ]);

        $user = $request->user();
        
        $result = $user->favoriteMovies()->toggle($validated['movieId']);

        // toggle() возвращает массив с ключами 'attached' и 'detached'
        $isFavorite = count($result['attached']) > 0;

        // 3. Возвращаем понятный статус для фронтенда
        return response()->json([
            'success' => true,
            // 'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Добавлено в избранное' : 'Удалено из избранного',
            'f' =>$result
        ]);
    }
}
