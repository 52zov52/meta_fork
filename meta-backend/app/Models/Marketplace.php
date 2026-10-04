<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Marketplace extends Model
{
    protected $table = 'marketplace';

    protected $fillable = [
        'name',
        'score',
        'photo_path',
        'is_archive',
        'creator_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (Marketplace $marketplace): void {
            if (! $marketplace->isDirty('photo_path')) {
                return;
            }

            $oldPath = $marketplace->getOriginal('photo_path');
            if (! $oldPath) {
                return;
            }

            DB::afterCommit(function () use ($oldPath): void {
                Storage::disk('public')->delete($oldPath);
            });
        });

        static::deleting(function (Marketplace $marketplace): void {
            $photoPath = $marketplace->photo_path;
            if (! $photoPath) {
                return;
            }

            DB::afterCommit(function () use ($photoPath): void {
                Storage::disk('public')->delete($photoPath);
            });
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
