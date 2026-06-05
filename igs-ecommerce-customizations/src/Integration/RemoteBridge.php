<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Integration;

use IGS\Ecommerce\Helper\TrustBadges;

/**
 * Integrazione con FP Remote Bridge: rende i meta dei prodotti-tour scrivibili da
 * remoto (content-action `set_post_meta`) tramite gli hook generici del Bridge,
 * senza che il Bridge debba conoscere chiavi specifiche di questo sito.
 *
 * - `fp_remote_bridge_content_actions_meta_keys_for_post`: aggiunge le chiavi tour
 *   all'allowlist, solo per i post di tipo `product`.
 * - `fp_remote_bridge_content_actions_sanitize_meta_value`: sanitizza il valore di
 *   ogni chiave tour con le stesse regole delle metabox admin (date gg/mm/aaaa,
 *   tappe mappa con coordinate validate, livelli 1–5, programma/caratteristiche).
 *
 * Richiede FP Remote Bridge >= 1.169.0 (hook introdotti in quella versione). Se il
 * Bridge è assente o più vecchio, i filtri semplicemente non vengono chiamati.
 */
final class RemoteBridge
{
    /** Chiavi scalari di testo. */
    private const TEXT_KEYS = [
        '_paese_tour',
        '_protagonista_tour',
        '_mappa_paese',
        '_igs_sidebar_title_it',
        '_igs_sidebar_title_en',
        '_igs_installment_text_it',
        '_igs_installment_text_en',
    ];

    /** Livelli esperienza 1–5 (clamp). */
    private const LEVEL_KEYS = [
        '_livello_culturale',
        '_livello_passeggiata',
        '_livello_piuma',
        '_livello_comfort',
        '_livello_esclusivita',
    ];

    /** Campi HTML lunghi dell'itinerario (wp_kses_post). */
    private const HTML_KEYS = [
        '_igs_tour_cosa_portare',
        '_igs_tour_documenti',
        '_igs_tour_quota_comprende',
        '_igs_tour_quota_non_comprende',
        '_igs_tour_voli',
    ];

    /** Chiavi con struttura ad array dedicata. */
    private const ARRAY_KEYS = [
        '_date_ranges',
        '_mappa_tappe',
        '_igs_tour_services',
        '_igs_tour_programma',
        '_igs_tour_caratteristiche',
        '_igs_trust_badges',
    ];

    public function register(): void
    {
        add_filter('fp_remote_bridge_content_actions_meta_keys_for_post', [$this, 'allowKeys'], 10, 2);
        add_filter('fp_remote_bridge_content_actions_sanitize_meta_value', [$this, 'sanitize'], 10, 4);
    }

    /**
     * @return list<string>
     */
    private static function ownedKeys(): array
    {
        return array_merge(self::TEXT_KEYS, self::LEVEL_KEYS, self::HTML_KEYS, self::ARRAY_KEYS);
    }

    /**
     * Estende l'allowlist del Bridge con le chiavi tour, solo sui prodotti.
     *
     * @param mixed $keys
     * @return list<string>
     */
    public function allowKeys($keys, \WP_Post $post): array
    {
        $keys = is_array($keys) ? array_values($keys) : [];
        if ($post->post_type !== 'product') {
            return $keys;
        }

        return array_merge($keys, self::ownedKeys());
    }

    /**
     * Sanitizza il valore delle chiavi tour. Ritorna `$pre` invariato per le chiavi
     * non possedute (lascia proseguire la logica standard del Bridge).
     *
     * @param mixed $pre
     * @param mixed $value
     * @return mixed
     */
    public function sanitize($pre, string $key, $value, int $postId)
    {
        if (!in_array($key, self::ownedKeys(), true)) {
            return $pre;
        }

        if (in_array($key, self::TEXT_KEYS, true)) {
            return sanitize_text_field(is_scalar($value) ? (string) $value : '');
        }

        if (in_array($key, self::LEVEL_KEYS, true)) {
            $raw = is_scalar($value) ? trim((string) $value) : '';
            if ($raw === '') {
                return '';
            }
            $num = absint($raw);

            return (string) max(1, min(5, $num ?: 1));
        }

        if (in_array($key, self::HTML_KEYS, true)) {
            return wp_kses_post(is_scalar($value) ? (string) $value : '');
        }

        switch ($key) {
            case '_date_ranges':
                return $this->sanitizeDateRanges($value);
            case '_mappa_tappe':
                return $this->sanitizeMapStops($value);
            case '_igs_tour_services':
                return $this->sanitizeServices($value);
            case '_igs_tour_programma':
                return $this->sanitizeProgramma($value);
            case '_igs_tour_caratteristiche':
                return $this->sanitizeCaratteristiche($value);
            case '_igs_trust_badges':
                return $this->sanitizeTrustBadges($value);
        }

        return $pre;
    }

    /**
     * @param mixed $value
     * @return list<array{start:string,end:string}>
     */
    private function sanitizeDateRanges($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ranges = [];
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $start = $this->normalizeDate(isset($entry['start']) ? (string) $entry['start'] : '');
            $end = $this->normalizeDate(isset($entry['end']) ? (string) $entry['end'] : '');
            if ($start !== '' && $end !== '') {
                $ranges[] = ['start' => $start, 'end' => $end];
            }
        }

        return $ranges;
    }

    /**
     * @param mixed $value
     * @return list<array{nome:string,lat:string,lon:string,descrizione:string}>
     */
    private function sanitizeMapStops($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $stops = [];
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $nome = isset($entry['nome']) ? sanitize_text_field((string) $entry['nome']) : '';
            if ($nome === '') {
                continue;
            }
            $stops[] = [
                'nome' => $nome,
                'lat' => isset($entry['lat']) ? $this->sanitizeCoord($entry['lat'], -90.0, 90.0) : '',
                'lon' => isset($entry['lon']) ? $this->sanitizeCoord($entry['lon'], -180.0, 180.0) : '',
                'descrizione' => isset($entry['descrizione']) ? sanitize_textarea_field((string) $entry['descrizione']) : '',
            ];
        }

        return $stops;
    }

    /**
     * @param mixed $value
     * @return list<array{icon:string,it:string,en:string}>
     */
    private function sanitizeServices($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $services = [];
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $it = isset($entry['it']) ? sanitize_text_field((string) $entry['it']) : '';
            $en = isset($entry['en']) ? sanitize_text_field((string) $entry['en']) : '';
            if ($it === '' && $en === '') {
                continue;
            }
            $services[] = [
                'icon' => isset($entry['icon']) ? sanitize_text_field((string) $entry['icon']) : '',
                'it' => $it,
                'en' => $en,
            ];
        }

        return $services;
    }

    /**
     * @param mixed $value
     * @return list<array{num:int,titolo:string,contenuto:string}>
     */
    private function sanitizeProgramma($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        $i = 0;
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                ++$i;
                continue;
            }
            $titolo = isset($entry['titolo']) ? sanitize_text_field((string) $entry['titolo']) : '';
            $contenuto = isset($entry['contenuto']) ? wp_kses_post((string) $entry['contenuto']) : '';
            if ($titolo === '' && $contenuto === '') {
                ++$i;
                continue;
            }
            $num = isset($entry['num']) ? absint($entry['num']) : 0;
            $out[] = [
                'num' => $num ?: ($i + 1),
                'titolo' => $titolo,
                'contenuto' => $contenuto,
            ];
            ++$i;
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return list<array<string,mixed>>
     */
    private function sanitizeCaratteristiche($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $it = isset($entry['it']) ? sanitize_text_field((string) $entry['it']) : '';
            $en = isset($entry['en']) ? sanitize_text_field((string) $entry['en']) : '';
            if ($it === '' && $en === '') {
                continue;
            }
            $out[] = [
                'it' => $it,
                'en' => $en,
                'subtitle_it' => isset($entry['subtitle_it']) ? sanitize_text_field((string) $entry['subtitle_it']) : '',
                'subtitle_en' => isset($entry['subtitle_en']) ? sanitize_text_field((string) $entry['subtitle_en']) : '',
                'icon' => isset($entry['icon']) ? sanitize_text_field((string) $entry['icon']) : '',
                'icon_image' => isset($entry['icon_image']) ? absint($entry['icon_image']) : 0,
                'rating' => isset($entry['rating']) ? max(0, min(5, (int) $entry['rating'])) : 0,
            ];
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function sanitizeTrustBadges($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $valid = array_keys(TrustBadges::getAll());
        $selected = array_intersect(array_map('sanitize_key', array_map('strval', $value)), $valid);

        return array_values($selected);
    }

    /**
     * Coordinata geografica come stringa, vincolata al range; '' se non valida.
     *
     * @param mixed $val
     */
    private function sanitizeCoord($val, float $min, float $max): string
    {
        $v = is_numeric($val) ? (float) $val : 0.0;
        $v = max($min, min($max, $v));

        return $v !== 0.0 || $val === '0' || $val === 0 ? (string) round($v, 6) : '';
    }

    /**
     * Normalizza una data in `gg/mm/aaaa`; '' se non interpretabile.
     */
    private function normalizeDate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $raw)) {
            $d = \DateTime::createFromFormat('d/m/Y', $raw);

            return $d ? $d->format('d/m/Y') : '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $d = \DateTime::createFromFormat('Y-m-d', $raw);

            return $d ? $d->format('d/m/Y') : '';
        }
        try {
            $d = new \DateTime($raw);

            return $d->format('d/m/Y');
        } catch (\Exception) {
            return '';
        }
    }
}
