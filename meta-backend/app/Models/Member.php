<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'gender',
        'birthday',
        'region_id',
        'planet_id',
        'institution_id',
        'institution',
        'course',
        'student_group',
        'profile_photo_path',
        'user_id',
        'academy_id',
        'education_level_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (Member $member): void {
            if (! $member->isDirty('profile_photo_path')) {
                return;
            }

            $oldPath = $member->getOriginal('profile_photo_path');
            if (! $oldPath) {
                return;
            }

            DB::afterCommit(function () use ($oldPath): void {
                Storage::disk('public')->delete($oldPath);
            });
        });

        static::deleted(function (Member $member): void {
            if (! $member->profile_photo_path) {
                return;
            }

            $path = $member->profile_photo_path;

            DB::afterCommit(function () use ($path): void {
                Storage::disk('public')->delete($path);
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_name.' '.$this->first_name.' '.$this->middle_name,
        );
    }

    public function shortName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $result = $this->last_name.' '.mb_substr($this->first_name, 0, 1).'.';

                if (! empty($this->middle_name)) {
                    $result .= ' '.mb_substr($this->middle_name, 0, 1).'.';
                }

                return $result;
            }
        );
    }

    public function institutionDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->institution_id == 4) {
                    return $this->getAttributes()['institution'] ?? null;
                }

                return $this->getRelation('institution')?->name;
            }
        );
    }
}
