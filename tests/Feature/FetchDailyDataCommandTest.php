<?php

namespace Tests\Feature;

use App\Actions\SyncMoviesAction;
use App\Services\KinopoiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
// use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;


class FetchDailyDataCommandTest extends TestCase
{
    use RefreshDatabase;

    // public function test_movie_reminders_command_is_scheduled(): void
    // {
    //     // 1. Получаем объект Schedule из контейнера
    //     $schedule = app(Schedule::class);

    //     // 2. Ищем команду в списке зарегистрированных событий
    //     $event = collect($schedule->events())->first(function (Event $event) {
    //         return str_contains($event->command, 'app:sync-movies-command');
    //     });

    //     // 3. Проверяем, что команда найдена и интервал совпадает
    //     $this->assertNotNull($event, 'Команда не зарегистрирована в Schedule');
    //     $this->assertEquals('0 0 * * *', $event->expression); 
    // }

    public function test_command_clears_cache_and_dispatches_event(): void
    {
        $this->mock(SyncMoviesAction::class, function ($mock) {
            $mock->shouldReceive('execute')
            ->once()
            ->andReturn(true);
        });
        // 2. Кладем тестовое значение в кэш, чтобы убедиться, что оно удалится
        Cache::put('all_movies', ['movie_1', 'movie_2'], 3600);
        $this->assertTrue(Cache::has('all_movies'));

        // 3. Запускаем команду
        $this->artisan('app:sync-movies-command') 
            ->expectsOutput('Фильмы успешно синхронизированы!')
            ->assertSuccessful();

        // 4. Проверяем, что кэш действительно очистился
        $this->assertFalse(Cache::has('all_movies'));
    }

    public function test_get_premieres_returns_formatted_array_on_success()
    {
        // 1. Мокаем первичное получение премьер и пул запросов деталей
        Http::fake([
            '*/v2.2/films/premieres*' => Http::response([
                'items' => [
                    ['kinopoiskId' => 101, 'nameRu' => 'Интерстеллар', 'duration' => 169, 'posterUrl' => 'http://img/1.jpg'],
                ]
            ], 200),

            '*/v2.2/films/101' => Http::response([
                'ratingKinopoisk' => 8.6,
                'description' => 'Отличное кино',
            ], 200),
        ]);

        // 2. Вызываем класс/сервис, где находится getPremieres()
        $service = new KinopoiskService();
        $result = $service->getPremieres();

        // 3. ПРОВЕРКИ
        $this->assertCount(1, $result);
        $this->assertEquals(101, $result[0]['kinopoisk_id']);
        $this->assertEquals('Интерстеллар', $result[0]['title']);
        $this->assertEquals('interstellar-101', $result[0]['slug']); // Проверка генерации slug
        $this->assertEquals(8.6, $result[0]['rating']);

        // Проверяем, что запрос на премьеры ушел с X-API-KEY
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v2.2/films/premieres');
        });
    }

    public function test_get_premieres_returns_empty_array_when_api_fails(): void
    {
        Http::fake([
            '*/v2.2/films/premieres*' => Http::response([], 500),
        ]);

        $service = new KinopoiskService();
        $result = $service->getPremieres();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_premieres_limits_results_to_fifteen(): void
    {
        // Генерируем 20 тестовых фильмов
        $items = array_map(fn($id) => ['kinopoiskId' => $id, 'nameRu' => "Movie $id"], range(1, 20));

        Http::fake([
            '*/v2.2/films/premieres*' => Http::response(['items' => $items], 200),
            '*/v2.2/films/*' => Http::response(['ratingKinopoisk' => 7.0], 200),
        ]);

        $service = new KinopoiskService();
        $result = $service->getPremieres();
        // dd($result);

        // Должно остаться ровно 15 элементов
        $this->assertCount(15, $result);
    }

    public function test_get_premieres_handles_missing_ratings_and_titles(): void
    {
        Http::fake([
            '*/v2.2/films/premieres*' => Http::response([
                'items' => [
                ['kinopoiskId' => 999, 'nameRu' => null, 'nameEn' => null]
                ]], 200),
            '*/v2.2/films/*' => Http::response(['ratingKinopoisk' => null], 200),
        ]);

        $service = new KinopoiskService();
        $result = $service->getPremieres();
        // dd($result);
      
        $this->assertEquals('bez-nazvaniya-999', $result[0]['slug']);
        $this->assertEquals('Без Названия', $result[0]['title']);
        // $this->assertNull($result[0]['title']);

        // Проверяем, что рейтинг сгенерировался в допустимом диапазоне 5.0 - 7.1
        $this->assertGreaterThanOrEqual(5.0, $result[0]['rating']);
        $this->assertLessThanOrEqual(7.1, $result[0]['rating']);
    }

    public function test_sync_action_saves_premieres_to_database(): void
    {
        // 1. Мокаем внешние API-запросы
        Http::fake([
            '*/v2.2/films/premieres*' => Http::response([
                'items' => [
                    ['kinopoiskId' => 101, 'nameEn' => 'Интерстеллар', 'duration' => 169],
                ]
            ], 200),

            '*/v2.2/films/101' => Http::response([
                'ratingKinopoisk' => 8.6,
                'description'     => 'Тестовое описание',
            ], 200),
        ]);

        // 2. Вызываем Action
        $action = app(SyncMoviesAction::class);
        $action->execute();

        // 3. Проверяем реальное сохранение в БД
        $this->assertDatabaseHas('movies', [
            'kinopoisk_id' => 101,
            'title'        => 'Интерстеллар',
            'rating'       => 8.6,
        ]);
    }

    public function test_search_movies_handles_missing_english_title_and_invalid_year(): void
    {
        Http::fake([
            '*/v2.1/films/search-by-keyword*' => Http::response([
                'films' => [
                    [
                        'filmId' => 777,
                        'nameEn' =>  '', // Нет английского названия
                        'rating' => 'null', // Некорректный rating
                        'year'   => 'N/A',  // Нечисловой год
                    ]
                ]
            ], 200),
        ]);

        $service = new KinopoiskService();
        $result = $service->searchMovies('Матрица');
        

        $this->assertEquals('movie-777', $result[0]['slug']);
        $this->assertEquals('год не указан',$result[0]['year']);
        $this->assertEquals('Без названия',$result[0]['title']);
    }

    public function test_search_movies_parses_duration_correctly(): void
    {
        Http::fake([
            '*/v2.1/films/search-by-keyword*' => Http::response([
                'films' => [
                    ['filmId' => 1, 'nameEn' => 'Movie 1', 'filmLength' => '2:15','year'   => 'N/A',],
                ]
            ], 200),
        ]);

        $service = new KinopoiskService();
        $result = $service->searchMovies('Test');

        $this->assertEquals(135, $result[0]['duration_min']); 
    }
}
