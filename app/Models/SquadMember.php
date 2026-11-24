<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SquadMember extends Model
{
    protected $fillable = ['squad_id', 'user_id'];

    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
