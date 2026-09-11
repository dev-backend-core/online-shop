<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSuccessfulNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public mixed $tickets,
        public string $pdfPath)
    {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->tickets);

       $mail = (new MailMessage)
            ->subject('Ваш билет успешно забронирован! 🎬')
            ->greeting('Здравствуйте')
            ->line('Спасибо за бронирование билетов в нашем кинотеатре.')
            ->line("Вы успешно забронировали {$count} мес. в нашем кинотеатре.")
            ->line('Детали ваших билетов:')
            ->line('---');

        // Выводим список забронированных мест
        foreach ($this->tickets as $ticket) {
            $mail->line("Ряд {$ticket->seat->row_number}, Место {$ticket->seat->seat_number} | Сеанс : {$ticket->show->movie->title} | Цена: {$ticket->price} $.");
        }

        $mail->line('---');

        // Прикрепляем PDF только если путь не пустой и файл действительно существует на диске
        if (!empty($this->pdfPath) && file_exists($this->pdfPath)) {
            $mail->line('Ваш PDF-билет прикреплен к этому письму.')
                ->attach($this->pdfPath, [
                    'as' => 'tickets.pdf',
                    'mime' => 'application/pdf',
                ]);
        }

        return $mail;
           
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
