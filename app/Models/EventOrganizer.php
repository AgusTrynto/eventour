<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOrganizer extends Model
{
    protected $fillable = ['user_id', 'org_name', 'phone', 'address', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}