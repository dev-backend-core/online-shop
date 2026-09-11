<?php

namespace App\Http\Controllers;

use App\Events\BookingCreated;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook');

        try {
            // 1. Проверяем подпись вебхука от Stripe
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Невалидный payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Невалидная подпись (секрет whsec_ не совпал)
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 2. Обрабатываем тип события
        switch ($event->type) {
            case 'checkout.session.completed':
                $paymentIntent = $event->data->object;
                $rawTickets = $paymentIntent->metadata->ticket_ids ?? '';
                $ticketIds = array_filter(explode(',', $rawTickets));

                if($paymentIntent->payment_status === 'paid' && !empty($ticketIds)){

                   

                    Ticket::whereIn('id',$ticketIds)
                    ->update(['status' => 'paid']);

                    BookingCreated::dispatch($ticketIds);
                }
                // ТУТ ВАША ЛОГИКА ДЛЯ БИЛЕТОВ:
                // - Найти заказ в БД по $paymentIntent->id или $paymentIntent->metadata->order_id
                // - Изменить статус заказа на "paid" (Оплачен)
                // - Перевести забронированные билеты в статус "куплены"
                // - Отправить email с билетами пользователю

                Log::info('Stripe Payment Succeeded: ' . $paymentIntent->id);
                break;

            case 'checkout.session.expired':
                $paymentIntent = $event->data->object;
                $rawTickets = $paymentIntent->metadata->ticket_ids ?? '';
                $ticketIds = array_filter(explode(',', $rawTickets));

                if(!empty($ticketIds)){
                    Ticket::whereIn('id',$ticketIds)
                    ->update(['status' => 'cancelled']);
                }
                
                // ТУТ ЛОГИКА ПРИ ОТМЕНЕ ПЛАТЕЖА:
                // - Снять временную бронь с мест
                
                Log::warning('Stripe Payment Failed: ' . $paymentIntent->id);
                break;

            default:
                Log::info('Received unknown event type ' . $event->type);
        }

        // 3. Возвращаем 200 OK, чтобы Stripe знал, что запрос обработан
        return response()->json(['status' => 'success'], 200);
    }
}
