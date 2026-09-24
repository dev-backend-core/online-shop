<?php

namespace Tests\Feature;

use App\Actions\BookingTicketsAction;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Show;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BookingTicketsActionTest extends TestCase
{
   use RefreshDatabase;

    private BookingTicketsAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new BookingTicketsAction();
    }

    public function test_throws_exception_if_cache_lock_times_out()
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $show = Show::factory()->create(['movie_id' => $movie->id,]);
        $seat = Seat::factory()->create();

        $lockKey = "booking_show_{$show->id}_lock";

        // Искусственно захватываем блокировку на 10 секунд до выполнения Action
        $existingLock = Cache::lock($lockKey, 10);
        $existingLock->get();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Сервер перегружен запросами на этот сеанс.');

        try {
            // Вызываем экшен — он попытается получить блокировку, но потерпит неудачу через 3 секунды
            $this->action->execute($user, $show->id, [$seat->id]);
        } finally {
            // Освобождаем блокировку в конце
            $existingLock->release();
        }
    }
}
