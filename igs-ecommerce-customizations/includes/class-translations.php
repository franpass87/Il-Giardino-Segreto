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
     * @var array<string,array<string,string>>
     */
    private static array $cache = [];

    /**
     * Bootstrap the translation filters.
     */
    public static function init(): void {
        add_filter( 'gettext', [ __CLASS__, 'filter_gettext' ], 5, 3 );
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

        if ( ! isset( self::$cache[ $locale ] ) ) {
            self::$cache[ $locale ] = self::load_translations( $locale );
        }

        if ( isset( self::$cache[ $locale ][ $text ] ) ) {
            return self::$cache[ $locale ][ $text ];
        }

        return $translation;
    }

    /**
     * Parse the locale specific PO file.
     *
     * @return array<string,string>
     */
    private static function load_translations( string $locale ): array {
        $file = Helpers\path( 'languages/igs-ecommerce-' . $locale . '.po' );

        if ( ! is_readable( $file ) ) {
            return [];
        }

        $handle = fopen( $file, 'rb' );

        if ( ! $handle ) {
            return [];
        }

        $entries = [];
        $msgid   = null;
        $msgstr  = '';
        $state   = null;

        while ( false !== ( $line = fgets( $handle ) ) ) {
            $line = rtrim( $line, "\r\n" );

            if ( '' === $line ) {
                if ( null !== $msgid && '' !== $msgid ) {
                    $entries[ $msgid ] = $msgstr;
                }

                $msgid  = null;
                $msgstr = '';
                $state  = null;
                continue;
            }

            if ( isset( $line[0] ) && '#' === $line[0] ) {
                continue;
            }

            if ( 0 === strpos( $line, 'msgid ' ) ) {
                if ( null !== $msgid && '' !== $msgid ) {
                    $entries[ $msgid ] = $msgstr;
                }

                $msgid  = self::parse_po_string( substr( $line, 6 ) );
                $msgstr = '';
                $state  = 'msgid';
                continue;
            }

            if ( 0 === strpos( $line, 'msgstr ' ) ) {
                $msgstr = self::parse_po_string( substr( $line, 7 ) );
                $state  = 'msgstr';
                continue;
            }

            if ( isset( $line[0] ) && '"' === $line[0] && $state ) {
                if ( 'msgid' === $state && null !== $msgid ) {
                    $msgid .= self::parse_po_string( $line );
                } elseif ( 'msgstr' === $state ) {
                    $msgstr .= self::parse_po_string( $line );
                }
            }
        }

        fclose( $handle );

        if ( null !== $msgid && '' !== $msgid ) {
            $entries[ $msgid ] = $msgstr;
        }

        return $entries;
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
}
