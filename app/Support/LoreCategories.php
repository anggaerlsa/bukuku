<?php

namespace App\Support;

use App\Models\LoreEntry;
use App\Models\World;

/**
 * Lore categories are the author's own vocabulary, kept as free text on each
 * entry rather than an enum in code — a magic system belongs to a fantasy
 * world, a command chain to a military one, and neither should carry the
 * other's dead weight.
 *
 * What this class provides is only guidance: the categories already used in a
 * world, plus starter suggestions drawn from the novel's theme. Nothing here
 * constrains what an author may type.
 */
class LoreCategories
{
    /** @var array<string, list<string>> keyed by NovelTheme key */
    public const SUGGESTIONS = [
        'normal' => ['Sejarah', 'Budaya & Adat', 'Istilah', 'Ekonomi & Perdagangan', 'Hukum'],
        'fantasy' => ['Sistem Sihir', 'Panteon & Agama', 'Ras & Bangsa', 'Bestiari', 'Artefak', 'Glosarium'],
        'scifi' => ['Teknologi', 'Spesies', 'Koloni & Stasiun', 'Protokol & Hukum', 'Persenjataan', 'Glosarium'],
        'military' => ['Doktrin & Taktik', 'Persenjataan', 'Rantai Komando', 'Operasi & Kampanye', 'Logistik', 'Sandi & Istilah'],
        'horror' => ['Entitas', 'Kutukan & Ritual', 'Legenda Lokal', 'Aturan Bertahan Hidup', 'Glosarium'],
        'romance' => ['Adat & Etiket', 'Silsilah Keluarga', 'Tempat Kenangan', 'Surat & Dokumen', 'Istilah'],
    ];

    /** Starter categories fitting the theme of the world's novel. */
    public static function suggestionsFor(World $world): array
    {
        $theme = NovelTheme::key($world->novel?->theme);

        return self::SUGGESTIONS[$theme] ?? self::SUGGESTIONS[NovelTheme::DEFAULT];
    }

    /** Categories this world already uses, in the author's own words. */
    public static function usedIn(World $world): array
    {
        return LoreEntry::where('world_id', $world->id)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /**
     * What to offer in the picker: what is already used first, then the
     * theme's starters that are not in use yet.
     *
     * @return list<string>
     */
    public static function optionsFor(World $world): array
    {
        $used = self::usedIn($world);
        $extra = array_values(array_diff(self::suggestionsFor($world), $used));

        return array_values(array_merge($used, $extra));
    }
}
