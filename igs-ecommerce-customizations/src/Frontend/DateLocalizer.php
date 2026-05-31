<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;

/**
 * Localizza in italiano le date dei post sulle pagine in lingua italiana.
 *
 * Su questo sito il locale a runtime non applica i nomi dei mesi in italiano
 * (date renderizzate dal tema come "May 7, 2026"). Questo filtro converte i
 * nomi di mese inglesi in italiano direttamente sulla stringa, senza dipendere
 * dal caricamento del file di lingua, e riordina il formato "Mese G, AAAA" nella
 * forma italiana "G mese AAAA". Agisce solo se la stringa contiene un mese
 * inglese, quindi è innocuo sugli orari.
 */
class DateLocalizer
{
    /** @var array<string, string> */
    private const MONTHS = [
        'January' => 'gennaio',
        'February' => 'febbraio',
        'March' => 'marzo',
        'April' => 'aprile',
        'May' => 'maggio',
        'June' => 'giugno',
        'July' => 'luglio',
        'August' => 'agosto',
        'September' => 'settembre',
        'October' => 'ottobre',
        'November' => 'novembre',
        'December' => 'dicembre',
    ];

    public function register(): void
    {
        add_filter('get_the_date', [$this, 'localize'], 20, 1);
        add_filter('get_the_time', [$this, 'localize'], 20, 1);
        add_filter('the_date', [$this, 'localize'], 20, 1);
        add_filter('the_time', [$this, 'localize'], 20, 1);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function localize($value)
    {
        if (is_admin() || !is_string($value) || $value === '') {
            return $value;
        }
        if (!Locale::isIt()) {
            return $value;
        }

        $monthsPattern = implode('|', array_keys(self::MONTHS));

        // Forma "Mese G, AAAA" o "Mese G AAAA" -> "G mese AAAA" (convenzione italiana).
        $reordered = preg_replace_callback(
            '/\b(' . $monthsPattern . ')\b\s+(\d{1,2})(?:,)?\s+(\d{4})/',
            static function (array $m): string {
                return $m[2] . ' ' . self::MONTHS[$m[1]] . ' ' . $m[3];
            },
            $value
        );

        if (is_string($reordered) && $reordered !== $value) {
            return $reordered;
        }

        // Altri formati: sostituisci comunque il nome del mese inglese con l'italiano.
        return preg_replace_callback(
            '/\b(' . $monthsPattern . ')\b/',
            static function (array $m): string {
                return self::MONTHS[$m[1]];
            },
            $value
        ) ?? $value;
    }
}
