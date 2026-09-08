<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Collection;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class CreateStripeCheckoutSessionAction
{
    public function __construct()
    {}

    public function execute(Collection $tickets): string
    {
        // 1. Устанавливаем секретный ключ
        Stripe::setApiKey(config('services.stripe.secret'));

        // 2. Формируем позиции для оплаты (например, билеты)
        $lineItems = [];
        foreach ($tickets as $ticket) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => "Билет: {$ticket->show->movie->title} (Ряд {$ticket->seat->row_number}, Место {$ticket->seat->seat_number})",
                    ],
                    'unit_amount' => (int) ($ticket->price * 100), // Цена в центaх
                ],
                'quantity' => 1,
            ];
        }

        // 3. Создаем сессию в Stripe
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            // Передаем ID нашего заказа в метаданных, чтобы связать платеж с нашей БД
            'metadata' => [
                'ticket_ids' => $tickets->pluck('id')->implode(',')
            ],
            'success_url' => config('app.url'),
            'cancel_url' => config('app.url'),
        ]);

        // Возвращаем сгенерированную ссылку
        return $session->url;
    }
}
