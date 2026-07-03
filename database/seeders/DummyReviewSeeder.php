<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyReviewSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = (int) env('DUMMY_REVIEW_EVENT_ID', 3);
        $count = (int) env('DUMMY_REVIEW_COUNT', 30);

        $event = Event::findOrFail($eventId);

        $comments = [
            ['rating' => 5, 'comment' => 'Acaranya sangat seru, konsep panggung rapi, dan suasananya ramai tapi tetap nyaman.'],
            ['rating' => 4, 'comment' => 'Event berjalan lancar, hanya antrean masuk agak lama saat jam ramai.'],
            ['rating' => 5, 'comment' => 'Pengisi acara menarik dan lokasi mudah ditemukan. Cocok untuk datang bareng teman.'],
            ['rating' => 3, 'comment' => 'Secara acara oke, tapi area parkir kurang tertata dan petunjuk arah masih minim.'],
            ['rating' => 4, 'comment' => 'Staff cukup membantu, rundown jelas, dan kualitas sound bagus.'],
            ['rating' => 2, 'comment' => 'Check-in terlalu lama dan informasi di lokasi kurang jelas. Perlu lebih banyak petugas.'],
            ['rating' => 5, 'comment' => 'Sangat puas. Venue bersih, acara tepat waktu, dan pengalaman keseluruhan menyenangkan.'],
            ['rating' => 4, 'comment' => 'Harga tiket sepadan dengan pengalaman. Semoga booth makanan ditambah di event berikutnya.'],
            ['rating' => 3, 'comment' => 'Acaranya menarik, tetapi beberapa sesi terasa molor dari jadwal.'],
            ['rating' => 5, 'comment' => 'Dekorasi bagus dan alur masuk keluar peserta cukup tertib.'],
            ['rating' => 4, 'comment' => 'Suka dengan konsep eventnya. Informasi sebelum acara juga cukup membantu.'],
            ['rating' => 2, 'comment' => 'Toilet dan tempat duduk perlu ditambah karena peserta cukup banyak.'],
            ['rating' => 5, 'comment' => 'Salah satu event terbaik yang saya datangi tahun ini. Sangat memorable.'],
            ['rating' => 3, 'comment' => 'Cukup menyenangkan, tapi area tunggu terasa panas dan penuh.'],
            ['rating' => 4, 'comment' => 'MC interaktif, penonton terhibur, dan keamanan terlihat berjaga dengan baik.'],
        ];

        for ($i = 1; $i <= $count; $i++) {
            $sample = $comments[($i - 1) % count($comments)];
            $email = sprintf('dummy-review-event-%d-%03d@eventour.test', $event->id, $i);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => sprintf('Reviewer Dummy %03d', $i),
                    'password' => Hash::make('password'),
                    'role' => 'user',
                ]
            );

            Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                ],
                [
                    'rating' => $sample['rating'],
                    'comment' => $sample['comment'],
                ]
            );
        }
    }
}
