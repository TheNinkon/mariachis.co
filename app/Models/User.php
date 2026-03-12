<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\MariachiPasswordResetNotification;
use App\Notifications\ClientPasswordSetupNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_MARIACHI = 'mariachi';
    public const ROLE_CLIENT = 'client';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'auth_provider',
        'auth_provider_id',
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

    public function mariachiProfile(): HasOne
    {
        return $this->hasOne(MariachiProfile::class);
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function favoriteMariachiProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MariachiProfile::class, 'client_favorites')
            ->withTimestamps();
    }

    public function mariachiListings(): HasManyThrough
    {
        return $this->hasManyThrough(
            MariachiListing::class,
            MariachiProfile::class,
            'user_id',
            'mariachi_profile_id',
            'id',
            'id'
        );
    }

    public function favoriteMariachiListings(): BelongsToMany
    {
        return $this->belongsToMany(MariachiListing::class, 'client_favorites', 'user_id', 'mariachi_listing_id')
            ->withTimestamps();
    }

    public function recentViews(): HasMany
    {
        return $this->hasMany(ClientRecentView::class);
    }

    public function clientQuoteConversations(): HasMany
    {
        return $this->hasMany(QuoteConversation::class, 'client_user_id');
    }

    public function clientLoginTokens(): HasMany
    {
        return $this->hasMany(ClientLoginToken::class);
    }

    public function quoteMessages(): HasMany
    {
        return $this->hasMany(QuoteMessage::class, 'sender_user_id');
    }

    public function clientReviews(): HasMany
    {
        return $this->hasMany(MariachiReview::class, 'client_user_id');
    }

    public function reviewedVerificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class, 'reviewed_by_user_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $fullName = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $fullName !== '' ? $fullName : (string) $this->name;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isMariachi(): bool
    {
        return $this->role === self::ROLE_MARIACHI;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function sendPasswordResetNotification($token): void
    {
        if ($this->isClient()) {
            $this->notify(new ClientPasswordSetupNotification($token));

            return;
        }

        if ($this->isMariachi()) {
            $this->notify(new MariachiPasswordResetNotification($token));

            return;
        }

        $this->notify(new ResetPasswordNotification($token));
    }
}
