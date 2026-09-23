<?php

namespace Tests\Feature;

use App\Events\BookingCreated;
use App\Models\Movie;
use App\Models\Seat;
use App\Models\Show;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Stripe\Event as StripeEvent;
use Stripe\Webhook;

class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_tickets_status_to_paid_and_dispatches_event_on_successful_checkout()
    {
        // 1. Подготовка: создаем 2 билета со статусом 'reserved'
        Event::fake([BookingCreated::class]); // Замораживаем ивент, чтобы он не отправлял реальные письма

        $userTarget = User::factory()->create();
        $movie1 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $movie2 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show1 = Show::factory()->create([
            'movie_id' => $movie1->id,
            'price' => 350,
        ]);

        $show2 = Show::factory()->create([
            'movie_id' => $movie2->id,
            'price' => 350,
        ]);

        $seat1 = Seat::factory()->create();
        $seat2 = Seat::factory()->create();

        $ticket1 = Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show1->id,
            'seat_id' => $seat1->id,
            'status' => 'reserved',
        ]);

        $ticket2 = Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show2->id,
            'seat_id' => $seat2->id,
            'status' => 'reserved',
        ]);
      
        $ticketIdsStr = "{$ticket1->id},{$ticket2->id}";

        // 2. Мокаем класс Webhook от Stripe, чтобы обнулить проверку подписи
        $fakeStripeEvent = StripeEvent::constructFrom([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_12345',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'ticket_ids' => $ticketIdsStr,
                    ],
                ],
            ],
        ]);

        // Подменяем вызов Stripe\Webhook::constructEvent(...)
        $this->mock('alias:' . Webhook::class, function ($mock) use ($fakeStripeEvent) {
            $mock->shouldReceive('constructEvent')->andReturn($fakeStripeEvent);
        });

        // 3. Вызов метода (отправляем POST запрос на наш вебхук)
        $response = $this->postJson('/api/stripe/webhook', [], [
            'Stripe-Signature' => 't=123,v1=fake_signature',
        ]);

        // 4. Проверки:
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Проверяем, что статусы билетов изменились на 'paid'
        $this->assertDatabaseHas('tickets', ['id' => $ticket1->id, 'status' => 'paid']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket2->id, 'status' => 'paid']);

        // Проверяем, что вызывался ивент BookingCreated с нашими ID
        Event::assertDispatched(BookingCreated::class, function ($event) use ($ticket1, $ticket2) {
            return $event->tickets === [(string)$ticket1->id, (string)$ticket2->id];
        });
    }

    public function test_it_cancels_tickets_when_checkout_session_expires()
    {
        $userTarget = User::factory()->create();
        $movie1 = Movie::factory()->create([
            'slug'  => 'bebe-' . fake()->unique()->slug(),
        ]);

        $show1 = Show::factory()->create([
            'movie_id' => $movie1->id,
            'price' => 350,
        ]);

        $seat1 = Seat::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $userTarget->id,
            'show_id' => $show1->id,
            'seat_id' => $seat1->id,
            'status' => 'reserved',
        ]);

        // 2. Готовим фейковый Stripe Event со статусом expired
        $fakeStripeEvent = StripeEvent::constructFrom([
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_expired',
                    'metadata' => ['ticket_ids' => (string) $ticket->id],
                ],
            ],
        ]);

        $this->mock('alias:' . Webhook::class, function ($mock) use ($fakeStripeEvent) {
            $mock->shouldReceive('constructEvent')->andReturn($fakeStripeEvent);
        });

        // 3. Делаем запрос
        $response = $this->postJson('/api/stripe/webhook', [], [
            'Stripe-Signature' => 't=123,v1=fake_signature',
        ]);

        // 4. Проверяем, что ответ 200 и статус билета изменился на 'cancelled'
        $response->assertStatus(200);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'cancelled']);
    }


    public function test_returns_400_when_signature_is_invalid()
    {
        // Мы НЕ мокаем класс Webhook!
        // Настоящий метод Webhook::constructEvent выбросит исключение SignatureVerificationException

        $this->withoutExceptionHandling();
        
        $response = $this->postJson('/api/stripe/webhook', ['payload'], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature or payload']);
    }
}
