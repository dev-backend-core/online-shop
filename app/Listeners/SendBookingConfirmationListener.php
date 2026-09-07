<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\BookingSuccessfulNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $tickets = $event->tickets;

        if (empty($tickets) || $tickets->isEmpty()) {
            return;
        }

        // Берем пользователя из первого билета (так как все билеты принадлежат одному юзеру)
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
