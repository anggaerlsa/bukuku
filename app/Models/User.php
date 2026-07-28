<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'approved_at',
        'approved_by',
        'ai_consented_at',
        'ai_consent_version',
    ];

    /** Account states. Only `active` may use the app. */
    public const STATUSES = [
        'pending' => 'Menunggu Persetujuan',
        'active' => 'Aktif',
        'rejected' => 'Ditolak',
    ];

    /**
     * Which revision of the AI disclosure the author must have agreed to.
     *
     * Bump this whenever the notice changes what is actually sent to the
     * provider, and every author is asked again. A past yes must not stand in
     * for terms they never read.
     */
    public const AI_CONSENT_VERSION = 1;

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
            'approved_at' => 'datetime',
            'ai_consented_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Worlds (universes) authored by this user.
     */
    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    /** Who approved this account, if anyone. */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    /** API keys this author has saved, one per provider. */
    public function aiCredentials(): HasMany
    {
        return $this->hasMany(AiCredential::class);
    }

    public function aiCredentialFor(?string $provider = null): ?AiCredential
    {
        return $this->aiCredentials()
            ->where('provider', $provider ?: config('ai.default'))
            ->first();
    }

    /** Has this author agreed to the current AI disclosure? */
    public function hasAiConsent(): bool
    {
        return $this->ai_consented_at !== null
            && (int) $this->ai_consent_version >= self::AI_CONSENT_VERSION;
    }

    /** Only an approved account may leave the waiting page. */
    public function isApproved(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * A short label for the user's primary role, for display.
     */
    public function primaryRoleLabel(): string
    {
        return match (true) {
            $this->hasRole('superadmin') => 'Superadmin',
            $this->hasRole('admin') => 'Admin',
            $this->hasRole('author') => 'Penulis',
            default => 'Tanpa Peran',
        };
    }
}
