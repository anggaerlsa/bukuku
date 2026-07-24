<?php

namespace App\Models;

use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasImages;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Character extends Model
{
    /** @use HasFactory<\Database\Factories\CharacterFactory> */
    use HasFactory;
    use HasCustomFields;
    use HasImages;

    /** Column holding the record's cover picture (see HasImages). */
    public function coverColumn(): string
    {
        return 'portrait_image';
    }

    /** @return list<string> */
    public function customFieldTargets(): array
    {
        return ['character'];
    }

    protected $fillable = [
        'world_id',
        'name',
        'aliases',
        'role',
        'species',
        'gender',
        'age',
        'status',
        'occupation',
        'affiliation',
        'appearance',
        'personality',
        'backstory',
        'goals',
        'portrait_image',
        'origin_type',
        'origin_id',
        'residence_type',
        'residence_id',
    ];

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            'protagonis' => 'Protagonis',
            'antagonis' => 'Antagonis',
            'deuteragonis' => 'Deuteragonis',
            'pendukung' => 'Pendukung',
            'mentor' => 'Mentor',
            'figuran' => 'Figuran',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'hidup' => 'Hidup',
            'wafat' => 'Wafat',
            'hilang' => 'Hilang',
            'tak-diketahui' => 'Tak Diketahui',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /**
     * Where the character comes from. Locations sit in five separate tables,
     * so this is polymorphic: origin_type holds the tier key (see the morph
     * map in AppServiceProvider).
     */
    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    /** Where the character currently lives. */
    public function residence(): MorphTo
    {
        return $this->morphTo();
    }

    /** Which organisations this character belongs to, and with what rank. */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /** Ties created from this character's page. */
    public function relationsOut(): HasMany
    {
        return $this->hasMany(CharacterRelation::class, 'character_id');
    }

    /** Ties created from the other character's page that name this one. */
    public function relationsIn(): HasMany
    {
        return $this->hasMany(CharacterRelation::class, 'related_character_id');
    }

    /**
     * Every tie involving this character, always phrased from ITS point of
     * view: incoming rows are flipped to their inverse type, so "B is A's
     * child" shows up on B's page as "Orang Tua: A".
     *
     * @return Collection<int, array{id:int, type:string, label:string, group:string, other:Character, note:?string}>
     */
    public function relationEntries(): Collection
    {
        $this->loadMissing(['relationsOut.relatedCharacter', 'relationsIn.character']);

        $entries = collect();

        foreach ($this->relationsOut as $relation) {
            $entries->push($this->relationEntry($relation, $relation->type, $relation->relatedCharacter));
        }

        foreach ($this->relationsIn as $relation) {
            $entries->push($this->relationEntry($relation, CharacterRelation::inverse($relation->type), $relation->character));
        }

        return $entries
            ->filter(fn (array $entry) => $entry['other'] !== null)
            ->sortBy([
                fn (array $a, array $b) => $a['group'] <=> $b['group'],
                fn (array $a, array $b) => $a['label'] <=> $b['label'],
                fn (array $a, array $b) => $a['other']->name <=> $b['other']->name,
            ])
            ->values();
    }

    private function relationEntry(CharacterRelation $relation, string $type, ?Character $other): array
    {
        return [
            'id' => $relation->id,
            'type' => $type,
            'label' => CharacterRelation::label($type),
            'group' => CharacterRelation::group($type),
            'other' => $other,
            'note' => $relation->note,
        ];
    }

    public function roleLabel(): ?string
    {
        return $this->role ? (static::roles()[$this->role] ?? ucfirst($this->role)) : null;
    }

    public function statusLabel(): ?string
    {
        return $this->status ? (static::statuses()[$this->status] ?? ucfirst($this->status)) : null;
    }

    public function portraitUrl(): string
    {
        if (! $this->portrait_image) {
            return 'https://placehold.co/400x500/3b2a17/e4c95e?font=playfair-display&text='
                . urlencode(Str::of($this->name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode(''));
        }

        return Uploads::url($this->portrait_image);
    }
}
