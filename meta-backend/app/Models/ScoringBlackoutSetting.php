<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringBlackoutSetting extends Model
{
    protected $fillable = [
        'winter_start',
        'winter_end',
        'summer_start',
        'summer_end',
    ];
}
