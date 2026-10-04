<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaseScore extends Model
{
    protected $table = 'base_scores';

    protected $fillable = [
        'category_id',
        'activity_type',
        'has_places',
        'has_implementation',
        'is_leader',
        'first_place',
        'second_place',
        'third_place',
        'participation',
        'is_active',
    ];

    protected $casts = [
        'has_places' => 'boolean',
        'has_implementation' => 'boolean',
        'is_leader' => 'boolean',
        'is_active' => 'boolean',
        'first_place' => 'integer',
        'second_place' => 'integer',
        'third_place' => 'integer',
        'participation' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id');
    }
}
