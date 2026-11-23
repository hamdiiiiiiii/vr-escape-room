<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $guarded = [];

    protected $casts = [
        'concept_scores' => 'array',
        'ai_analysis' => 'array',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
