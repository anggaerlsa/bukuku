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
    ];

    /** Account states. Only `active` may use the app. */
    public const STATUSES = [
        'pending' => 'Menunggu Persetujuan',
        'active' => 'Aktif',
        'rejected' => 'Ditolak',
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
            'approved_at' => 'datetime',
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
