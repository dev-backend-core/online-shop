<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ExpireBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//    /**
//      * @param Collection $tickets
//      */
    public function __construct(public array $ticketIds)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       // Выбираем только ID переданных билетов
        // $ticketIds = $this->tickets->pluck('id');

        Ticket::whereIn('id', $this->ticketIds)
        ->where('status', 'reserved')
        ->update(['status' => 'cancelled']);
       
    }
}
