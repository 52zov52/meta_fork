<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParameter extends Model
{
    protected $table = 'activity_parameters';

    protected $fillable = [
        'activity_id',
        'activity_type',
        'has_places',
        'has_implementation',
        'is_leader',
        'target_education_level_ids',
    ];

    protected $casts = [
        'has_places' => 'boolean',
        'has_implementation' => 'boolean',
        'is_leader' => 'boolean',
        'target_education_level_ids' => 'array',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
