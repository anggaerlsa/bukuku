<?php

namespace App\Models;

use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasImages;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * A faction, house, army, order or guild inside a world. Characters join it
 * through OrganizationMember, which is where their rank lives.
 */
class Organization extends Model
{
    use HasCustomFields;
    use HasImages;

    protected $fillable = [
        'world_id',
        'parent_id',
        'name',
        'aliases',
        'type',
        'status',
        'motto',
        'summary',
        'description',
        'purpose',
        'history',
        'emblem_image',
        'headquarters_type',
        'headquarters_id',
    ];

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'aktif' => 'Aktif',
            'bubar' => 'Bubar',
            'rahasia' => 'Rahasia',
            'tak-diketahui' => 'Tak Diketahui',
        ];
    }

    /** Column holding the record's cover picture (see HasImages). */
    public function coverColumn(): string
    {
        return 'emblem_image';
    }

    /** @return list<string> */
    public function customFieldTargets(): array
    {
        return ['organization'];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /** The bigger body this one sits inside — a division under its army. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Where it is based. Locations span five tables, hence the morph. */
    public function headquarters(): MorphTo
    {
        return $this->morphTo();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    /** The author's own label if given, otherwise a neutral fallback. */
    public function displayLabel(): string
    {
        return $this->type ?: 'Organisasi';
    }

    public function emblemUrl(): string
    {
        if (! $this->emblem_image) {
            return 'https://placehold.co/400x400/4f46e5/ffffff?text='
                . urlencode(Str::of($this->name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode(''));
        }

        return Uploads::url($this->emblem_image);
    }
}
