<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MovieReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Ticket $ticket)
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
       $showtime = $this->ticket->show->start_time->translatedFormat('d F в H:i');

        return (new MailMessage)
            ->subject("🎬 Напоминание: сеанс «{$this->ticket->show->movie->title}» через 2 часа!")
            ->greeting("Привет")
            ->line("Напоминаем, что ваш сеанс начинается совсем скоро.")
            ->line("Фильм: {$this->ticket->show->movie->title}")
            ->line("Дата и время: {$showtime}")
            ->line("Место: Ряд {$this->ticket->seat->row_number}, Место {$this->ticket->seat->seat_number}")
            ->line("Номер билета: #{$this->ticket->id}")
            ->line('Пожалуйста, приходите за 10–15 минут до начала сеанса. Приятного просмотра!');
        
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
