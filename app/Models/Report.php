<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $guarded = [];
        protected $casts = [
        'concept_scores' => 'array',
        'ai_analysis' => 'array',
    ];
}
