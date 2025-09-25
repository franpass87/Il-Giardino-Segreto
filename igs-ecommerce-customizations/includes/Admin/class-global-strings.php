<?php
/**
 * Global string replacement manager.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide an interface to configure text replacements site wide.
 */
class Global_Strings {
    /**
     * Cached replacement rules grouped by type.
     *
     * @var array{
     *     literal: array<string,string>,
     *     regex: array<int,array{pattern:string,replacement:string}>
     * }|null
     */
    private static ?array $rules_cache = null;

    /**
     * Cache results of previous replacements to avoid repeated processing.
     *
     * @var array<string,string>
     */
    private static array $result_cache = [];

    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_filter( 'gettext', [ __CLASS__, 'filter_strings' ], 20, 3 );
        add_filter( 'gettext_with_context', [ __CLASS__, 'filter_strings' ], 20, 4 );
        add_filter( 'ngettext', [ __CLASS__, 'filter_plural_strings' ], 20, 5 );
        add_filter( 'ngettext_with_context', [ __CLASS__, 'filter_plural_strings' ], 20, 6 );
    }

    /**
     * Add the options page under Settings.
     */
    public static function add_menu_page(): void {
        add_options_page(
            __( 'Gestione testo globale', 'igs-ecommerce' ),
            __( 'Gestione testo', 'igs-ecommerce' ),
            'manage_options',
            'gw-global-strings',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Register the settings and fields.
     */
    public static function register_settings(): void {
        register_setting(
            'gw_global_strings_group',
            'gw_string_replacements_global',
            [
                'sanitize_callback' => [ __CLASS__, 'sanitize_rules' ],
            ]
        );

        add_settings_section(
            'gw_global_strings_section',
            __( 'Regole di sostituzione testo', 'igs-ecommerce' ),
            [ __CLASS__, 'render_section_description' ],
            'gw-global-strings'
        );

        add_settings_field(
            'gw_string_replacements_global_field',
            __( 'Regole', 'igs-ecommerce' ),
            [ __CLASS__, 'render_textarea' ],
            'gw-global-strings',
            'gw_global_strings_section'
        );
    }

    /**
     * Sanitize replacements input.
     */
    public static function sanitize_rules( $value ): string {
        $value = is_string( $value ) ? wp_unslash( $value ) : '';

        // Reset caches so the new rules are applied immediately.
        self::$rules_cache = null;
        self::$result_cache = [];

        return wp_kses_post( $value );
    }

    /**
     * Render the settings section description.
     */
    public static function render_section_description(): void {
        echo '<p>' . esc_html__( 'Inserisci una regola per riga. Separa il testo originale dal nuovo testo con il carattere "|".', 'igs-ecommerce' ) . '</p>';
        echo '<p>' . esc_html__( 'Per usare le espressioni regolari, prefissa la regola con "regex:" e racchiudi il pattern tra slash, ad esempio regex:/prodotti/i.', 'igs-ecommerce' ) . '</p>';
        echo '<p><strong>' . esc_html__( 'Esempio:', 'igs-ecommerce' ) . '</strong> <code>Prodotti correlati | Ti potrebbe interessare anche</code></p>';
    }

    /**
     * Render the textarea input.
     */
    public static function render_textarea(): void {
        $value = get_option( 'gw_string_replacements_global', '' );
        echo '<textarea name="gw_string_replacements_global" rows="10" cols="80" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
    }

    /**
     * Output the settings page markup.
     */
    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields( 'gw_global_strings_group' );
        do_settings_sections( 'gw-global-strings' );
        submit_button( __( 'Salva modifiche', 'igs-ecommerce' ) );
        echo '</form>';
        echo '</div>';
    }

    /**
     * Apply replacements to translated strings, including contextual variants.
     */
    public static function filter_strings( string $translated, string $text, string $domain, string $context = '' ): string {
        return self::replace_with_rules( $translated );
    }

    /**
     * Apply replacements to pluralised strings, including contextual variants.
     */
    public static function filter_plural_strings( string $translation, string $single, string $plural, int $number, string $domain, string $context = '' ): string {
        return self::replace_with_rules( $translation );
    }

    /**
     * Replace the provided string using cached rules if available.
     */
    private static function replace_with_rules( string $value ): string {
        self::ensure_rules_cache();

        if ( isset( self::$result_cache[ $value ] ) ) {
            return self::$result_cache[ $value ];
        }

        $result = $value;

        if ( isset( self::$rules_cache['literal'][ $value ] ) ) {
            $result = self::$rules_cache['literal'][ $value ];
        } else {
            foreach ( self::$rules_cache['regex'] as $rule ) {
                $replaced = @preg_replace( $rule['pattern'], $rule['replacement'], $result );

                if ( null === $replaced || ! is_string( $replaced ) ) {
                    continue;
                }

                $result = $replaced;
            }
        }

        self::$result_cache[ $value ] = $result;

        return $result;
    }

    /**
     * Parse and cache the configured replacement rules.
     */
    private static function ensure_rules_cache(): void {
        if ( null !== self::$rules_cache ) {
            return;
        }

        self::$rules_cache = [
            'literal' => [],
            'regex'   => [],
        ];

        $raw_rules = get_option( 'gw_string_replacements_global', '' );

        foreach ( preg_split( '/\r?\n/', (string) $raw_rules ) as $line ) {
            $parts = self::split_rule_line( $line );

            if ( null === $parts ) {
                continue;
            }

            [ $original, $replacement ] = $parts;

            if ( '' === $original ) {
                continue;
            }

            if ( 0 === stripos( $original, 'regex:' ) ) {
                $parsed = self::parse_regex_rule( substr( $original, 6 ) );

                if ( null !== $parsed ) {
                    self::$rules_cache['regex'][] = [
                        'pattern'     => $parsed,
                        'replacement' => $replacement,
                    ];
                }

                continue;
            }

            if ( '\\' === substr( $original, 0, 1 ) && 0 === stripos( substr( $original, 1 ), 'regex:' ) ) {
                $original = substr( $original, 1 );
            }

            self::$rules_cache['literal'][ $original ] = $replacement;
        }
    }

    /**
     * Split a raw rule line into source and replacement, supporting escaped separators.
     *
     * @param string $line Raw rule line.
     *
     * @return array{0:string,1:string}|null
     */
    private static function split_rule_line( $line ): ?array {
        if ( ! is_string( $line ) || '' === $line ) {
            return null;
        }

        $length  = strlen( $line );
        $escaped = false;

        for ( $i = 0; $i < $length; $i++ ) {
            $char = $line[ $i ];

            if ( '\\' === $char ) {
                $escaped = ! $escaped;
                continue;
            }

            if ( '|' === $char && ! $escaped ) {
                $original    = substr( $line, 0, $i );
                $replacement = substr( $line, $i + 1 );

                return [
                    self::unescape_rule_segment( $original ),
                    self::unescape_rule_segment( $replacement ),
                ];
            }

            $escaped = false;
        }

        return null;
    }

    /**
     * Normalize a rule segment by trimming and unescaping escaped separators.
     */
    private static function unescape_rule_segment( string $segment ): string {
        $segment = trim( $segment );

        if ( '' === $segment ) {
            return '';
        }

        return str_replace( '\\|', '|', $segment );
    }

    /**
     * Convert a user provided regex rule into a safe PCRE pattern.
     */
    private static function parse_regex_rule( string $pattern ): ?string {
        $pattern = trim( $pattern );

        if ( '' === $pattern ) {
            return null;
        }

        if ( preg_match( '#^/(.*?)(?<!\\)/([A-Za-z]*)$#', $pattern, $matches ) ) {
            $body  = $matches[1];
            $flags = self::sanitize_regex_flags( $matches[2] );
            $regex = '/' . $body . '/' . $flags;
        } else {
            $body  = str_replace( '/', '\/', $pattern );
            $regex = '/' . $body . '/';
        }

        if ( ! self::is_valid_regex( $regex ) ) {
            return null;
        }

        return $regex;
    }

    /**
     * Sanitize the allowed regex flags, removing unsupported characters and duplicates.
     */
    private static function sanitize_regex_flags( string $flags ): string {
        if ( '' === $flags ) {
            return '';
        }

        $allowed = [ 'i', 'm', 's', 'u', 'x', 'A', 'D', 'U', 'J' ];
        $clean   = '';

        foreach ( str_split( $flags ) as $flag ) {
            if ( in_array( $flag, $allowed, true ) && false === strpos( $clean, $flag ) ) {
                $clean .= $flag;
            }
        }

        return $clean;
    }

    /**
     * Determine whether the provided regex compiles successfully.
     */
    private static function is_valid_regex( string $pattern ): bool {
        set_error_handler( static function () {
            // Swallow warnings generated by invalid patterns.
        } );

        $result = preg_match( $pattern, '' );

        restore_error_handler();

        return false !== $result;
    }
}
