<?php

namespace App\Http\Controllers\Api;

use App\Actions\BookingTicketsAction;
use App\Actions\CreateStripeCheckoutSessionAction;
use App\Actions\GetUserBookingAction;
use App\Events\BookingCreated;
use App\Http\Controllers\Controller;
use App\Jobs\ExpireBookingJob;
use App\Models\Show;
use Illuminate\Http\Request;


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

    public function create(Request $request,BookingTicketsAction $action,CreateStripeCheckoutSessionAction $stripe)
    {
        //+ redis atomic locks не забудь
        $validated = $request->validate([
            'showId'=>'required|integer|exists:shows,id',
            'selectedSeats'   => 'required|array|min:1',
            'selectedSeats.*' => 'required|integer|exists:seats,id',
        ]);

        try {
           
            $tickets = $action->execute(
                $request->user(),
                $validated['showId'],
                $validated['selectedSeats']
            );

            // $tickets->load(['show.movie', 'seat']);

            $url = $stripe->execute($tickets);
            
            // передать сразу массив id и вызвать в зависимости от ответа stripe

            ExpireBookingJob::dispatch($tickets->pluck('id')->toArray())
            ->onQueue('high')
            ->delay(now()->addMinutes(10));

            BookingCreated::dispatch($tickets);
 
            return response()->json([
                'success' => true,
                'message' => 'Места успешно забронированы!',
                'tickets' => $tickets,
                'url' => $url
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409); 
        }
    }

    public function seats(Show $show)
    {
        $occupiedSeats = $show->tickets()
            ->pluck('seat_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'occupiedSeats' => $occupiedSeats
        ]);
    }
}
