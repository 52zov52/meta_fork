<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityUser extends Model
{
    protected $table = 'activity_user';

    protected $fillable = [
        'user_id',
        'activity_id',
        'level',
        'place',
        'earned_score',
        'is_confirmed',
    ];

    protected $casts = [
        'level' => 'decimal:2',
        'place' => 'string',
        'earned_score' => 'integer',
        'is_confirmed' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
