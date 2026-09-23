<?php

namespace App\Actions;

use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Exception;

class BookingTicketsAction
{
    public function execute(User $user, int $showId, array $seatIds)
    {
        // 1. Создаем уникальный ключ блокировки для конкретного сеанса
        $lockKey = "booking_show_{$showId}_lock";

        // 2. Запрашиваем блокировку на 5 секунд
        $lock = Cache::lock($lockKey, 5);

        try {
            // 3. Ждем максимум 3 секунды, если другой запрос сейчас бронирует места на этот же сеанс
            return $lock->block(3, function () use ($user, $seatIds, $showId) {

                // Внутри блокировки выполняется ваша оригинальная транзакция
                return DB::transaction(function () use ($user, $seatIds, $showId) {

                    $show = Show::findOrFail($showId);
                    $price = $show->price;

                    $alreadyBooked = Ticket::where('show_id', $showId)
                        ->whereIn('seat_id', $seatIds)
                        ->whereIn('status', ['paid', 'reserved'])
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyBooked) {
                        throw new Exception('Одно или несколько мест уже забронированы!');
                    }

                    $ticketsData = array_map(function ($seatId) use ($showId, $price) {
                        return [
                            'show_id' => $showId,
                            'seat_id' => $seatId,
                            'status'  => 'reserved',
                            'price'   => $price,
                        ];
                    }, $seatIds);

                    return $user->tickets()->createMany($ticketsData);
                });

            });
        } catch (LockTimeoutException $e) {
            // Если за 3 секунды очередь не дошла до этого запроса
            throw new Exception('Сервер перегружен запросами на этот сеанс. Попробуйте еще раз!');
        }
    }
}
