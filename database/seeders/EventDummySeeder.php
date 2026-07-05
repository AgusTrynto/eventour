<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventOrganizer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;

class EventDummySeeder extends Seeder
{
    public function run(): void
    {
        $eoUser = User::updateOrCreate(
            ['email' => 'dummy.eo@eventour.test'],
            [
                'name' => 'EvenTour Dummy EO',
                'password' => Hash::make('password'),
                'role' => 'eo',
            ]
        );

        $organizer = EventOrganizer::updateOrCreate(
            ['user_id' => $eoUser->id],
            [
                'org_name' => 'EvenTour Creative Organizer',
                'phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 10, Jakarta',
                'status' => 'approved',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'EvenTour Creative Organizer',
                'location' => new Point(-6.2088, 106.8456),
            ]
        );

        $events = [
            [
                'title' => 'Jakarta Indie Night',
                'description' => 'Malam musik indie dengan lineup lokal Jakarta.',
                'category' => 'musik',
                'start_date' => now()->subDays(18)->setTime(19, 0),
                'end_date' => now()->subDays(18)->setTime(22, 30),
                'location_name' => 'M Bloc Space, Jakarta',
                'lat' => -6.2434,
                'lng' => 106.7983,
                'price' => 125000,
                'quota' => 300,
            ],
            [
                'title' => 'Workshop Fotografi Kota Lama',
                'description' => 'Kelas singkat street photography dan hunting foto bersama.',
                'category' => 'seni',
                'start_date' => now()->subDays(10)->setTime(9, 0),
                'end_date' => now()->subDays(10)->setTime(13, 0),
                'location_name' => 'Kota Tua, Jakarta',
                'lat' => -6.1352,
                'lng' => 106.8133,
                'price' => 75000,
                'quota' => 60,
            ],
            [
                'title' => 'Kuliner Malam Bandung',
                'description' => 'Festival jajanan lokal dan demo masak komunitas.',
                'category' => 'kuliner',
                'start_date' => now()->subDays(4)->setTime(17, 0),
                'end_date' => now()->subDays(4)->setTime(22, 0),
                'location_name' => 'Gedung Sate, Bandung',
                'lat' => -6.9025,
                'lng' => 107.6188,
                'price' => 50000,
                'quota' => 500,
            ],
            [
                'title' => 'Mini Soccer Community Cup',
                'description' => 'Turnamen mini soccer antar komunitas kampus dan pekerja muda.',
                'category' => 'olahraga',
                'start_date' => now()->subDay()->setTime(8, 0),
                'end_date' => now()->subDay()->setTime(16, 0),
                'location_name' => 'Lapangan Aldiron, Jakarta',
                'lat' => -6.2441,
                'lng' => 106.8317,
                'price' => 35000,
                'quota' => 200,
            ],
            [
                'title' => 'Pop-Up Art Market',
                'description' => 'Pasar kreatif seniman lokal, merchandise, dan live drawing.',
                'category' => 'seni',
                'start_date' => now()->subHours(2),
                'end_date' => now()->addHours(5),
                'location_name' => 'Taman Ismail Marzuki, Jakarta',
                'lat' => -6.1901,
                'lng' => 106.8390,
                'price' => 0,
                'quota' => 400,
            ],
            [
                'title' => 'Tech Startup Meetup',
                'description' => 'Networking founder, product builder, dan sesi sharing teknologi.',
                'category' => 'teknologi',
                'start_date' => now()->addDays(3)->setTime(18, 30),
                'end_date' => now()->addDays(3)->setTime(21, 0),
                'location_name' => 'GoWork Plaza Indonesia, Jakarta',
                'lat' => -6.1938,
                'lng' => 106.8227,
                'price' => 100000,
                'quota' => 120,
            ],
            [
                'title' => 'Sunday Jazz Picnic',
                'description' => 'Konser jazz santai di ruang terbuka untuk keluarga dan komunitas.',
                'category' => 'musik',
                'start_date' => now()->addDays(6)->setTime(15, 0),
                'end_date' => now()->addDays(6)->setTime(19, 0),
                'location_name' => 'Hutan Kota GBK, Jakarta',
                'lat' => -6.2186,
                'lng' => 106.8025,
                'price' => 150000,
                'quota' => 800,
            ],
            [
                'title' => 'Fun Run 5K Senayan',
                'description' => 'Lari santai 5K dengan rute kawasan Senayan.',
                'category' => 'olahraga',
                'start_date' => now()->addDays(9)->setTime(6, 0),
                'end_date' => now()->addDays(9)->setTime(9, 0),
                'location_name' => 'Gelora Bung Karno, Jakarta',
                'lat' => -6.2183,
                'lng' => 106.8022,
                'price' => 85000,
                'quota' => 1000,
            ],
            [
                'title' => 'Festival Kopi Nusantara',
                'description' => 'Kurasi roaster lokal, cupping session, dan talkshow kopi.',
                'category' => 'kuliner',
                'start_date' => now()->addDays(14)->setTime(10, 0),
                'end_date' => now()->addDays(14)->setTime(21, 0),
                'location_name' => 'Sarinah, Jakarta',
                'lat' => -6.1879,
                'lng' => 106.8231,
                'price' => 65000,
                'quota' => 700,
            ],
            [
                'title' => 'Game Developer Showcase',
                'description' => 'Pameran game indie, playtest, dan panel bersama developer lokal.',
                'category' => 'teknologi',
                'start_date' => now()->addDays(21)->setTime(11, 0),
                'end_date' => now()->addDays(21)->setTime(18, 0),
                'location_name' => 'ICE BSD, Tangerang',
                'lat' => -6.3016,
                'lng' => 106.6527,
                'price' => 120000,
                'quota' => 600,
            ],
            [
                'title' => 'Tegal Food Night Market',
                'description' => 'Festival kuliner malam khas Tegal dengan tenant lokal dan hiburan akustik.',
                'category' => 'kuliner',
                'start_date' => now()->addDays(4)->setTime(17, 0),
                'end_date' => now()->addDays(4)->setTime(22, 30),
                'location_name' => 'Alun-Alun Kota Tegal',
                'lat' => -6.8694,
                'lng' => 109.1402,
                'price' => 25000,
                'quota' => 500,
            ],
            [
                'title' => 'Pantai Alam Indah Music Sunset',
                'description' => 'Konser sore di area Pantai Alam Indah dengan panggung komunitas musik lokal.',
                'category' => 'musik',
                'start_date' => now()->addDays(8)->setTime(16, 0),
                'end_date' => now()->addDays(8)->setTime(20, 0),
                'location_name' => 'Pantai Alam Indah, Tegal',
                'lat' => -6.8439,
                'lng' => 109.1445,
                'price' => 60000,
                'quota' => 700,
            ],
            [
                'title' => 'Workshop Batik Tegalan',
                'description' => 'Kelas membatik motif Tegalan bersama perajin lokal untuk pemula.',
                'category' => 'seni',
                'start_date' => now()->addDays(11)->setTime(9, 0),
                'end_date' => now()->addDays(11)->setTime(13, 0),
                'location_name' => 'Taman Pancasila, Tegal',
                'lat' => -6.8687,
                'lng' => 109.1438,
                'price' => 45000,
                'quota' => 80,
            ],
            [
                'title' => 'Slawi Fun Run 5K',
                'description' => 'Lari santai 5K untuk komunitas olahraga Tegal dan sekitarnya.',
                'category' => 'olahraga',
                'start_date' => now()->addDays(15)->setTime(6, 0),
                'end_date' => now()->addDays(15)->setTime(9, 0),
                'location_name' => 'Alun-Alun Slawi, Kabupaten Tegal',
                'lat' => -6.9816,
                'lng' => 109.1392,
                'price' => 80000,
                'quota' => 600,
            ],
            [
                'title' => 'Tegal Tech Meetup',
                'description' => 'Meetup teknologi untuk developer, mahasiswa, dan founder lokal Tegal.',
                'category' => 'teknologi',
                'start_date' => now()->addDays(19)->setTime(18, 30),
                'end_date' => now()->addDays(19)->setTime(21, 0),
                'location_name' => 'Rita Supermall Tegal',
                'lat' => -6.8859,
                'lng' => 109.1257,
                'price' => 0,
                'quota' => 120,
            ],
            [
                'title' => 'Tegal Creative Market',
                'description' => 'Pasar kreatif komunitas Tegal berisi produk lokal, craft, dan live painting.',
                'category' => 'seni',
                'start_date' => now()->subDays(7)->setTime(10, 0),
                'end_date' => now()->subDays(7)->setTime(18, 0),
                'location_name' => 'GOR Wisanggeni, Tegal',
                'lat' => -6.8760,
                'lng' => 109.1262,
                'price' => 30000,
                'quota' => 300,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                [
                    'event_organizer_id' => $organizer->id,
                    'title' => $event['title'],
                ],
                [
                    'description' => $event['description'],
                    'category' => $event['category'],
                    'start_date' => $event['start_date'],
                    'end_date' => $event['end_date'],
                    'location_name' => $event['location_name'],
                    'location' => new Point($event['lat'], $event['lng']),
                    'price' => $event['price'],
                    'quota' => $event['quota'],
                    'status' => 'approved',
                    'reject_reason' => null,
                ]
            );
        }
    }
}
