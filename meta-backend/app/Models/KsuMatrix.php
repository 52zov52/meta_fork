<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KsuMatrix extends Model
{
    protected $table = 'ksu_matrix';

    protected $fillable = [
        'delta',
        'coefficient',
        'description',
        'is_active',
    ];

    protected $casts = [
        'delta' => 'integer',
        'coefficient' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
