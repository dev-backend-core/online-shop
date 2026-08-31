<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()
            ->tickets()
            ->with(['show.movie'])
            ->get();

        $groupRows = [
            ["A", "B"],
            ["C", "D"],
            ["E", "F"],
            ["G", "H"],
            ["I", "J"],
        ];

        $flatRows = collect($groupRows)->flatten()->toArray();

        $groupedBookings = $tickets->groupBy(function ($ticket) {
            return $ticket->show_id . '_' . $ticket->created_at->toDateTimeString();
        })->map(function ($group) use ($flatRows) {

            $firstTicket = $group->first(); 

            $seatsList = $group->map(function ($ticket) use ($flatRows) {
                $rowNum  = $ticket->seat->row_number;  
                $seatNum = $ticket->seat->seat_number; 
                $rowLetter = $flatRows[$rowNum - 1] ?? $rowNum; 

                return "{$rowLetter}{$seatNum}";
            })->implode(', ');

            return [
                
                'booking_date' => $firstTicket->created_at->format('Y-m-d H:i'),
                'total_price'  => $group->sum('price'), // Считаем общую цену
                'total_seats'  => $group->count(),       // Количество мест
                // Собираем все места через запятую (например: "Ряд 2 Место 5, Ряд 2 Место 6")
                'seats_list'   => $seatsList, 
                'status'   => $firstTicket->status,
                // Подробный массив мест для фронтенда (если нужно рендерить плашки)
                'seats'        => $group->pluck('seat'), 
                'show'         => $firstTicket->show->makeHidden('movie'),
                'movie'        => $firstTicket->show->movie,
            ];
        })->values();

        // Возвращаем данные. Laravel сам превратит этот массив в JSON
        return response()->json([
            'success' => true,
            'bookings' => $groupedBookings
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
