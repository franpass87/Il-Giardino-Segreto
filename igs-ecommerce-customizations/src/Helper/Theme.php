<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

/**
 * Accesso ai colori del tema Salient (redux), così le personalizzazioni seguono
 * l'accent del tema invece di colori hardcoded.
 */
final class Theme
{
    private const FALLBACK_ACCENT = '#0b5764';

    /** Colore accent di Salient (es. #0b5764). Fallback al teal del brand. */
    public static function accent(): string
    {
        $redux = get_option('salient_redux');
        $c = is_array($redux) && !empty($redux['accent-color']) ? trim((string) $redux['accent-color']) : '';
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $c)) {
            return str_starts_with($c, '#') ? $c : '#' . $c;
        }

        return self::FALLBACK_ACCENT;
    }

    /** Accent come "r,g,b" (per rgba(...) nelle ombre). */
    public static function accentRgb(): string
    {
        $h = ltrim(self::accent(), '#');

        return hexdec(substr($h, 0, 2)) . ',' . hexdec(substr($h, 2, 2)) . ',' . hexdec(substr($h, 4, 2));
    }

    /** Variante più scura dell'accent (per hover), fattore 0..1. */
    public static function darken(?string $hex = null, float $factor = 0.82): string
    {
        $h = ltrim($hex ?? self::accent(), '#');
        if (strlen($h) !== 6) {
            return self::FALLBACK_ACCENT;
        }
        $r = (int) round(hexdec(substr($h, 0, 2)) * $factor);
        $g = (int) round(hexdec(substr($h, 2, 2)) * $factor);
        $b = (int) round(hexdec(substr($h, 4, 2)) * $factor);

        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}
