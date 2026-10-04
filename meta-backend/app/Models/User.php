<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'previous_semester_score',
        'is_terms_accepted',
        'is_activated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->portfolios()
                ->chunkById(100, function ($portfolios): void {
                    $portfolios->each->delete();
                });
        });
    }

    /**
     * Scope a query to search users by name, email, group or full FIO.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByKeyword($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        // Split search into individual words for robust multi-word matching
        $words = array_values(array_filter(preg_split('/\s+/u', $search) ?: []));

        return $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->where(function ($inner) use ($word) {
                    $inner->where('users.email', 'like', "%{$word}%");

                    // Detect if the query already has a join with members
                    $joins = collect($inner->getQuery()->joins);
                    $hasMembersJoin = $joins->contains(function ($join) {
                        return $join->table === 'members';
                    });

                    if ($hasMembersJoin) {
                        // Fast direct column matches if joined
                        $inner->orWhereRaw("CONCAT_WS(' ', members.last_name, members.first_name, members.middle_name) LIKE ?", ["%{$word}%"])
                              ->orWhere('members.student_group', 'like', "%{$word}%");
                    } else {
                        // Safe subquery lookup if not joined
                        $inner->orWhereHas('member', function ($mq) use ($word) {
                            $mq->whereRaw("CONCAT_WS(' ', last_name, first_name, middle_name) LIKE ?", ["%{$word}%"])
                               ->orWhere('student_group', 'like', "%{$word}%");
                        });
                    }
                });
            }
        });
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_user')
            ->withPivot('is_confirmed', 'level')
            ->withTimestamps();
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function marketplaceOrders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
