<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecommendationDummySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        $purchases = [
            [
                'title' => 'Jakarta Indie Night',
                'quantity' => 2,
                'paid_at' => now()->subDays(16),
            ],
            [
                'title' => 'Pop-Up Art Market',
                'quantity' => 1,
                'paid_at' => now()->subDays(2),
            ],
            [
                'title' => 'Kuliner Malam Bandung',
                'quantity' => 1,
                'paid_at' => now()->subDays(4),
            ],
            [
                'title' => 'Tegal Creative Market',
                'quantity' => 1,
                'paid_at' => now()->subDays(7),
            ],
        ];

        foreach ($purchases as $purchase) {
            $event = Event::where('title', $purchase['title'])->first();

            if (!$event) {
                continue;
            }

            $order = Order::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'payment_method' => 'dummy-recommendation',
                ],
                [
                    'quantity' => $purchase['quantity'],
                    'unit_price' => $event->price,
                    'total_amount' => (float) $event->price * $purchase['quantity'],
                    'payment_status' => 'paid',
                    'payment_proof' => null,
                    'paid_at' => $purchase['paid_at'],
                    'refunded_at' => null,
                    'refund_reason' => null,
                ]
            );

            $existingTickets = Ticket::where('order_id', $order->id)->count();

            for ($i = $existingTickets + 1; $i <= $purchase['quantity']; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'ticket_code' => sprintf('REC-%d-%d-%02d', $user->id, $event->id, $i),
                    'status' => 'valid',
                ]);
            }

            $order->refresh();

            app(RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);
        }
    }
}
