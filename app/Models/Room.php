<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }
}
