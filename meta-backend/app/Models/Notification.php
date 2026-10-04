<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'checked',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
