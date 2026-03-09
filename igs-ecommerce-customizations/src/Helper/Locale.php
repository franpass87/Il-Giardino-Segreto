<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Helper;

final class Locale
{
    public static function isIt(): bool
    {
        $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
        return str_starts_with($loc, 'it_');
    }
}
