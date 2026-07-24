<?php

namespace App\Support;

use App\Models\Novel;
use App\Models\World;
use Illuminate\Http\Request;

/**
 * Per-novel visual themes. A novel picks one, and every page belonging to it —
 * the novel itself, its worlds, locations, characters, galleries, attributes —
 * renders in that skin.
 *
 * The actual colours live in resources/css/app.css as CSS custom properties
 * under `:root[data-theme="<key>"]`; this class holds the metadata around them:
 * the label shown to the author, which web fonts to load, and the swatches used
 * in the picker. Keys here MUST match the selectors there.
 */
class NovelTheme
{
    public const DEFAULT = 'normal';

    /**
     * @var array<string, array{
     *   label: string, blurb: string, fonts: string,
     *   display: string, body: string, swatches: list<string>
     * }>
     */
    public const THEMES = [
        'normal' => [
            'label' => 'Normal',
            'blurb' => 'Netral dan tenang. Cocok untuk cerita apa pun — fiksi kontemporer, romansa, misteri.',
            'fonts' => 'Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600',
            'display' => 'Inter',
            'body' => 'Inter',
            'swatches' => ['#4f46e5', '#818cf8', '#111827', '#f4f5f7'],
        ],
        'fantasy' => [
            'label' => 'Fantasi',
            'blurb' => 'Perkamen, emas tua, dan huruf berukir. Untuk kerajaan, sihir, dan zaman pedang.',
            'fonts' => 'Cinzel:wght@500;600;700&family=EB+Garamond:wght@400;500;600',
            'display' => 'Cinzel',
            'body' => 'EB Garamond',
            'swatches' => ['#a16207', '#d4a437', '#2f2519', '#f7f1e3'],
        ],
        'scifi' => [
            'label' => 'Fiksi Ilmiah',
            'blurb' => 'Baja dingin dan cyan menyala, dengan huruf geometris. Untuk luar angkasa dan masa depan.',
            'fonts' => 'Orbitron:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700',
            'display' => 'Orbitron',
            'body' => 'Space Grotesk',
            'swatches' => ['#0891b2', '#22d3ee', '#0f1b26', '#eef4f8'],
        ],
        'military' => [
            'label' => 'Militer',
            'blurb' => 'Zaitun, kanvas lapangan, dan huruf tegak memadat. Untuk perang, taktik, dan ketentaraan.',
            'fonts' => 'Oswald:wght@500;600;700&family=Barlow:wght@400;500;600;700',
            'display' => 'Oswald',
            'body' => 'Barlow',
            'swatches' => ['#4d7c0f', '#84cc16', '#1c1f16', '#f1f2ea'],
        ],
        'horror' => [
            'label' => 'Horor',
            'blurb' => 'Satu-satunya tema gelap: latar nyaris hitam, merah darah, dan serif tinggi yang dingin.',
            'fonts' => 'Cormorant+Garamond:wght@500;600;700&family=Crimson+Pro:wght@400;500;600',
            'display' => 'Cormorant Garamond',
            'body' => 'Crimson Pro',
            'swatches' => ['#b91c1c', '#f87171', '#e8e0dd', '#0f0c0c'],
        ],
        'romance' => [
            'label' => 'Romantis',
            'blurb' => 'Merah muda lembut, kertas hangat, dan serif anggun. Untuk kisah cinta dan drama.',
            'fonts' => 'Playfair+Display:wght@500;600;700&family=Lora:wght@400;500;600',
            'display' => 'Playfair Display',
            'body' => 'Lora',
            'swatches' => ['#be185d', '#f472b6', '#3d2530', '#fdf2f5'],
        ],
    ];

    /** Themes that invert the page — light text on a dark ground. */
    public const DARK = ['horror'];

    public static function isDark(?string $key): bool
    {
        return in_array(self::key($key), self::DARK, true);
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::THEMES);
    }

    public static function isTheme(?string $key): bool
    {
        return $key !== null && isset(self::THEMES[$key]);
    }

    public static function key(?string $key): string
    {
        return self::isTheme($key) ? $key : self::DEFAULT;
    }

    /** @return array{label:string, blurb:string, fonts:string, display:string, body:string, swatches:list<string>} */
    public static function get(?string $key): array
    {
        return self::THEMES[self::key($key)];
    }

    public static function label(?string $key): string
    {
        return self::get($key)['label'];
    }

    /** The Google Fonts stylesheet for a theme — only the active one is loaded. */
    public static function fontUrl(?string $key): string
    {
        return 'https://fonts.googleapis.com/css2?family=' . self::get($key)['fonts'] . '&display=swap';
    }

    /**
     * Which theme the current page should render in, worked out from the route's
     * bound models: every lore route carries either {novel} or {world}, and a
     * world inherits its novel's theme. Anything else falls back to the default.
     */
    public static function forRequest(Request $request): string
    {
        $route = $request->route();

        if (! $route) {
            return self::DEFAULT;
        }

        $novel = $route->parameter('novel');

        if (! $novel instanceof Novel) {
            $world = $route->parameter('world');
            $novel = $world instanceof World ? $world->novel : null;
        }

        return self::key($novel?->theme);
    }
}
