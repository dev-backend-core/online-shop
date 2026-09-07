<?php

namespace App\Console\Commands;

use App\Jobs\SendMovieReminderJob;
use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-movie-reminders-command')]
#[Description('Отправка напоминаний о фильме за 2 часа до начала')]
class SendMovieRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startWindow = now()->addHours(2)->subMinutes(5);
        $endWindow = now()->addHours(2)->addMinutes(5);

        $tickets = Ticket::with('user','shows')
        ->where('status','paid')
        ->where('reminder_sent',false)
        ->whereHas('show', function ($query) use ($startWindow, $endWindow) {
            $query->whereBetween('start_time', [$startWindow, $endWindow]);
        })
        ->get();

        foreach($tickets as $item){
            SendMovieReminderJob::dispatch($item);
        }

        $this->info("Запланировано отправлений: {$tickets->count()}");
    }
}
