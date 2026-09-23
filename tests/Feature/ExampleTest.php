<?php

namespace Tests\Feature;

use App\Jobs\SendMovieReminderJob;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\MovieReminderNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\Event;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reminders_for_movies_starting_in_two_hours(): void
    {
        // 1. Фиксируем текущее время: 12:00
        $now = Carbon::parse('2026-09-19 12:00:00');
        Carbon::setTestNow($now);

        // 2. Включаем фейк очередей (чтобы перехватывать Job::dispatch)
        Bus::fake();

        // 3. Подготавливаем тестовые данные:
        // Пользователь А: сеанс ровно через 2 часа (14:00) -> должен получить
        $seat2 = Seat::factory()->create();
        $seat1 = Seat::factory()->create();

        $userTarget = User::factory()->create();
        $movie1 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show1 = Show::factory()->create([
            'movie_id' => $movie1->id,
            'start_time' => $now->copy()->addHours(2),
            'price' => 350,
        ]);

        $ticket1 = Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show1->id,
            'seat_id' => $seat1->id,
            'status' => 'paid',
            'price' => 350,

        ]);

        // Пользователь Б: сеанс через 5 часов (17:00) -> не должен получить
        $userOther = User::factory()->create();
        $movie2 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show2 = Show::factory()->create([
            'movie_id' => $movie2->id,
            'start_time' => $now->copy()->addHours(5),
            'price' => 350,
        ]);

        $ticket2 = Ticket::factory()->create([
            'user_id' => $userOther->id,
            'show_id' => $show2->id,
            'seat_id' => $seat2->id,
            'status' => 'paid',
            'price' => 350,

        ]);

        // 4. Запускаем КОМАНДУ, а не джобы вручную
        $this->artisan('app:send-movie-reminders-command') // Укажи тут signature своей команды
            ->expectsOutput("Запланировано отправлений: 1")
            ->assertSuccessful();

        // 5. Проверяем, что в ОЧЕРЕДЬ была отправлена джоба только для первого билета
        Bus::assertDispatched(SendMovieReminderJob::class, function ($job) use ($ticket1) {
            return $job->ticket->id === $ticket1->id;
        });

        // Проверяем, что для второго билета джоба НЕ отправлялась
        Bus::assertNotDispatched(SendMovieReminderJob::class, function ($job) use ($ticket2) {
            return $job->ticket->id === $ticket2->id;
        });
    }

    /*Итоговая суть блока
    Этот тест говорит фреймворку:

    «Включи перехват уведомлений. Возьми билет и выполни код Job напрямую. А теперь убедись, что хозяин этого билета ($userTarget) получил сообщение MovieReminderNotification!» */

    public function test_send_movie_reminder_job_sends_notification_and_updates_status()
    {
        $now = Carbon::parse('2026-09-19 12:00:00');
        Carbon::setTestNow($now);
        // 2. Включаем фейк уведомлений
        Notification::fake();

        $seat1 = Seat::factory()->create();

        $userTarget = User::factory()->create();
        $movie1 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show1 = Show::factory()->create([
            'movie_id' => $movie1->id,
            'start_time' => $now->copy(),
            'price' => 350,
        ]);

        $ticket = Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show1->id,
            'seat_id' => $seat1->id,
            'status' => 'paid',
            'price' => 350,
        ]);

        // Вызов джобу вручную
        (new SendMovieReminderJob($ticket))->handle();

        Notification::assertSentTo(
            [$userTarget],
            MovieReminderNotification::class
        );

        // Проверяем, что флаг reminder_sent изменился в БД
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'reminder_sent' => true,
        ]);
    }

    public function test_notification_mail_content()
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create([
            'title' => 'Интерстеллар',
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);
        $seat = Seat::factory()->create();
        $show = Show::factory()->create([
            'movie_id' => $movie->id,
            'start_time' => '2026-09-19 18:00:00',
        ]);
       
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'show_id' => $show->id,
            'seat_id' => $seat->id,
        ]);
        // 2. Создаем объект уведомления
        $notification = new MovieReminderNotification($ticket);

        // 3. Вызываем метод toMail() напрямую!
        $mail = $notification->toMail($user);

        // 4. ПРОВЕРКИ (Assertions):
        
        // Проверяем тему письма (Subject)
        $this->assertStringContainsString('Напоминаем, что ваш сеанс начинается совсем скоро.', $mail->introLines[0]);
        $this->assertStringContainsString('Фильм: Интерстеллар', $mail->introLines[1]);
        $this->assertStringContainsString('Номер билета: #4', $mail->introLines[4]);
        $this->assertStringContainsString('Приятного просмотра!', $mail->introLines[5]);
    }

    public function test_movie_reminders_command_is_scheduled(): void
    {
        // 1. Получаем объект Schedule из контейнера
        $schedule = app(Schedule::class);

        // 2. Ищем команду в списке зарегистрированных событий
        $event = collect($schedule->events())->first(function (Event $event) {
            return str_contains($event->command, 'app:send-movie-reminders-command');
        });

        // 3. Проверяем, что команда найдена и интервал совпадает
        $this->assertNotNull($event, 'Команда не зарегистрирована в Schedule');
        $this->assertEquals('*/10 * * * *', $event->expression); // */10 * * * * = everyTenMinutes()
    }

    public function test_command_does_not_dispatch_jobs_when_no_tickets_found(): void
    {
        Bus::fake();

        // Запускаем команду при пустой базе данных
        $this->artisan('app:send-movie-reminders-command')
            ->expectsOutput("Запланировано отправлений: 0")
            ->assertSuccessful();

        // Проверяем, что в очередь ВООБЩЕ ничего не отправлялось
        Bus::assertNothingDispatched(); // или Bus::assertNotDispatched(SendMovieReminderJob::class);
    }

    public function test_does_not_send_reminder_if_already_sent() : void 
    {
        $now = Carbon::parse('2026-09-19 12:00:00');
        Carbon::setTestNow($now);

        // 2. Включаем фейк очередей (чтобы перехватывать Job::dispatch)
        Bus::fake();

        
        $seat2 = Seat::factory()->create();
        $seat1 = Seat::factory()->create();

        $userTarget = User::factory()->create();
        $movie1 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show1 = Show::factory()->create([
            'movie_id' => $movie1->id,
            'start_time' => $now->copy()->addHours(2),
            'price' => 350,
        ]);

        Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show1->id,
            'seat_id' => $seat1->id,
            'status' => 'paid',
            'reminder_sent' => true
        ]);

        $userOther = User::factory()->create();
        $movie2 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show2 = Show::factory()->create([
            'movie_id' => $movie2->id,
            'start_time' => $now->copy()->addHours(2),
            'price' => 350,
        ]);

        Ticket::factory()->create([
            'user_id' => $userOther->id,
            'show_id' => $show2->id,
            'seat_id' => $seat2->id,
            'status' => 'paid',
            'reminder_sent' => true

        ]);

        // 4. Запускаем КОМАНДУ, а не джобы вручную
        $this->artisan('app:send-movie-reminders-command') // Укажи тут signature своей команды
            ->expectsOutput("Запланировано отправлений: 0")
            ->assertSuccessful();

    
        Bus::assertNotDispatched(SendMovieReminderJob::class);
    }
}
