<?php

namespace App\Http\Controllers\Api;

use App\Actions\SearchMoviesAction;
use App\Actions\SyncMoviesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovieDetailsRequest;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Services\KinopoiskService;
use Illuminate\Support\Facades\Cache;



class MovieController extends Controller
{
    
    public function index(SyncMoviesAction $syncAction)
    {
        if (Movie::count() === 0) {
            $synced = $syncAction->execute();
            
            if (!$synced && Movie::count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис афиши временно недоступен',
                    'shows'   => []
                ], 503);
            }
        }

        $movies = Cache::remember('all_movies', now()->addHours(24), fn () => Movie::all()->toArray());

        return response()->json([
            'success' => true,
            'shows'   => $movies
        ]);
    }

    public function movieDetails(MovieDetailsRequest $request,SearchMoviesAction $action)
    {
        $id = (int) $request->validated('id');

        $movie = Movie::with('shows')
            ->where('id', $id)
            ->orWhere('kinopoisk_id', $id)
            ->first();

       if (!$movie) {
            $movie = $action->execute($id);;
            
            if (!$movie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис афиши временно недоступен',
                    'movie'   => []
                ], 503);
            }
        }

        $movieData = Cache::remember("movie_details_{$id}", now()->addHours(24), function () use ($movie) {
            return $movie->load('shows')->toArray();
        });

        return response()->json([
            'success' => true,
            'movie'   => $movieData,
        ]);
    }

    
    public function searchMovie(Request $request,KinopoiskService $kinopoisk)
    {
        $request->validate([
            'searchValue' => 'required|string'
        ]);

        $movies = $kinopoisk->searchMovies($request->searchValue);

        return response()->json([
            'success' => true,
            'source' => 'kinopoisk',
            'movies' =>  $movies,
        ]);
    }
}
