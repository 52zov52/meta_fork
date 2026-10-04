<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityScore extends Model
{
    protected $table = 'activity_scores';

    protected $fillable = [
        'activity_id',
        'education_level_id',
        'place',
        'base_score',
        'nuo_value',
        'uta_level',
        'delta',
        'ksu_coefficient',
        'final_score',
    ];

    protected $casts = [
        'base_score' => 'integer',
        'nuo_value' => 'integer',
        'uta_level' => 'integer',
        'delta' => 'integer',
        'ksu_coefficient' => 'decimal:2',
        'final_score' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }
}
