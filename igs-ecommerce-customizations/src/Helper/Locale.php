<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

final class Locale
{
    public static function isIt(): bool
    {
        // Sotto WPML la lingua corrente reale è più affidabile di determine_locale().
        if (has_filter('wpml_current_language')) {
            $lang = apply_filters('wpml_current_language', null);
            if (is_string($lang) && $lang !== '') {
                return strtolower($lang) === 'it';
            }
        }

        $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
        return str_starts_with($loc, 'it_');
    }
}
