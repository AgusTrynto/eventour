<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOrganizer extends Model
{
    protected $fillable = [
        'user_id', 'org_name', 'phone', 'address', 'status', 'reject_reason',
        'bank_name', 'bank_account_number', 'bank_account_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }
}