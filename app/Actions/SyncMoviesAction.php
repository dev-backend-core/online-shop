<?php

namespace App\Actions;

use App\Models\Movie;
use App\Models\Show;
use App\Services\KinopoiskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMoviesAction
{
    private array $dateTime;

   // Подключаем KinopoiskService через автоматический DI Laravel
    public function __construct(
        private readonly KinopoiskService $kinopoiskService
    ) {}

   
    public function execute(){

        $movieData = $this->kinopoiskService->getPremieres();

        if (empty($movieData)) {
            Log::warning('Премьеры не загружены, пустой ответ от API');
            return false;
        }

        // Log::warning('Колич.фильмов: ' . count($movieData));

        try{
            DB::transaction(function () use ($movieData) {
                foreach ($movieData as $item) {
                    $movie = Movie::updateOrCreate(
                        ['kinopoisk_id' => $item['kinopoisk_id']],
                        $item
                    );

                    if ($movie->shows()->count() === 0) {
                        SearchMoviesAction::generateDefaultShows($movie);
                    }
                }
            });

            return true;

        }catch(\Throwable $e){
            Log::error("Сбой транзакции при загрузки всех фильмов" . $e->getMessage());

            return false;
        }
    }
}    
    
