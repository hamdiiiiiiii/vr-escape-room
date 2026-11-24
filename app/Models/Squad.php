<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Squad extends Model
{
    protected $fillable = ['leader_id', 'max_players'];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SquadMember::class)->orderBy('id');
    }
}
