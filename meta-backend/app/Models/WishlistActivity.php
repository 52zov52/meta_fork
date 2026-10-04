<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistActivity extends Model
{
    protected $table = 'wishlist_activity';

    protected $fillable = [
        'user_id',
        'activity_id',
    ];
}
