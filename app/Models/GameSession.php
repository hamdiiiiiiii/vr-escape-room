<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $guarded = [];
        protected $casts = [
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
        public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function report()
    {
        return $this->hasOne(Report::class);
    }
}
