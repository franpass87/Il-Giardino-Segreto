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
     * @var array<string,array{default: array<string,string>, contextual: array<string,array<string,string>>}>
     */
    private static array $cache = [];

    /**
     * Bootstrap the translation filters.
     */
    public static function init(): void {
        add_filter( 'gettext', [ __CLASS__, 'filter_gettext' ], 5, 3 );
        add_filter( 'gettext_with_context', [ __CLASS__, 'filter_gettext_with_context' ], 5, 4 );
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
     * Retrieve the cached catalogue for the current locale.
     *
     * @return array{default: array<string,string>, contextual: array<string,array<string,string>>}
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
     * @return array{default: array<string,string>, contextual: array<string,array<string,string>>}
     */
    private static function load_translations( string $locale ): array {
        $file = Helpers\path( 'languages/igs-ecommerce-' . $locale . '.po' );

        if ( ! is_readable( $file ) ) {
            return [
                'default'    => [],
                'contextual' => [],
            ];
        }

        $handle = fopen( $file, 'rb' );

        if ( ! $handle ) {
            return [
                'default'    => [],
                'contextual' => [],
            ];
        }

        $entries = [
            'default'    => [],
            'contextual' => [],
        ];
        $msgid   = null;
        $msgstr  = '';
        $state   = null;
        $context = null;

        while ( false !== ( $line = fgets( $handle ) ) ) {
            $line = rtrim( $line, "\r\n" );

            if ( '' === $line ) {
                if ( null !== $msgid ) {
                    self::store_entry( $entries, $msgid, $msgstr, $context );
                }

                $msgid   = null;
                $msgstr  = '';
                $state   = null;
                $context = null;
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
                $state  = 'msgid';
                continue;
            }

            if ( 0 === strpos( $line, 'msgstr ' ) ) {
                $msgstr = self::parse_po_string( substr( $line, 7 ) );
                $state  = 'msgstr';
                continue;
            }

            if ( isset( $line[0] ) && '"' === $line[0] && $state ) {
                if ( 'msgctxt' === $state && null !== $context ) {
                    $context .= self::parse_po_string( $line );
                } elseif ( 'msgid' === $state && null !== $msgid ) {
                    $msgid .= self::parse_po_string( $line );
                } elseif ( 'msgstr' === $state ) {
                    $msgstr .= self::parse_po_string( $line );
                }
            }
        }

        fclose( $handle );

        if ( null !== $msgid ) {
            self::store_entry( $entries, $msgid, $msgstr, $context );
        }

        return $entries;
    }

    /**
     * Store a parsed PO entry into the catalogue buckets.
     *
     * @param array{default: array<string,string>, contextual: array<string,array<string,string>>} $entries Entries catalogue.
     */
    private static function store_entry( array &$entries, ?string $msgid, string $msgstr, ?string $context ): void {
        if ( null === $msgid ) {
            return;
        }

        if ( '' === $msgid && ( null === $context || '' === $context ) ) {
            // Header entry, skip.
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
}
