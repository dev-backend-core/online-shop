<?php

namespace App\Actions;

use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingTicketsAction
{
    public function execute(User $user, int $showId, array $seatIds){

        return DB::transaction(function () use ($user, $seatIds, $showId)
        {

        $show = Show::findOrFail($showId);
        $price = $show->price;
       

        $alreadyBooked = Ticket::where('show_id', $showId)
            ->whereIn('seat_id', $seatIds)
            ->whereIn('status', ['paid', 'reserved'])
            ->lockForUpdate()
            ->exists();

        if ($alreadyBooked) {
            throw new \Exception('Одно или несколько мест уже забронированы!');
        }

        $ticketsData = array_map(function ($seatId) use ($showId, $price)
        {
            return [
                'show_id' => $showId,
                'seat_id' => $seatId,
                'status'  => 'reserved',
                'price'   => $price,
            ];
        }, $seatIds);

        return $user->tickets()->createMany($ticketsData);
        });
    }
}
