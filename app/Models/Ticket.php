<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'order_id', 'event_id', 'user_id', 'attendee_name', 'attendee_nik', 'ticket_code',
        'status', 'checked_in_at', 'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function recommendationFeatureSnapshot()
    {
        return $this->hasOne(RecommendationFeatureSnapshot::class);
    }

    // Generate kode unik, contoh: ET-7F3K9X2A1B
    public static function generateCode(): string
    {
        do {
            $code = 'ET-' . strtoupper(Str::random(10));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }

    // =========================================================
    // Generate tiket untuk order berdasarkan data pemesan & attendees
    // =========================================================
    public static function generateForOrder(Order $order): void
    {
        if (self::where('order_id', $order->id)->exists()) {
            return;
        }

        $attendees    = $order->attendee_details ?? [];
        $user         = $order->user;
        $userName     = $user->name ?? 'Pembeli';
        $userNik      = $user->nik ?? null;
        $event        = $order->event;
        $maxPerPerson = $event->max_tickets_per_person;

        // Hitung berapa tiket yang sudah dimiliki pemilik akun di event ini sebelum order ini
        $userOwnedTicketsCount = 0;
        if ($maxPerPerson !== null) {
            $userOwnedTicketsCount = self::where('event_id', $event->id)
                ->where('order_id', '!=', $order->id)
                ->where(function ($q) use ($userNik, $user) {
                    if ($userNik) {
                        $q->where('attendee_nik', $userNik);
                    } else {
                        $q->where('user_id', $user->id);
                    }
                })
                ->whereIn('status', ['valid', 'used'])
                ->count();
        }

        $userRemainingSlots = $maxPerPerson !== null ? max(0, $maxPerPerson - $userOwnedTicketsCount) : null;

        $holders = [];

        if (!empty($attendees)) {
            // Jika ada data pemegang tiket dari input form:
            if ($userRemainingSlots !== null && $userRemainingSlots === 0) {
                // Pembeli utama sudah mencapai limit kuota, seluruh tiket untuk attendees dari form
            } else {
                // Pembeli utama masih punya kuota, masukkan pembeli utama untuk 1 tiket miliknya
                $holders[] = [
                    'name'     => $userName,
                    'nik'      => $userNik,
                    'capacity' => 1,
                ];
            }

            foreach ($attendees as $attendee) {
                if (is_array($attendee)) {
                    $holders[] = [
                        'name'     => $attendee['name'] ?? ($attendee['nama'] ?? $userName),
                        'nik'      => $attendee['nik'] ?? null,
                        'capacity' => 1,
                    ];
                } else {
                    $holders[] = [
                        'name'     => (string) $attendee,
                        'nik'      => null,
                        'capacity' => 1,
                    ];
                }
            }
        } else {
            // Tidak ada attendees khusus (semua tiket atas nama pembeli utama)
            $holders[] = [
                'name'     => $userName,
                'nik'      => $userNik,
                'capacity' => $order->quantity,
            ];
        }

        $holderIndex = 0;
        $currentHolderAssigned = 0;

        for ($i = 0; $i < $order->quantity; $i++) {
            $holder = $holders[$holderIndex] ?? end($holders) ?: ['name' => $userName, 'nik' => $userNik, 'capacity' => 1];

            self::create([
                'order_id'      => $order->id,
                'event_id'      => $order->event_id,
                'user_id'       => $order->user_id,
                'attendee_name' => $holder['name'],
                'attendee_nik'  => $holder['nik'],
                'ticket_code'   => self::generateCode(),
                'status'        => 'valid',
            ]);

            $currentHolderAssigned++;
            if ($currentHolderAssigned >= ($holder['capacity'] ?? 1)) {
                if (isset($holders[$holderIndex + 1])) {
                    $holderIndex++;
                    $currentHolderAssigned = 0;
                }
            }
        }

        $order->refresh();

        if (class_exists(\App\Services\RecommendationFeatureSnapshotService::class)) {
            app(\App\Services\RecommendationFeatureSnapshotService::class)->recordPurchasedOrder($order);
        }
    }
}
