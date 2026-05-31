<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

final class Locale
{
    public static function isIt(): bool
    {
        // FP-Multilanguage: la lingua corrente dall'API del plugin è il segnale affidabile
        // (determine_locale() restituisce il locale base del sito anche su pagine IT/EN).
        $langInstance = null;
        if (function_exists('fpml_get_language')) {
            $langInstance = fpml_get_language();
        } elseif (class_exists('\FPML_Language')) {
            $langInstance = \FPML_Language::instance();
        }
        if (is_object($langInstance) && method_exists($langInstance, 'get_current_language')) {
            $lang = (string) $langInstance->get_current_language();
            if ($lang !== '') {
                return strtolower($lang) === 'it';
            }
        }

        // Fallback: prefisso URL (IT è la lingua di default senza prefisso).
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path !== '' && preg_match('#^/(en|de|fr|es|pt|ru|zh|ja)(/|$)#i', $path)) {
            return false;
        }

        // Ultimo fallback: locale WordPress.
        $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
        return str_starts_with($loc, 'it_');
    }
}
