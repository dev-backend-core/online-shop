<?php

namespace App\Http\Controllers\Api;

use App\Actions\SyncMoviesAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Show;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;

class MovieController extends Controller
{
    
    public function index(SyncMoviesAction $syncAction)
    {
        $moviesInDb = Movie::all();

        if ($moviesInDb->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'shows' => $moviesInDb
            ]);
        }

        if (Movie::count() === 0) {
            $syncAction->execute();
        }

        return response()->json([
            'success' => true,
            'shows' => Movie::all()
        ]);
    }

    public function movieDetails(Movie $movie)
    {
         // $movie = Movie::findOrFail($id);
        // $shows = $movie->shows;
        $movie->load('shows');

        // Возвращаем данные. Laravel сам превратит этот массив в JSON
        return response()->json([
            'success' => true,
            'movie' => $movie,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
