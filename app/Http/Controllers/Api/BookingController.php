<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetUserBookingAction;
use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request, GetUserBookingAction $userBooking)
    {
        $tickets = $request->user()
            ->tickets()
            ->with(['show.movie'])
            ->get();

        $bookings = $userBooking->execute($tickets);

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }

    public function create(Request $request)
    {
        //!!! не забудь про двойное бронирование

        $validated = $request->validate([
            'showId'=>'required|integer|exists:shows,id',
            'selectedSeats'   => 'required|array|min:1',
            'selectedSeats.*' => 'required|integer|exists:seats,id',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $show = Show::findOrFail($validated['showId']);
            $price = $show->price;

            $ticketsData = array_map(function ($seatId) use ($validated,$price) {
                return [
                    'show_id' => $validated['showId'],
                    'seat_id' => $seatId,
                    'status'  => 'reserved',
                    'price'   => $price,
                ];
            }, $validated['selectedSeats']);
        
            $tickets = $request->user()->tickets()->createMany($ticketsData);

            return response()->json([
                'success' => true,
                'message' => 'Места успешно забронированы!',
                'tickets' => $tickets
            ], 201);
        });
    }
}
