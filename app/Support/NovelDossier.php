<?php

namespace App\Support;

use App\Ai\Ai;
use App\Models\Character;
use App\Models\LoreEntry;
use App\Models\Novel;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Everything the assistant is allowed to know, assembled from ONE novel.
 *
 * This class is where the novel-level lock actually lives. Not in the system
 * prompt — an instruction not to discuss other novels is advice, and advice can
 * be argued with. Here, every query starts from the world ids and book ids of
 * the novel it was handed, so material from another novel is never fetched, is
 * never in memory, and cannot be in the request body. The assistant is not
 * forbidden from knowing about novel B; it is never told about it.
 *
 * The output is deliberately dull, stable Markdown in a fixed order. Providers
 * cache prompt prefixes, and a cache hit is two orders of magnitude cheaper
 * than a miss — so nothing here may vary between requests. No timestamps, no
 * "generated at", no per-request notes.
 *
 * Records in the bin are absent throughout: every model carries SoftDeletes, so
 * the ordinary relations already exclude them. Thrown-away lore should not come
 * back as an answer.
 */
class NovelDossier
{
    private function __construct(
        public readonly string $text,
        public readonly int $tokens,
        public readonly int $chaptersIncluded,
        public readonly int $chaptersTotal,
        /** Where the manuscript was cut off, phrased for the author. */
        public readonly ?string $trimmedFrom,
    ) {}

    public static function for(Novel $novel, bool $withManuscript = true): self
    {
        $worlds = $novel->worlds()->orderBy('name')->orderBy('id')->get();
        $worldIds = $worlds->pluck('id');
        $custom = new CustomValues($worldIds);

        $parts = [
            self::novelPart($novel),
            self::worldsPart($worlds),
            self::charactersPart($worldIds, $worlds, $custom),
            self::locationsPart($worldIds, $worlds, $custom),
            self::organizationsPart($worldIds, $worlds, $custom),
            self::lorePart($worldIds, $worlds, $custom),
        ];

        $lore = implode("\n", array_filter($parts));

        // The lore is never trimmed: it is the part the assistant needs in full
        // to stay consistent, and it is a fraction of the manuscript's size.
        // The manuscript is what gives, chapter by chapter, from the end.
        $budget = (int) (config('ai.context_budget') * (float) config('ai.chars_per_token'));
        $manuscript = $withManuscript
            ? self::manuscript($novel, $budget - mb_strlen($lore))
            : ['text' => '', 'included' => 0, 'total' => self::chapterCount($novel), 'trimmedFrom' => null];

        $text = rtrim($lore . $manuscript['text']) . "\n";

        return new self(
            text: $text,
            tokens: Ai::estimateTokens($text),
            chaptersIncluded: $manuscript['included'],
            chaptersTotal: $manuscript['total'],
            trimmedFrom: $manuscript['trimmedFrom'],
        );
    }

    public function wasTrimmed(): bool
    {
        return $this->trimmedFrom !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */

    private static function novelPart(Novel $novel): string
    {
        $out = "# NOVEL: {$novel->title}\n";
        $out .= self::fields([
            'Status' => $novel->statusLabel(),
            'Tema' => $novel->themeLabel(),
            'Genre' => $novel->genres->pluck('name')->implode(', '),
            'Tagline' => $novel->tagline,
        ]);

        if (filled($novel->synopsis)) {
            $out .= "\n## Sinopsis\n{$novel->synopsis}\n";
        }

        return $out;
    }

    private static function worldsPart(Collection $worlds): string
    {
        if ($worlds->isEmpty()) {
            return '';
        }

        $out = "\n# DUNIA\n";

        foreach ($worlds as $world) {
            $out .= "\n## {$world->name}\n";
            $out .= self::fields([
                'Status' => $world->statusLabel(),
                'Tagline' => $world->tagline,
                'Premis' => $world->premise,
            ]);
        }

        return $out;
    }

    private static function charactersPart(Collection $worldIds, Collection $worlds, CustomValues $custom): string
    {
        $characters = Character::whereIn('world_id', $worldIds)
            ->with(['origin', 'residence', 'memberships.organization', 'relationsOut.relatedCharacter', 'relationsIn.character'])
            ->orderBy('name')->orderBy('id')
            ->get();

        if ($characters->isEmpty()) {
            return '';
        }

        $out = "\n# KARAKTER\n";

        foreach ($characters as $character) {
            $out .= "\n## {$character->name}" . self::inWorld($character, $worlds) . "\n";
            $out .= self::fields([
                'Alias' => $character->aliases,
                'Peran' => $character->roleLabel(),
                'Spesies' => $character->species,
                'Gender' => $character->gender,
                'Usia' => $character->age,
                'Keadaan' => $character->statusLabel(),
                'Pekerjaan' => $character->occupation,
                'Afiliasi' => $character->affiliation,
                'Asal' => self::placeLabel($character->origin),
                'Domisili' => self::placeLabel($character->residence),
                'Penampilan' => $character->appearance,
                'Kepribadian' => $character->personality,
                'Latar belakang' => $character->backstory,
                'Tujuan' => $character->goals,
            ]);

            $out .= self::fields([
                'Hubungan' => $character->relationEntries()
                    ->map(fn (array $e) => $e['label'] . ': ' . $e['other']->name . ($e['note'] ? " ({$e['note']})" : ''))
                    ->implode('; '),
                'Keanggotaan' => $character->memberships
                    ->filter(fn ($m) => $m->organization !== null)
                    ->map(fn ($m) => $m->organization->name . ($m->role ? " — {$m->role}" : '') . ($m->status ? " ({$m->status})" : ''))
                    ->implode('; '),
            ]);

            $out .= $custom->fieldsFor($character);
        }

        return $out;
    }

    private static function locationsPart(Collection $worldIds, Collection $worlds, CustomValues $custom): string
    {
        $out = '';
        // Parent names collected as we descend, so a city can say which province
        // it sits in without a lookup per row.
        $names = [];

        foreach (Hierarchy::keys() as $tier) {
            $model = Hierarchy::model($tier);
            $rows = $model::whereIn('world_id', $worldIds)->orderBy('name')->orderBy('id')->get();

            $names[$tier] = $rows->pluck('name', 'id')->all();

            if ($rows->isEmpty()) {
                continue;
            }

            $out .= "\n# LOKASI · " . Hierarchy::label($tier) . "\n";
            $parentTier = Hierarchy::parent($tier);
            $parentKey = Hierarchy::parentForeignKey($tier);

            foreach ($rows as $row) {
                $parent = $parentTier
                    ? [Hierarchy::label($parentTier) => $names[$parentTier][$row->{$parentKey}] ?? null]
                    : [];

                $out .= "\n## {$row->name}" . self::inWorld($row, $worlds) . "\n";
                $out .= self::fields([
                    'Tingkat' => Hierarchy::label($tier),
                    'Jenis' => $row->type,
                ] + $parent + [
                    'Ringkasan' => $row->summary,
                    'Deskripsi' => $row->description,
                    'Geografi' => $row->geography,
                    'Iklim' => $row->climate,
                    'Populasi' => $row->population,
                    'Pemerintahan' => $row->government,
                    'Tempat penting' => $row->points_of_interest,
                ]);

                $out .= $custom->fieldsFor($row);
            }
        }

        return $out;
    }

    private static function organizationsPart(Collection $worldIds, Collection $worlds, CustomValues $custom): string
    {
        $organizations = Organization::whereIn('world_id', $worldIds)
            ->with(['parent', 'headquarters', 'memberships.character'])
            ->orderBy('name')->orderBy('id')
            ->get();

        if ($organizations->isEmpty()) {
            return '';
        }

        $out = "\n# ORGANISASI\n";

        foreach ($organizations as $organization) {
            $out .= "\n## {$organization->name}" . self::inWorld($organization, $worlds) . "\n";
            $out .= self::fields([
                'Alias' => $organization->aliases,
                'Jenis' => $organization->type,
                'Keadaan' => $organization->statusLabel(),
                'Semboyan' => $organization->motto,
                'Induk' => $organization->parent?->name,
                'Markas' => self::placeLabel($organization->headquarters),
                'Ringkasan' => $organization->summary,
                'Deskripsi' => $organization->description,
                'Tujuan' => $organization->purpose,
                'Sejarah' => $organization->history,
                'Anggota' => $organization->memberships
                    ->filter(fn ($m) => $m->character !== null)
                    ->map(fn ($m) => $m->character->name . ($m->role ? " — {$m->role}" : '') . ($m->status ? " ({$m->status})" : ''))
                    ->implode('; '),
            ]);

            $out .= $custom->fieldsFor($organization);
        }

        return $out;
    }

    private static function lorePart(Collection $worldIds, Collection $worlds, CustomValues $custom): string
    {
        $entries = LoreEntry::whereIn('world_id', $worldIds)
            ->orderBy('category')->orderBy('position')->orderBy('title')->orderBy('id')
            ->get();

        if ($entries->isEmpty()) {
            return '';
        }

        $out = "\n# ARTIKEL LORE\n";

        foreach ($entries as $entry) {
            $category = $entry->category ? "[{$entry->category}] " : '';
            $out .= "\n## {$category}{$entry->title}" . self::inWorld($entry, $worlds) . "\n";
            $out .= self::fields(['Ringkasan' => $entry->summary]);
            $out .= $custom->fieldsFor($entry);

            // The body is kept verbatim, Markdown tables and all: several of
            // these articles ARE tables, and reformatting them would lose the
            // structure the author put there.
            if (filled($entry->body)) {
                $out .= "\n{$entry->body}\n";
            }
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | The manuscript
    |--------------------------------------------------------------------------
    */

    /**
     * Chapters in reading order, up to the character budget left over.
     *
     * @return array{text: string, included: int, total: int, trimmedFrom: ?string}
     */
    private static function manuscript(Novel $novel, int $budget): array
    {
        $total = self::chapterCount($novel);
        $out = "\n# NASKAH\n";
        $spent = mb_strlen($out);
        $included = 0;
        $trimmedFrom = null;

        foreach ($novel->books as $book) {
            if ($trimmedFrom !== null) {
                break;
            }

            // Written only once a chapter of this book actually fits, so a book
            // whose first chapter is over budget leaves no dangling heading.
            $heading = "\n## Buku: {$book->title}\n";

            foreach ($book->chapters()->orderBy('position')->orderBy('id')->cursor() as $chapter) {
                $piece = ($heading ?: '') . "\n### Bab {$chapter->position}: {$chapter->title}\n{$chapter->body}\n";
                $length = mb_strlen($piece);

                if ($spent + $length > $budget) {
                    $left = $total - $included;
                    $trimmedFrom = "Bab {$chapter->position} \"{$chapter->title}\" ({$left} bab tidak ikut terkirim)";
                    break;
                }

                $out .= $piece;
                $spent += $length;
                $included++;
                $heading = '';
            }
        }

        return [
            'text' => $included > 0 || $trimmedFrom !== null ? $out : '',
            'included' => $included,
            'total' => $total,
            'trimmedFrom' => $trimmedFrom,
        ];
    }

    private static function chapterCount(Novel $novel): int
    {
        return $novel->chaptersCount();
    }

    /*
    |--------------------------------------------------------------------------
    | Formatting
    |--------------------------------------------------------------------------
    */

    /**
     * "Label: value" lines, skipping anything empty. Blank fields are dropped
     * rather than written out as "Iklim: —": an absent line costs nothing,
     * while hundreds of empty ones would be paid for on every request.
     *
     * @param  array<string, mixed>  $fields
     */
    private static function fields(array $fields): string
    {
        $out = '';

        foreach ($fields as $label => $value) {
            if (blank($value) || blank($label)) {
                continue;
            }

            $value = trim((string) $value);
            // Multi-line prose gets its own block so the label does not run
            // into a paragraph and read as part of it.
            $out .= str_contains($value, "\n")
                ? "{$label}:\n{$value}\n"
                : "{$label}: {$value}\n";
        }

        return $out;
    }

    /** " (Dunia: X)" — omitted when the novel has only one world. */
    private static function inWorld(object $record, Collection $worlds): string
    {
        if ($worlds->count() < 2) {
            return '';
        }

        $name = $worlds->firstWhere('id', $record->world_id)?->name;

        return $name ? " (Dunia: {$name})" : '';
    }

    private static function placeLabel(mixed $place): ?string
    {
        if ($place === null) {
            return null;
        }

        return $place->name . ' (' . $place->tierLabel() . ')';
    }
}
