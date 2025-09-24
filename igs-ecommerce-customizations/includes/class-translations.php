<?php
/**
 * Lightweight runtime translation loader based on PO files.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide a PO-based fallback for environments that cannot ship MO binaries.
 */
class Translations {
    /**
     * Cached translations grouped by locale.
     *
     * @var array<string,array{
     *     default: array<string,string>,
     *     contextual: array<string,array<string,string>>,
     *     plural: array<string,array<int,string>>,
     *     contextual_plural: array<string,array<string,array<int,string>>>,
     *     nplurals: int,
     *     plural_rule: string,
     * }
     */
    private static array $cache = [];

    /**
     * Bootstrap the translation filters.
     */
    public static function init(): void {
        add_filter( 'gettext', [ __CLASS__, 'filter_gettext' ], 5, 3 );
        add_filter( 'gettext_with_context', [ __CLASS__, 'filter_gettext_with_context' ], 5, 4 );
        add_filter( 'ngettext', [ __CLASS__, 'filter_ngettext' ], 5, 5 );
        add_filter( 'ngettext_with_context', [ __CLASS__, 'filter_ngettext_with_context' ], 5, 6 );
    }

    /**
     * Replace untranslated strings using the PO catalogue.
     */
    public static function filter_gettext( string $translation, string $text, string $domain ): string {
        if ( 'igs-ecommerce' !== $domain ) {
            return $translation;
        }

        if ( $translation !== $text ) {
            return $translation;
        }

        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

        $catalogue = self::get_catalogue( $locale );

        if ( isset( $catalogue['default'][ $text ] ) && '' !== $catalogue['default'][ $text ] ) {
            return $catalogue['default'][ $text ];
        }

        return $translation;
    }

    /**
     * Replace contextual strings using the PO catalogue.
     */
    public static function filter_gettext_with_context( string $translation, string $text, string $context, string $domain ): string {
        if ( 'igs-ecommerce' !== $domain ) {
            return $translation;
        }

        if ( $translation !== $text ) {
            return $translation;
        }

        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

        $catalogue = self::get_catalogue( $locale );

        if ( isset( $catalogue['contextual'][ $context ][ $text ] ) && '' !== $catalogue['contextual'][ $context ][ $text ] ) {
            return $catalogue['contextual'][ $context ][ $text ];
        }

        return $translation;
    }

    /**
     * Replace pluralised strings using the PO catalogue.
     */
    public static function filter_ngettext( string $translation, string $single, string $plural, int $number, string $domain ): string {
        if ( 'igs-ecommerce' !== $domain ) {
            return $translation;
        }

        $expected = ( 1 === (int) $number ) ? $single : $plural;

        if ( $translation !== $expected ) {
            return $translation;
        }

        $locale    = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        $catalogue = self::get_catalogue( $locale );
        $result    = self::lookup_plural( $catalogue, $single, $number, null );

        return null !== $result ? $result : $translation;
    }

    /**
     * Replace contextual plural strings using the PO catalogue.
     */
    public static function filter_ngettext_with_context( string $translation, string $single, string $plural, int $number, string $context, string $domain ): string {
        if ( 'igs-ecommerce' !== $domain ) {
            return $translation;
        }

        $expected = ( 1 === (int) $number ) ? $single : $plural;

        if ( $translation !== $expected ) {
            return $translation;
        }

        $locale    = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        $catalogue = self::get_catalogue( $locale );
        $result    = self::lookup_plural( $catalogue, $single, $number, $context );

        return null !== $result ? $result : $translation;
    }

    /**
     * Retrieve the cached catalogue for the current locale.
     *
     * @return array{
     *     default: array<string,string>,
     *     contextual: array<string,array<string,string>>,
     *     plural: array<string,array<int,string>>,
     *     contextual_plural: array<string,array<string,array<int,string>>>,
     *     nplurals: int,
     *     plural_rule: string,
     * }
     */
    private static function get_catalogue( string $locale ): array {
        if ( ! isset( self::$cache[ $locale ] ) ) {
            self::$cache[ $locale ] = self::load_translations( $locale );
        }

        return self::$cache[ $locale ];
    }

    /**
     * Parse the locale specific PO file.
     *
     * @return array{
     *     default: array<string,string>,
     *     contextual: array<string,array<string,string>>,
     *     plural: array<string,array<int,string>>,
     *     contextual_plural: array<string,array<string,array<int,string>>>,
     *     nplurals: int,
     *     plural_rule: string,
     * }
     */
    private static function load_translations( string $locale ): array {
        $file = Helpers\path( 'languages/igs-ecommerce-' . $locale . '.po' );

        if ( ! is_readable( $file ) ) {
            return [
                'default'    => [],
                'contextual' => [],
                'plural'     => [],
                'contextual_plural' => [],
                'nplurals'   => 2,
                'plural_rule'=> 'n != 1',
            ];
        }

        $handle = fopen( $file, 'rb' );

        if ( ! $handle ) {
            return [
                'default'    => [],
                'contextual' => [],
                'plural'     => [],
                'contextual_plural' => [],
                'nplurals'   => 2,
                'plural_rule'=> 'n != 1',
            ];
        }

        $entries = [
            'default'    => [],
            'contextual' => [],
            'plural'     => [],
            'contextual_plural' => [],
            'nplurals'   => 2,
            'plural_rule'=> 'n != 1',
        ];
        $msgid         = null;
        $msgid_plural  = null;
        $msgstr        = '';
        $msgstr_plural = [];
        $state         = null;
        $context       = null;

        while ( false !== ( $line = fgets( $handle ) ) ) {
            $line = rtrim( $line, "\r\n" );

            if ( '' === $line ) {
                if ( null !== $msgid ) {
                    self::store_entry( $entries, $msgid, $msgstr, $context, $msgid_plural, $msgstr_plural );
                }

                $msgid         = null;
                $msgid_plural  = null;
                $msgstr        = '';
                $msgstr_plural = [];
                $state         = null;
                $context       = null;
                continue;
            }

            if ( isset( $line[0] ) && '#' === $line[0] ) {
                continue;
            }

            if ( 0 === strpos( $line, 'msgctxt ' ) ) {
                $context = self::parse_po_string( substr( $line, 8 ) );
                $state   = 'msgctxt';
                continue;
            }

            if ( 0 === strpos( $line, 'msgid ' ) ) {
                $msgid  = self::parse_po_string( substr( $line, 6 ) );
                $msgstr = '';
                $msgid_plural  = null;
                $msgstr_plural = [];
                $state  = 'msgid';
                continue;
            }

            if ( 0 === strpos( $line, 'msgid_plural ' ) ) {
                $msgid_plural = self::parse_po_string( substr( $line, 13 ) );
                $state        = 'msgid_plural';
                continue;
            }

            if ( 0 === strpos( $line, 'msgstr ' ) ) {
                $msgstr = self::parse_po_string( substr( $line, 7 ) );
                $state  = 'msgstr';
                continue;
            }

            if ( preg_match( '/^msgstr\[(\d+)\]\s+(.+)$/', $line, $matches ) ) {
                $index               = (int) $matches[1];
                $msgstr_plural[ $index ] = self::parse_po_string( $matches[2] );
                $state               = 'msgstr[' . $index . ']';
                continue;
            }

            if ( isset( $line[0] ) && '"' === $line[0] && $state ) {
                if ( 'msgctxt' === $state && null !== $context ) {
                    $context .= self::parse_po_string( $line );
                } elseif ( 'msgid' === $state && null !== $msgid ) {
                    $msgid .= self::parse_po_string( $line );
                } elseif ( 'msgstr' === $state ) {
                    $msgstr .= self::parse_po_string( $line );
                } elseif ( 0 === strpos( $state, 'msgstr[' ) ) {
                    $index = (int) filter_var( $state, FILTER_SANITIZE_NUMBER_INT );
                    $msgstr_plural[ $index ] = ( $msgstr_plural[ $index ] ?? '' ) . self::parse_po_string( $line );
                }
            }
        }

        fclose( $handle );

        if ( null !== $msgid ) {
            self::store_entry( $entries, $msgid, $msgstr, $context, $msgid_plural, $msgstr_plural );
        }

        // Normalise contextual plural entries into the expected structure.
        if ( isset( $entries['contextual_plural'] ) ) {
            foreach ( $entries['contextual_plural'] as $ctx => $values ) {
                if ( ! is_array( $values ) ) {
                    unset( $entries['contextual_plural'][ $ctx ] );
                }
            }
        }

        return $entries;
    }

    /**
     * Store a parsed PO entry into the catalogue buckets.
     *
     * @param array{
     *     default: array<string,string>,
     *     contextual: array<string,array<string,string>>,
     *     plural: array<string,array<int,string>>,
     *     contextual_plural: array<string,array<string,array<int,string>>>,
     *     nplurals: int,
     *     plural_rule: string,
     * } $entries Entries catalogue.
     */
    private static function store_entry( array &$entries, ?string $msgid, string $msgstr, ?string $context, ?string $msgid_plural, array $msgstr_plural ): void {
        if ( null === $msgid ) {
            return;
        }

        if ( '' === $msgid && ( null === $context || '' === $context ) ) {
            self::parse_headers( $entries, $msgstr );
            return;
        }

        if ( null !== $msgid_plural && ! empty( $msgstr_plural ) ) {
            ksort( $msgstr_plural );

            if ( null !== $context && '' !== $context ) {
                if ( ! isset( $entries['contextual_plural'][ $context ] ) ) {
                    $entries['contextual_plural'][ $context ] = [];
                }

                $entries['contextual_plural'][ $context ][ $msgid ] = $msgstr_plural;
                return;
            }

            $entries['plural'][ $msgid ] = $msgstr_plural;
            return;
        }

        if ( null !== $context && '' !== $context ) {
            if ( ! isset( $entries['contextual'][ $context ] ) ) {
                $entries['contextual'][ $context ] = [];
            }

            $entries['contextual'][ $context ][ $msgid ] = $msgstr;
            return;
        }

        $entries['default'][ $msgid ] = $msgstr;
    }

    /**
     * Decode a PO string literal.
     */
    private static function parse_po_string( string $value ): string {
        $value = trim( $value );

        if ( 0 === strpos( $value, '"' ) && '"' === substr( $value, -1 ) ) {
            $value = substr( $value, 1, -1 );
        }

        return stripcslashes( $value );
    }

    /**
     * Parse the header block for plural information.
     */
    private static function parse_headers( array &$entries, string $headers ): void {
        foreach ( preg_split( '/\n/', $headers ) as $header ) {
            if ( false === strpos( $header, ':' ) ) {
                continue;
            }

            [ $key, $value ] = array_map( 'trim', explode( ':', $header, 2 ) );

            if ( 'Plural-Forms' !== $key ) {
                continue;
            }

            if ( preg_match( '/nplurals\s*=\s*(\d+)/', $value, $matches ) ) {
                $entries['nplurals'] = max( 1, (int) $matches[1] );
            }

            if ( preg_match( '/plural\s*=\s*([^;]+)/', $value, $matches ) ) {
                $entries['plural_rule'] = trim( $matches[1] );
            }
        }
    }

    /**
     * Look up the correct plural form from the catalogue.
     */
    private static function lookup_plural( array $catalogue, string $msgid, int $number, ?string $context ): ?string {
        if ( null !== $context && '' !== $context ) {
            $forms = $catalogue['contextual_plural'][ $context ][ $msgid ] ?? null;
        } else {
            $forms = $catalogue['plural'][ $msgid ] ?? null;
        }

        if ( ! is_array( $forms ) ) {
            return null;
        }

        $index = self::determine_plural_index( $catalogue, $number );

        if ( isset( $forms[ $index ] ) && '' !== $forms[ $index ] ) {
            return $forms[ $index ];
        }

        foreach ( $forms as $form ) {
            if ( '' !== $form ) {
                return $form;
            }
        }

        return null;
    }

    /**
     * Determine the plural index for a given quantity.
     */
    private static function determine_plural_index( array $catalogue, int $number ): int {
        $nplurals = isset( $catalogue['nplurals'] ) ? max( 1, (int) $catalogue['nplurals'] ) : 2;
        $rule     = $catalogue['plural_rule'] ?? 'n != 1';

        $rule = trim( $rule );

        if ( '' === $rule ) {
            return min( $nplurals - 1, ( 1 === $number ) ? 0 : 1 );
        }

        // Allow only safe characters before evaluating.
        if ( preg_match( '/[^n0-9\s\(\)\?\:\|\&\!\<\>\=\+\-\*\/\%]/', $rule ) ) {
            return min( $nplurals - 1, ( 1 === $number ) ? 0 : 1 );
        }

        $n      = (int) $number;
        $result = 0;

        try {
            /** @psalm-suppress UnresolvableInclude */
            $result = eval( 'return (int) (' . str_replace( 'n', '$n', $rule ) . ');' );
        } catch ( \Throwable $e ) {
            $result = ( 1 === $number ) ? 0 : 1;
        }

        if ( ! is_int( $result ) ) {
            $result = (int) $result;
        }

        if ( $result < 0 ) {
            $result = 0;
        }

        if ( $result >= $nplurals ) {
            $result = $nplurals - 1;
        }

        return $result;
    }
}
