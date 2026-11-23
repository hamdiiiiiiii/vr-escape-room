<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuzzleResult extends Model
{
    protected $guarded = [];

    protected $casts = [
        'solved' => 'boolean',
        'attempts' => 'integer',
        'time_seconds' => 'integer',
        'hints_used' => 'integer',
        'score' => 'integer',
        'wrong_answers' => 'array',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
