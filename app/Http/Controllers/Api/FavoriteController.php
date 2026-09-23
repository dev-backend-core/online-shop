<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
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
        $isFavorite = $user->favoriteMovies()->contains($validated['movieId']);

      
        return response()->json([
            'success' => true,
            'message' => $isFavorite ? 'Добавлено в избранное' : 'Удалено из избранного',
            'f' =>$result
        ]);
    }
}
