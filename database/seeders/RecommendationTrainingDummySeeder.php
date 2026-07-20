<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventOrganizer;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\RecommendationFeatureSnapshotService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;

class RecommendationTrainingDummySeeder extends Seeder
{
    private const USER_COUNT = 60;

    private const CATEGORIES = [
        'musik',
        'seni',
        'olahraga',
        'kuliner',
        'teknologi',
        'travel',
        'gaming',
        'workshop',
        'seminar',
        'fashion_beauty',
        'komunitas',
        'bazaar',
        'otomotif',
    ];

    private const CITIES = [
        'Jakarta' => [
            'lat' => -6.2088,
            'lng' => 106.8456,
            'venues' => ['M Bloc Space', 'Taman Ismail Marzuki', 'Sarinah', 'Hutan Kota GBK'],
        ],
        'Bandung' => [
            'lat' => -6.9175,
            'lng' => 107.6191,
            'venues' => ['Gedung Sate', 'Braga City Walk', 'Cihampelas Walk', 'Taman Film'],
        ],
        'Tegal' => [
            'lat' => -6.8797,
            'lng' => 109.1256,
            'venues' => ['Alun-Alun Kota Tegal', 'GOR Wisanggeni', 'Rita Supermall', 'Pantai Alam Indah'],
        ],
        'Semarang' => [
            'lat' => -6.9667,
            'lng' => 110.4167,
            'venues' => ['Kota Lama Semarang', 'Simpang Lima', 'Taman Indonesia Kaya', 'PRPP Jawa Tengah'],
        ],
        'Yogyakarta' => [
            'lat' => -7.7956,
            'lng' => 110.3695,
            'venues' => ['Taman Budaya Yogyakarta', 'Malioboro', 'Jogja Expo Center', 'Alun-Alun Kidul'],
        ],
    ];

    private const EVENT_TEMPLATES = [
        'musik' => [
            ['title' => 'Indie Night', 'description' => 'Konser band lokal, rilisan baru, dan panggung komunitas musik.', 'hour' => 19, 'duration' => 4, 'base_price' => 90000],
            ['title' => 'Jazz Picnic', 'description' => 'Pertunjukan jazz santai dengan area piknik dan tenant kreatif.', 'hour' => 16, 'duration' => 4, 'base_price' => 130000],
            ['title' => 'Acoustic Session', 'description' => 'Sesi akustik intimate bersama musisi lokal dan open mic.', 'hour' => 18, 'duration' => 3, 'base_price' => 65000],
            ['title' => 'Band Showcase', 'description' => 'Showcase band kampus, komunitas, dan kolaborasi lintas genre.', 'hour' => 20, 'duration' => 3, 'base_price' => 110000],
        ],
        'seni' => [
            ['title' => 'Art Market', 'description' => 'Pasar seni, merchandise ilustrator, live drawing, dan workshop kreatif.', 'hour' => 10, 'duration' => 8, 'base_price' => 35000],
            ['title' => 'Batik Workshop', 'description' => 'Kelas membatik untuk pemula bersama perajin dan komunitas lokal.', 'hour' => 9, 'duration' => 4, 'base_price' => 55000],
            ['title' => 'Photo Walk', 'description' => 'Hunting foto kota, sesi komposisi, dan diskusi street photography.', 'hour' => 7, 'duration' => 4, 'base_price' => 45000],
            ['title' => 'Craft Class', 'description' => 'Kelas craft, clay, rajut, dan produk handmade untuk komunitas kreatif.', 'hour' => 13, 'duration' => 4, 'base_price' => 70000],
        ],
        'olahraga' => [
            ['title' => 'Fun Run 5K', 'description' => 'Lari santai 5K dengan refreshment, medali finisher, dan komunitas sehat.', 'hour' => 6, 'duration' => 3, 'base_price' => 85000],
            ['title' => 'Mini Soccer Cup', 'description' => 'Turnamen mini soccer antar komunitas dengan babak grup dan final.', 'hour' => 8, 'duration' => 8, 'base_price' => 60000],
            ['title' => 'Yoga Morning', 'description' => 'Sesi yoga pagi, mindful breathing, dan kelas pemulihan ringan.', 'hour' => 7, 'duration' => 2, 'base_price' => 50000],
            ['title' => 'Cycling Meet', 'description' => 'Gowes komunitas, city ride, sharing rute aman, dan coffee stop.', 'hour' => 6, 'duration' => 4, 'base_price' => 40000],
        ],
        'kuliner' => [
            ['title' => 'Food Night Market', 'description' => 'Festival jajanan malam, tenant lokal, demo masak, dan live acoustic.', 'hour' => 17, 'duration' => 6, 'base_price' => 25000],
            ['title' => 'Coffee Festival', 'description' => 'Kurasi roaster lokal, cupping session, kelas brewing, dan talkshow kopi.', 'hour' => 10, 'duration' => 9, 'base_price' => 65000],
            ['title' => 'Street Food Tour', 'description' => 'Tur kuliner kota, rekomendasi hidden gem, dan tasting menu lokal.', 'hour' => 18, 'duration' => 4, 'base_price' => 75000],
            ['title' => 'Cooking Demo', 'description' => 'Demo masak chef lokal, resep rumahan, dan sesi tanya jawab.', 'hour' => 14, 'duration' => 3, 'base_price' => 55000],
        ],
        'teknologi' => [
            ['title' => 'Tech Meetup', 'description' => 'Meetup developer, founder, product builder, dan sesi sharing teknologi.', 'hour' => 18, 'duration' => 3, 'base_price' => 0],
            ['title' => 'Startup Talk', 'description' => 'Diskusi startup, validasi produk, growth, dan networking founder.', 'hour' => 19, 'duration' => 3, 'base_price' => 90000],
            ['title' => 'Game Dev Showcase', 'description' => 'Pameran game indie, playtest, panel developer, dan komunitas kreator.', 'hour' => 11, 'duration' => 7, 'base_price' => 120000],
            ['title' => 'AI Workshop', 'description' => 'Workshop machine learning, prompt engineering, data, dan prototyping AI.', 'hour' => 9, 'duration' => 6, 'base_price' => 150000],
        ],
        'lainnya' => [
            ['title' => 'Community Fair', 'description' => 'Pameran komunitas lokal, booth edukasi, bazaar, dan aktivitas keluarga.', 'hour' => 10, 'duration' => 7, 'base_price' => 20000],
            ['title' => 'Family Picnic', 'description' => 'Piknik keluarga, aktivitas anak, mini games, dan panggung komunitas.', 'hour' => 8, 'duration' => 5, 'base_price' => 30000],
            ['title' => 'Charity Bazaar', 'description' => 'Bazaar amal, donasi komunitas, thrift market, dan pertunjukan kecil.', 'hour' => 11, 'duration' => 7, 'base_price' => 25000],
            ['title' => 'Campus Expo', 'description' => 'Expo kampus, komunitas mahasiswa, talkshow karier, dan booth edukasi.', 'hour' => 9, 'duration' => 6, 'base_price' => 15000],
        ],
        'travel' => [
            ['title' => 'City Tour Guide', 'description' => 'Tur kota bersama guide lokal, kunjungan hidden gems, dan walking tour.', 'hour' => 8, 'duration' => 6, 'base_price' => 80000],
            ['title' => 'Backpacker Gathering', 'description' => 'Meetup backpacker, sharing itinerary, tips solo travel, dan komunitas.', 'hour' => 18, 'duration' => 3, 'base_price' => 35000],
            ['title' => 'Photo Trip', 'description' => 'Trip fotografi ke spot ikonik, komposisi landscape, dan golden hour hunt.', 'hour' => 5, 'duration' => 8, 'base_price' => 120000],
            ['title' => 'Travel Talk', 'description' => 'Diskusi traveling, budaya lokal, sustainable travel, dan kisah inspiratif.', 'hour' => 15, 'duration' => 3, 'base_price' => 45000],
        ],
        'gaming' => [
            ['title' => 'Game Night', 'description' => 'Malam bermain game, turnamen ringan, dan sesi main bareng komunitas.', 'hour' => 19, 'duration' => 4, 'base_price' => 50000],
            ['title' => 'E-Sports Tournament', 'description' => 'Turnamen e-sports antar komunitas, hadiah menarik, dan siaran langsung.', 'hour' => 10, 'duration' => 10, 'base_price' => 100000],
            ['title' => 'Board Game Meet', 'description' => 'Kumpul komunitas board game, belajar aturan baru, dan sesi seru.', 'hour' => 13, 'duration' => 5, 'base_price' => 35000],
            ['title' => 'Indie Game Launch', 'description' => 'Peluncuran game indie lokal, playtest langsung, dan diskusi developer.', 'hour' => 14, 'duration' => 6, 'base_price' => 0],
        ],
        'workshop' => [
            ['title' => 'Creative Workshop', 'description' => 'Workshop kreatif, hands-on membuat produk, dan coaching kreativitas.', 'hour' => 9, 'duration' => 6, 'base_price' => 95000],
            ['title' => 'Writing Class', 'description' => 'Kelas menulis kreatif, teknik narasi, dan forum diskusi penulis.', 'hour' => 10, 'duration' => 4, 'base_price' => 65000],
            ['title' => 'Design Sprint', 'description' => 'Sprint desain produk, prototyping cepat, dan validasi bersama mentor.', 'hour' => 8, 'duration' => 8, 'base_price' => 150000],
            ['title' => 'Skillshare Session', 'description' => 'Sesi berbagi keterampilan bahasa, digital, public speaking, dan lainnya.', 'hour' => 15, 'duration' => 3, 'base_price' => 40000],
        ],
        'seminar' => [
            ['title' => 'Business Seminar', 'description' => 'Seminar bisnis dengan praktisi, strategi skala usaha, dan networking.', 'hour' => 9, 'duration' => 6, 'base_price' => 120000],
            ['title' => 'Motivational Talk', 'description' => 'Talkshow motivasi bersama tokoh inspiratif dan sesi tanya jawab.', 'hour' => 14, 'duration' => 3, 'base_price' => 70000],
            ['title' => 'Career Panel', 'description' => 'Panel karir dengan profesional berbagai industri dan tips rekrutmen.', 'hour' => 10, 'duration' => 5, 'base_price' => 85000],
            ['title' => 'Industry Summit', 'description' => 'Konferensi industri, tren terbaru, kolaborasi lintas sektor, dan pameran.', 'hour' => 8, 'duration' => 9, 'base_price' => 180000],
        ],
        'fashion_beauty' => [
            ['title' => 'Fashion Show', 'description' => 'Pertunjukan fashion koleksi desainer lokal, runway, dan kreativitas busana.', 'hour' => 19, 'duration' => 3, 'base_price' => 95000],
            ['title' => 'Beauty Workshop', 'description' => 'Workshop kecantikan, tutorial makeup, skincare, dan tips perawatan diri.', 'hour' => 10, 'duration' => 4, 'base_price' => 70000],
            ['title' => 'Thrift Market', 'description' => 'Pasar thrift fashion, second hand berkualitas, dan komunitas sustainable.', 'hour' => 9, 'duration' => 8, 'base_price' => 15000],
            ['title' => 'Style Talk', 'description' => 'Diskusi gaya, mix and match, personal branding, dan fashion tips.', 'hour' => 15, 'duration' => 3, 'base_price' => 55000],
        ],
        'komunitas' => [
            ['title' => 'Komunitas Meetup', 'description' => 'Meetup komunitas lintas minat, sharing ide, dan kolaborasi baru.', 'hour' => 15, 'duration' => 4, 'base_price' => 20000],
            ['title' => 'Networking Night', 'description' => 'Malam networking, bertemu profesional, dan memperluas koneksi.', 'hour' => 18, 'duration' => 3, 'base_price' => 60000],
            ['title' => 'Volunteer Gathering', 'description' => 'Kumpul relawan, aksi sosial, program komunitas, dan dampak positif.', 'hour' => 8, 'duration' => 5, 'base_price' => 0],
            ['title' => 'Community Service', 'description' => 'Bakti sosial, kerja bakti, edukasi masyarakat, dan kebersamaan.', 'hour' => 7, 'duration' => 6, 'base_price' => 0],
        ],
        'bazaar' => [
            ['title' => 'Weekend Bazaar', 'description' => 'Bazaar akhir pekan dengan tenant lokal, kuliner, dan produk kreatif.', 'hour' => 9, 'duration' => 9, 'base_price' => 0],
            ['title' => 'Artisan Fair', 'description' => 'Pameran produk artisan, kerajinan tangan, dan kolaborasi pengrajin.', 'hour' => 10, 'duration' => 8, 'base_price' => 25000],
            ['title' => 'Pop-Up Market', 'description' => 'Pasar pop-up dengan konsep unik, limited edition, dan tenant musiman.', 'hour' => 11, 'duration' => 8, 'base_price' => 20000],
            ['title' => 'Creative Bazaar', 'description' => 'Bazaar kreatif, merchandise ilustrator, zine, dan komunitas seni.', 'hour' => 10, 'duration' => 7, 'base_price' => 30000],
        ],
        'otomotif' => [
            ['title' => 'Car Meet', 'description' => 'Kumpul komunitas otomotif, pameran modifikasi, dan sesi foto bersama.', 'hour' => 8, 'duration' => 5, 'base_price' => 40000],
            ['title' => 'Motor Show', 'description' => 'Pameran motor modifikasi, kontes, dan gathering bikers komunitas.', 'hour' => 9, 'duration' => 7, 'base_price' => 45000],
            ['title' => 'Auto Talk', 'description' => 'Diskusi otomotif, review kendaraan, tips modifikasi, dan keselamatan.', 'hour' => 15, 'duration' => 3, 'base_price' => 30000],
            ['title' => 'Racing Day', 'description' => 'Hari balap komunitas, drag race, dan kompetisi keterampilan mengemudi.', 'hour' => 7, 'duration' => 8, 'base_price' => 110000],
        ],
    ];

    private const PERSONAS = [
        ['name' => 'Music Night Explorer', 'categories' => ['musik', 'kuliner'], 'cities' => ['Jakarta', 'Bandung'], 'max_price' => 160000],
        ['name' => 'Creative Weekend Hunter', 'categories' => ['seni', 'workshop'], 'cities' => ['Yogyakarta', 'Semarang'], 'max_price' => 90000],
        ['name' => 'Morning Sport Fan', 'categories' => ['olahraga'], 'cities' => ['Jakarta', 'Tegal'], 'max_price' => 100000],
        ['name' => 'Food Festival Seeker', 'categories' => ['kuliner', 'musik'], 'cities' => ['Bandung', 'Tegal', 'Semarang'], 'max_price' => 90000],
        ['name' => 'Tech Builder', 'categories' => ['teknologi', 'workshop'], 'cities' => ['Jakarta', 'Yogyakarta'], 'max_price' => 180000],
        ['name' => 'Travel Explorer', 'categories' => ['travel', 'kuliner'], 'cities' => ['Yogyakarta', 'Bandung', 'Semarang'], 'max_price' => 120000],
        ['name' => 'Gamer Enthusiast', 'categories' => ['gaming', 'teknologi'], 'cities' => ['Jakarta', 'Bandung'], 'max_price' => 130000],
        ['name' => 'Lifelong Learner', 'categories' => ['seminar', 'workshop'], 'cities' => ['Semarang', 'Yogyakarta'], 'max_price' => 150000],
        ['name' => 'Fashion Lover', 'categories' => ['fashion_beauty', 'bazaar'], 'cities' => ['Jakarta', 'Bandung', 'Semarang'], 'max_price' => 100000],
        ['name' => 'Community Activist', 'categories' => ['komunitas', 'seni'], 'cities' => ['Tegal', 'Yogyakarta'], 'max_price' => 60000],
        ['name' => 'Bazaar Hunter', 'categories' => ['bazaar', 'kuliner'], 'cities' => ['Jakarta', 'Tegal', 'Semarang'], 'max_price' => 50000],
        ['name' => 'Auto Enthusiast', 'categories' => ['otomotif', 'teknologi'], 'cities' => ['Jakarta', 'Bandung'], 'max_price' => 140000],
    ];

    public function run(): void
    {
        $organizer = $this->organizer();
        $events = $this->seedEvents($organizer);
        $this->command?->info("Training events ready: {$events->count()} events.");

        $users = $this->seedUsers();
        $this->command?->info("Training users ready: {$users->count()} users.");

        $ordersCreated = $this->seedOrders($users, $events);

        $this->command?->info(sprintf(
            'Recommendation training dummy ready: %d users, %d events, %d paid orders.',
            $users->count(),
            $events->count(),
            $ordersCreated
        ));
    }

    private function organizer(): EventOrganizer
    {
        $user = User::updateOrCreate(
            ['email' => 'training.eo@eventour.test'],
            [
                'name' => 'EvenTour Training EO',
                'password' => $this->passwordHash(),
                'role' => 'eo',
            ]
        );

        return EventOrganizer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'org_name' => 'EvenTour Training Organizer',
                'phone' => '081200001234',
                'address' => 'Jl. Data Rekomendasi No. 1, Jakarta',
                'status' => 'approved',
                'bank_name' => 'BCA',
                'bank_channel_code' => 'ID_BCA',
                'bank_account_number' => '1234500000',
                'bank_account_name' => 'EvenTour Training Organizer',
                'location' => new Point(-6.2088, 106.8456),
            ]
        );
    }

    private function seedEvents(EventOrganizer $organizer): Collection
    {
        $events = collect();
        $cityNames = array_keys(self::CITIES);

        foreach ($cityNames as $cityIndex => $cityName) {
            $city = self::CITIES[$cityName];

            foreach (self::CATEGORIES as $categoryIndex => $category) {
                foreach (self::EVENT_TEMPLATES[$category] as $templateIndex => $template) {
                    $venue = $city['venues'][$templateIndex % count($city['venues'])];
                    $dayOffset = (($cityIndex * 17 + $categoryIndex * 9 + $templateIndex * 6) % 95) - 30;
                    $startDate = now()->copy()
                        ->startOfDay()
                        ->addDays($dayOffset)
                        ->setTime($template['hour'], 0);
                    $endDate = $startDate->copy()->addHours($template['duration']);
                    $coordinateOffset = (($categoryIndex - 2) * 0.006) + (($templateIndex - 1) * 0.003);
                    $price = $this->eventPrice((int) $template['base_price'], $cityIndex, $templateIndex);

                    $event = Event::updateOrCreate(
                        [
                            'event_organizer_id' => $organizer->id,
                            'title' => "{$cityName} {$template['title']}",
                        ],
                        [
                            'description' => "{$template['description']} Area {$cityName} di {$venue}.",
                            'category' => $category,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'location_name' => "{$venue}, {$cityName}",
                            'location' => new Point(
                                $city['lat'] + $coordinateOffset,
                                $city['lng'] - $coordinateOffset
                            ),
                            'price' => $price,
                            'quota' => 80 + (($cityIndex + 1) * 70) + ($templateIndex * 45),
                            'status' => 'approved',
                            'reject_reason' => null,
                        ]
                    );

                    $events->push($event->fresh());
                }
            }
        }

        return $events;
    }

    private function seedUsers(): Collection
    {
        $users = collect();
        $cityNames = array_keys(self::CITIES);

        for ($index = 1; $index <= self::USER_COUNT; $index++) {
            $persona = self::PERSONAS[($index - 1) % count(self::PERSONAS)];
            $homeCityName = $persona['cities'][($index - 1) % count($persona['cities'])];
            $homeCity = self::CITIES[$homeCityName] ?? self::CITIES[$cityNames[0]];
            $offset = (($index % 9) - 4) * 0.004;

            $users->push(User::updateOrCreate(
                ['email' => sprintf('training.user%03d@eventour.test', $index)],
                [
                    'name' => sprintf('Training User %03d', $index),
                    'password' => $this->passwordHash(),
                    'role' => 'user',
                    'last_location' => new Point(
                        $homeCity['lat'] + $offset,
                        $homeCity['lng'] - $offset
                    ),
                ]
            )->fresh());
        }

        return $users;
    }

    private function seedOrders(Collection $users, Collection $events): int
    {
        $snapshotService = app(RecommendationFeatureSnapshotService::class);
        $ordersCreated = 0;

        foreach ($users->values() as $userIndex => $user) {
            $persona = self::PERSONAS[$userIndex % count(self::PERSONAS)];
            $selectedEvents = $this->selectedEventsForPersona($events, $persona, $userIndex);

            foreach ($selectedEvents->values() as $orderIndex => $event) {
                $quantity = 1 + (($userIndex + $orderIndex) % 3);
                $paidAt = $this->paidAtFor($event, $userIndex, $orderIndex);

                $order = Order::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'payment_method' => 'dummy-training-recommendation',
                    ],
                    [
                        'quantity' => $quantity,
                        'unit_price' => $event->price,
                        'total_amount' => (float) $event->price * $quantity,
                        'payment_status' => 'paid',
                        'payment_proof' => null,
                        'paid_at' => $paidAt,
                        'refunded_at' => null,
                        'refund_reason' => null,
                    ]
                );

                $order->forceFill([
                    'created_at' => $paidAt->copy()->subHours(2),
                    'updated_at' => now(),
                ])->saveQuietly();

                $this->ensureTickets($order, $quantity);
                $order->refresh();
                $snapshotService->recordPurchasedOrder($order);
                $ordersCreated++;
            }

            if (($userIndex + 1) % 10 === 0) {
                $this->command?->line(sprintf(
                    'Training orders progress: %d/%d users processed.',
                    $userIndex + 1,
                    $users->count()
                ));
            }
        }

        return $ordersCreated;
    }

    private function selectedEventsForPersona(Collection $events, array $persona, int $userIndex): Collection
    {
        $preferred = $events
            ->filter(fn (Event $event) => in_array($event->category, $persona['categories'], true)
                && $this->eventMatchesAnyCity($event, $persona['cities'])
                && (float) $event->price <= $persona['max_price'])
            ->values();

        $fallbackPreferred = $events
            ->filter(fn (Event $event) => in_array($event->category, $persona['categories'], true))
            ->values();

        $exploration = $events
            ->filter(fn (Event $event) => ! in_array($event->category, $persona['categories'], true)
                && (float) $event->price <= $persona['max_price'])
            ->values();

        return collect()
            ->merge($this->deterministicPick($preferred->isNotEmpty() ? $preferred : $fallbackPreferred, 7, $userIndex * 3))
            ->merge($this->deterministicPick($exploration, 3, $userIndex * 5))
            ->unique('id')
            ->take(10)
            ->values();
    }

    private function deterministicPick(Collection $items, int $count, int $offset): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        return collect(range(0, $count - 1))
            ->map(fn (int $index) => $items[($offset + ($index * 2)) % $items->count()]);
    }

    private function ensureTickets(Order $order, int $quantity): void
    {
        $existingTickets = Ticket::where('order_id', $order->id)->count();
        $now = now();
        $tickets = [];

        for ($ticketNumber = $existingTickets + 1; $ticketNumber <= $quantity; $ticketNumber++) {
            $tickets[] = [
                'order_id' => $order->id,
                'event_id' => $order->event_id,
                'user_id' => $order->user_id,
                'ticket_code' => sprintf('TRAIN-%d-%d-%02d', $order->id, $order->event_id, $ticketNumber),
                'status' => 'valid',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($tickets !== []) {
            Ticket::insert($tickets);
        }
    }

    private function paidAtFor(Event $event, int $userIndex, int $orderIndex): Carbon
    {
        $paidAt = $event->start_date
            ? $event->start_date->copy()->subDays(2 + (($userIndex + $orderIndex) % 21))
            : now()->subDays(1 + (($userIndex + $orderIndex) % 60));

        if ($paidAt->gt(now())) {
            return now()->subDays(1 + (($userIndex + $orderIndex) % 45));
        }

        return $paidAt;
    }

    private function eventPrice(int $basePrice, int $cityIndex, int $templateIndex): int
    {
        if ($basePrice === 0) {
            return 0;
        }

        return $basePrice + ($cityIndex * 5000) + ($templateIndex * 7500);
    }

    private function eventMatchesAnyCity(Event $event, array $cities): bool
    {
        foreach ($cities as $city) {
            if (str_contains($event->location_name, $city)) {
                return true;
            }
        }

        return false;
    }

    private function passwordHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('password');
    }
}
