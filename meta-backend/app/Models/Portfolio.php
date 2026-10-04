<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Portfolio extends Model
{
    protected $table = 'portfolio';

    protected $fillable = [
        'user_id',
        'title',
        'path_to_file',
        'original_name',
        'extension',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    //  Скрываем внутренний путь к файлу в API ответах
    protected $hidden = [
        'path_to_file',
    ];

    //  Добавляем вычисляемые поля
    protected $appends = [
        'file_url',
        'has_activities',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Portfolio $portfolio): void {
            if (! $portfolio->path_to_file) {
                return;
            }

            $pathToFile = $portfolio->path_to_file;

            DB::afterCommit(function () use ($pathToFile): void {
                Storage::disk('public')->delete($pathToFile);
            });
        });
    }

    /**
     *  Получить полный URL файла
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->path_to_file ? asset('storage/'.$this->path_to_file) : null;
    }

    /**
     *  Проверка наличия привязанных мероприятий
     */
    public function getHasActivitiesAttribute(): bool
    {
        return $this->activities()->exists();
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     *  Связь многие-ко-многим с мероприятиями
     */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_portfolio')
            ->withTimestamps()
            ->orderBy('date_start', 'desc');
    }
}
