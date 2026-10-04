<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ActivityFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ActivityFile $activityFile) {
            $path = $activityFile->file_path;
            if (! $path) {
                return;
            }

            DB::afterCommit(function () use ($path): void {
                Storage::disk('public')->delete($path);
            });
        });
    }

    /**
     * Получить мероприятие, к которому прикреплён файл.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Получить размер файла в человекочитаемом формате.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['Б', 'КБ', 'МБ', 'ГБ'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
