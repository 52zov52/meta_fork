<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Activity extends Model
{
    protected $fillable = [
        'title',
        'description',
        'date_start',
        'date_end',
        'score',
        'is_dynamic_score',
        'status',
        'planet_id',
        'academy_id',
        'category_id',
        'creator_id',
        'photo_path',
    ];

    protected static function booted(): void
    {
        static::updating(function (Activity $activity): void {
            if (! $activity->isDirty('photo_path')) {
                return;
            }

            $oldPath = $activity->getOriginal('photo_path');
            if (! $oldPath) {
                return;
            }

            DB::afterCommit(function () use ($oldPath): void {
                Storage::disk('public')->delete($oldPath);
            });
        });

        static::deleting(function (Activity $activity) {
            $activity->files->each->delete();

            $photoPath = $activity->photo_path;
            if (! $photoPath) {
                return;
            }

            DB::afterCommit(function () use ($photoPath): void {
                Storage::disk('public')->delete($photoPath);
            });
        });
    }

    public function portfolios(): BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'activity_portfolio')
            ->withTimestamps();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_user')
            ->withPivot('is_confirmed', 'level')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'academy_id');
    }

    /**
     * Получить файлы, прикреплённые к мероприятию.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ActivityFile::class);
    }

    public function parameters(): HasOne
    {
        return $this->hasOne(ActivityParameter::class);
    }

    public function activityScores(): HasMany
    {
        return $this->hasMany(ActivityScore::class);
    }
}
