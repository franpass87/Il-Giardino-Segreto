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
     * Cached replacement rules.
     *
     * @var array<string,string>|null
     */
    private static ?array $rules_cache = null;

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

        // Reset cache so the new rules are applied immediately.
        self::$rules_cache = null;

        return wp_kses_post( $value );
    }

    /**
     * Render the settings section description.
     */
    public static function render_section_description(): void {
        echo '<p>' . esc_html__( 'Inserisci una regola per riga. Separa il testo originale dal nuovo testo con il carattere "|".', 'igs-ecommerce' ) . '</p>';
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
        if ( null === self::$rules_cache ) {
            self::$rules_cache = [];
            $raw_rules         = get_option( 'gw_string_replacements_global', '' );

            foreach ( preg_split( '/\r?\n/', $raw_rules ) as $line ) {
                if ( strpos( $line, '|' ) === false ) {
                    continue;
                }

                [ $original, $replacement ] = array_map( 'trim', explode( '|', $line, 2 ) );

                if ( '' !== $original ) {
                    self::$rules_cache[ $original ] = $replacement;
                }
            }
        }

        if ( isset( self::$rules_cache[ $value ] ) ) {
            return self::$rules_cache[ $value ];
        }

        return $value;
    }
}
