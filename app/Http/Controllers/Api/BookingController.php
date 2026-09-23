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
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket;


class BookingController extends Controller
{
    public function index(Request $request, GetUserBookingAction $userBooking)
    {
        $tickets = $request->user()
            ->tickets()
            ->where('status','!=','cancelled')
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

            $ticketIds = $tickets->pluck('id')->toArray();

            ExpireBookingJob::dispatch($ticketIds)
            ->delay(now()->addMinutes(10));

            $url = $stripe->execute($tickets);
            
            return response()->json([
                'success' => true,
                'message' => 'Места успешно забронированы!',
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
            ->whereIn('status', ['paid', 'reserved'])
            ->pluck('seat_id')
            ->toArray();

        return response()->json([
        'success'       => true,
        'occupiedSeats' => $occupiedSeats
        ])->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')->header('Pragma', 'no-cache');
    }

    public function createStripeSession(Request $request,CreateStripeCheckoutSessionAction $action)
    {
        $validated = $request->validate([
            'booking_date' => 'required|string',
        ]);

        $tickets = Ticket::where('user_id', Auth::id())
        ->where('created_at',$validated['booking_date'])
        ->where('status', 'reserved')
        ->get();

        if($tickets->isEmpty()){
            return response()->json([
                'message' => 'Забронированные билеты не найдены',
                'a'=> $validated['booking_date']
            ], 404);
        }

        $url = $action->execute($tickets);

        return response()->json([
            'url' => $url
        ], 201);
    }
}
