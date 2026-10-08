<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

final class CountryFlags
{
    /** @var array<string, string> nome paese (lowercase) => flag emoji */
    private const MAP = [
        'italia' => '🇮🇹',
        'italy' => '🇮🇹',
        'portogallo' => '🇵🇹',
        'portugal' => '🇵🇹',
        'irlanda' => '🇮🇪',
        'ireland' => '🇮🇪',
        'francia' => '🇫🇷',
        'france' => '🇫🇷',
        'spagna' => '🇪🇸',
        'spain' => '🇪🇸',
        'regno unito' => '🇬🇧',
        'united kingdom' => '🇬🇧',
        'uk' => '🇬🇧',
        'inghilterra' => '🇬🇧',
        'england' => '🇬🇧',
        'germania' => '🇩🇪',
        'germany' => '🇩🇪',
        'grecia' => '🇬🇷',
        'greece' => '🇬🇷',
        'paesi bassi' => '🇳🇱',
        'netherlands' => '🇳🇱',
        'olanda' => '🇳🇱',
        'svizzera' => '🇨🇭',
        'switzerland' => '🇨🇭',
        'austria' => '🇦🇹',
        'turchia' => '🇹🇷',
        'turkey' => '🇹🇷',
        'croazia' => '🇭🇷',
        'croatia' => '🇭🇷',
        'slovenia' => '🇸🇮',
        'malta' => '🇲🇹',
        'azzorre' => '🇵🇹',
        'azores' => '🇵🇹',
        'scozia' => '🇬🇧',
        'scotland' => '🇬🇧',
        'belgio' => '🇧🇪',
        'belgium' => '🇧🇪',
        'polonia' => '🇵🇱',
        'poland' => '🇵🇱',
        'repubblica ceca' => '🇨🇿',
        'czech republic' => '🇨🇿',
        'cekia' => '🇨🇿',
        'czechia' => '🇨🇿',
        'ungheria' => '🇭🇺',
        'hungary' => '🇭🇺',
        'romania' => '🇷🇴',
        'romenia' => '🇷🇴',
        'bulgaria' => '🇧🇬',
        'serbia' => '🇷🇸',
        'bosnia' => '🇧🇦',
        'bosnia ed erzegovina' => '🇧🇦',
        'bosnia and herzegovina' => '🇧🇦',
        'montenegro' => '🇲🇪',
        'albania' => '🇦🇱',
        'macedonia' => '🇲🇰',
        'north macedonia' => '🇲🇰',
        'macedonia del nord' => '🇲🇰',
        'ucraina' => '🇺🇦',
        'ukraine' => '🇺🇦',
        'russia' => '🇷🇺',
        'norvegia' => '🇳🇴',
        'norway' => '🇳🇴',
        'svezia' => '🇸🇪',
        'sweden' => '🇸🇪',
        'danimarca' => '🇩🇰',
        'denmark' => '🇩🇰',
        'finlandia' => '🇫🇮',
        'finland' => '🇫🇮',
        'islanda' => '🇮🇸',
        'iceland' => '🇮🇸',
        'estonia' => '🇪🇪',
        'lettonia' => '🇱🇻',
        'latvia' => '🇱🇻',
        'lituania' => '🇱🇹',
        'lithuania' => '🇱🇹',
        'andorra' => '🇦🇩',
        'monaco' => '🇲🇨',
        'san marino' => '🇸🇲',
        'vaticano' => '🇻🇦',
        'città del vaticano' => '🇻🇦',
        'lisbona' => '🇵🇹',
        'lisbon' => '🇵🇹',
        'madeira' => '🇵🇹',
        'madera' => '🇵🇹',
        'sardegna' => '🇮🇹',
        'sardinia' => '🇮🇹',
        'sicilia' => '🇮🇹',
        'sicily' => '🇮🇹',
        'toscana' => '🇮🇹',
        'tuscany' => '🇮🇹',
        'liguria' => '🇮🇹',
        'provence' => '🇫🇷',
        'provenza' => '🇫🇷',
        'loira' => '🇫🇷',
        'loire' => '🇫🇷',
        'normandia' => '🇫🇷',
        'normandy' => '🇫🇷',
        'catalogna' => '🇪🇸',
        'catalonia' => '🇪🇸',
        'andalusia' => '🇪🇸',
        'israele' => '🇮🇱',
        'israel' => '🇮🇱',
        'emirati arabi uniti' => '🇦🇪',
        'uae' => '🇦🇪',
        'emirates' => '🇦🇪',
        'marocco' => '🇲🇦',
        'morocco' => '🇲🇦',
        'egitto' => '🇪🇬',
        'egypt' => '🇪🇬',
        'tunisia' => '🇹🇳',
        'giappone' => '🇯🇵',
        'japan' => '🇯🇵',
        'cina' => '🇨🇳',
        'china' => '🇨🇳',
        'india' => '🇮🇳',
        'thailandia' => '🇹🇭',
        'thailand' => '🇹🇭',
        'vietnam' => '🇻🇳',
        'indonesia' => '🇮🇩',
        'bali' => '🇮🇩',
        'sri lanka' => '🇱🇰',
        'australia' => '🇦🇺',
        'nuova zelanda' => '🇳🇿',
        'new zealand' => '🇳🇿',
        'stati uniti' => '🇺🇸',
        'usa' => '🇺🇸',
        'stati uniti d\'america' => '🇺🇸',
        'united states' => '🇺🇸',
        'canada' => '🇨🇦',
        'messico' => '🇲🇽',
        'mexico' => '🇲🇽',
        'brasile' => '🇧🇷',
        'brazil' => '🇧🇷',
        'argentina' => '🇦🇷',
        'perù' => '🇵🇪',
        'peru' => '🇵🇪',
        'cile' => '🇨🇱',
        'chile' => '🇨🇱',
        'colombia' => '🇨🇴',
        'sudafrica' => '🇿🇦',
        'south africa' => '🇿🇦',
        'kenya' => '🇰🇪',
        'tanzania' => '🇹🇿',
    ];

    public static function forName(string $name): ?string
    {
        $key = mb_strtolower(trim($name));
        if ($key === '') {
            return null;
        }
        return self::MAP[$key] ?? null;
    }

    public static function withFlag(string $name): string
    {
        return $name;
    }

    /**
     * HTML "bandiera (SVG) + nome", pronto per l'output (stringa già sicura).
     * Usa immagini SVG da flagcdn.com così le bandiere si vedono su TUTTI i sistemi
     * (le flag-emoji non vengono disegnate su Windows). L'ISO del paese è ricavato
     * dai codepoint dell'emoji già mappata.
     */
    public static function withFlagHtml(string $name): string
    {
        $img = self::flagImg($name);
        $safe = esc_html($name);
        if ($img === '') {
            return $safe;
        }
        // Contenitore inline-flex centrato: bandiera e nome restano allineati
        // verticalmente come unità, indipendentemente dalle metriche del font
        // (la sola vertical-align:middle sull'img la lasciava leggermente bassa).
        $wrapStyle = 'display:inline-flex;align-items:center;gap:.4em;vertical-align:middle;line-height:1';
        return '<span class="igs-country" style="' . esc_attr($wrapStyle) . '">' . $img . '<span class="igs-country-name">' . $safe . '</span></span>';
    }

    private static function flagImg(string $name): string
    {
        $emoji = self::forName($name);
        if ($emoji === null) {
            return '';
        }
        $iso = self::emojiToIso2($emoji);
        if ($iso === '') {
            return '';
        }
        $url = 'https://flagcdn.com/' . $iso . '.svg';
        // Dimensione forzata via attributo style inline: vince sul tema (es. `li.product img { width:100% }`)
        // anche quando il CSS inline del plugin non viene caricato (carosello Nectar flickity senza handle woocommerce-general),
        // evitando che la bandiera SVG (es. Spagna) si gonfi a tutta larghezza nella card.
        // Riquadro FISSO (non height:auto): le bandiere hanno proporzioni diverse
        // — Italia 3:2, Belgio 15:13, Svizzera 1:1 — e con l'altezza libera ognuna
        // risultava alta diversamente, sfalsando la pastiglia da una card all'altra.
        // object-fit:cover riempie il riquadro senza deformare il disegno.
        $style = 'width:1.5em;height:1.05em;max-width:none;display:inline-block;vertical-align:middle;'
            . 'object-fit:cover;border-radius:2px;box-shadow:0 0 0 1px rgba(0,0,0,.18)';
        return '<img class="igs-flag" src="' . esc_url($url) . '" alt="" width="22" height="16" style="' . esc_attr($style) . '" loading="lazy" decoding="async">';
    }

    private static function emojiToIso2(string $emoji): string
    {
        $iso = '';
        $len = mb_strlen($emoji, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $cp = mb_ord(mb_substr($emoji, $i, 1, 'UTF-8'), 'UTF-8');
            if ($cp !== false && $cp >= 0x1F1E6 && $cp <= 0x1F1FF) {
                $iso .= chr(ord('A') + ($cp - 0x1F1E6));
            }
        }
        return strtolower($iso);
    }
}
