<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\Ticket;
use App\Notifications\BookingSuccessfulNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;
   
    public function __construct()
    {}

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $ticketIds = $event->tickets;

        if (empty($ticketIds)) {
            return;
        }

        $tickets = Ticket::with(['seat','show'])
        ->whereIn('id',$ticketIds)
        ->get();

        $firstTicket = $tickets->first();
        $user = $firstTicket->user; 

        // 1. Генерация одного PDF с массивом билетов
        // $pdf = Pdf::loadView('pdf.tickets', compact('tickets'));
        // $pdfPath = storage_path("app/private/tickets/booking-{$firstTicket->id}.pdf");
        // $pdf->save($pdfPath);

        // Временная заглушка для теста:
        $pdfPath = '';
        // storage_path("app/tickets-booking.pdf");
        // file_put_contents($pdfPath, "Успешно забронировано билетов: " . count($tickets));

        // 2. Отправляем уведомление
        $user->notify(new BookingSuccessfulNotification($tickets, $pdfPath));
    }
}
