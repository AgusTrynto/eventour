<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NeuralContentRecommendationService;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$mode = $argv[1] ?? 'inspect';
$userId = (int) ($argv[2] ?? 2);
$limit = (int) ($argv[3] ?? 3);

$user = User::find($userId);

if (!$user) {
    fwrite(STDERR, "User {$userId} tidak ditemukan.\n");
    exit(1);
}

$dummyOrders = [
    [
        'title' => 'Jakarta Indie Night',
        'quantity' => 2,
        'status' => 'paid',
        'created_at' => now()->subDays(18),
        'paid_at' => now()->subDays(16),
    ],
    [
        'title' => 'Workshop Fotografi Kota Lama',
        'quantity' => 1,
        'status' => 'pending',
        'created_at' => now()->subDays(10),
        'paid_at' => null,
    ],
    [
        'title' => 'Tegal Creative Market',
        'quantity' => 1,
        'status' => 'cancelled',
        'created_at' => now()->subDays(7),
        'paid_at' => null,
    ],
];

if ($mode === 'apply') {
    $snapshotService = app(RecommendationFeatureSnapshotService::class);

    foreach ($dummyOrders as $dummyOrder) {
        $event = Event::where('title', $dummyOrder['title'])->first();

        if (!$event) {
            echo "Skip: event {$dummyOrder['title']} tidak ditemukan.\n";
            continue;
        }

        $order = Order::updateOrCreate(
            [
                'user_id' => $user->id,
                'event_id' => $event->id,
                'payment_method' => 'dummy-user2-recommendation',
            ],
            [
                'quantity' => $dummyOrder['quantity'],
                'unit_price' => $event->price,
                'total_amount' => (float) $event->price * $dummyOrder['quantity'],
                'payment_status' => $dummyOrder['status'],
                'payment_proof' => null,
                'paid_at' => $dummyOrder['paid_at'],
                'refunded_at' => null,
                'refund_reason' => null,
                'created_at' => $dummyOrder['created_at'],
                'updated_at' => now(),
            ]
        );

        if ($dummyOrder['status'] === 'paid') {
            $existingTickets = Ticket::where('order_id', $order->id)->count();

            for ($i = $existingTickets + 1; $i <= $dummyOrder['quantity']; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'ticket_code' => sprintf('U2REC-%d-%d-%02d', $order->id, $event->id, $i),
                    'status' => 'valid',
                ]);
            }
        }

        $order->refresh();
        $snapshotService->recordPurchasedOrder($order);

        echo "OK: {$dummyOrder['status']} order {$order->id} untuk {$event->title}\n";
    }
}

$orders = Order::where('user_id', $user->id)
    ->with('event:id,title,category,start_date,price')
    ->latest('id')
    ->get()
    ->map(fn (Order $order) => [
        'id' => $order->id,
        'event' => $order->event?->title,
        'category' => $order->event?->category,
        'status' => $order->payment_status,
        'method' => $order->payment_method,
        'created_at' => $order->created_at?->toDateTimeString(),
    ])
    ->values();

$recommendations = app(NeuralContentRecommendationService::class)
    ->recommendForUser($user, $limit)
    ->map(fn (array $recommendation) => [
        'event_id' => $recommendation['event']->id,
        'title' => $recommendation['event']->title,
        'category' => $recommendation['event']->category,
        'score' => round((float) $recommendation['score'], 6),
        'score_label' => $recommendation['score_label'],
        'model_label' => $recommendation['model_label'],
        'price_label' => $recommendation['price_label'],
    ])
    ->values();

echo json_encode([
    'user' => $user->only(['id', 'name', 'email', 'role']),
    'dummy_plan' => $dummyOrders,
    'orders' => $orders,
    'recommendations' => $recommendations,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
