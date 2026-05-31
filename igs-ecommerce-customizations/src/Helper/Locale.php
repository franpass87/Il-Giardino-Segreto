<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

final class Locale
{
    public static function isIt(): bool
    {
        // 1) Il prefisso URL è il segnale PIÙ affidabile per il routing a segmento di
        //    FP-Multilanguage: ogni lingua target ha il prefisso (/en/, /de/, ...), mentre
        //    l'italiano è la lingua di default senza prefisso. L'API fpml_get_language()
        //    può non essere ancora allineata al momento del render (e su questo sito
        //    determine_locale() non distingue IT/EN), quindi l'URL ha la precedenza.
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path !== '' && preg_match('#^/(en|de|fr|es|pt|nl|pl|ru|zh|ja|ar)(/|$)#i', $path)) {
            return false;
        }

        // 2) API FP-Multilanguage: se indica una lingua esplicita, rispettala.
        $langInstance = null;
        if (function_exists('fpml_get_language')) {
            $langInstance = fpml_get_language();
        } elseif (class_exists('\FPML_Language')) {
            $langInstance = \FPML_Language::instance();
        }
        if (is_object($langInstance) && method_exists($langInstance, 'get_current_language')) {
            $lang = strtolower((string) $langInstance->get_current_language());
            if ($lang !== '') {
                return $lang === 'it';
            }
        }

        // 3) Ultimo fallback: locale WordPress.
        $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
        return str_starts_with($loc, 'it_');
    }
}
