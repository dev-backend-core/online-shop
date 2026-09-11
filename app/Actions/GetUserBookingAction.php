<?php

namespace App\Actions;

class GetUserBookingAction
{
    private array $flatRows;

    public function __construct()
    {
        $groupRows = [
            ["A", "B"],
            ["C", "D"],
            ["E", "F"],
            ["G", "H"],
            ["I", "J"],
        ];

        $this->flatRows = collect($groupRows)->flatten()->toArray();
    }

    public function execute($tickets){
       
        return $tickets->groupBy(function ($ticket) {
            return $ticket->show_id . '_' . $ticket->created_at->toDateTimeString();
        })->map(function ($group){

            $firstTicket = $group->first(); 

            $seatsList = $group->map(function ($ticket){
                $rowNum  = $ticket->seat->row_number;  
                $seatNum = $ticket->seat->seat_number; 
                $rowLetter = $this->flatRows[$rowNum - 1] ?? $rowNum; 

                return "{$rowLetter}{$seatNum}";
            })->implode(', ');

            return [
                
                'booking_date' => $firstTicket->created_at->format('Y-m-d H:i:s'),
                'total_price'  => $group->sum('price'), 
                'total_seats'  => $group->count(),       
                'seats_list'   => $seatsList, 
                'status'       => $firstTicket->status,
                'seats'        => $group->pluck('seat'), 
                'show'         => $firstTicket->show->makeHidden('movie'),
                'movie'        => $firstTicket->show->movie,
            ];
        })->values();
    }
}
