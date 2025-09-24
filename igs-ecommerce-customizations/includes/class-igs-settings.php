<?php
/**
 * Settings handler for the plugin.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manage plugin options and settings page.
 */
class Settings {
    /**
     * Option key.
     */
    private const OPTION_KEY = 'igs_ecommerce_customizations';

    /**
     * Default option values.
     *
     * @var array<string, mixed>
     */
    private $defaults = [
        'enable_free_shipping_progress' => 1,
        'free_shipping_threshold'       => 49.0,
        'free_shipping_goal_message'    => 'Aggiungi {remaining} per ottenere la spedizione gratuita.',
        'free_shipping_success_message' => 'Complimenti! Hai diritto alla spedizione gratuita.',
        'enable_discount_badge'         => 1,
        'enable_new_badge'              => 1,
        'new_badge_days'                => 30,
        'new_badge_label'               => 'Novità',
        'enable_checkout_fields'        => 1,
        'require_codice_fiscale'        => 1,
        'require_partita_iva'           => 0,
        'enable_gift_message'           => 1,
        'gift_message_max_length'       => 180,
    ];

    /**
     * Register hooks for settings screen.
     */
    public function register(): void {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'register_menu_page' ] );
    }

    /**
     * Register option and fields.
     */
    public function register_settings(): void {
        register_setting( self::OPTION_KEY, self::OPTION_KEY, [ $this, 'sanitize_settings' ] );

        add_settings_section(
            'igs_general_settings',
            __( 'Impostazioni generali', 'igs-ecommerce' ),
            null,
            self::OPTION_KEY
        );

        $this->add_checkbox_field( 'enable_free_shipping_progress', __( 'Mostra barra progresso spedizione gratuita', 'igs-ecommerce' ) );
        $this->add_number_field( 'free_shipping_threshold', __( 'Soglia spedizione gratuita (€)', 'igs-ecommerce' ), 0, 10000, 1 );
        $this->add_text_field( 'free_shipping_goal_message', __( 'Messaggio per soglia non raggiunta', 'igs-ecommerce' ) );
        $this->add_text_field( 'free_shipping_success_message', __( 'Messaggio quando la soglia è raggiunta', 'igs-ecommerce' ) );

        $this->add_checkbox_field( 'enable_discount_badge', __( 'Mostra percentuale di sconto sui prodotti in offerta', 'igs-ecommerce' ) );

        $this->add_checkbox_field( 'enable_new_badge', __( 'Mostra badge "Novità" per i nuovi prodotti', 'igs-ecommerce' ) );
        $this->add_number_field( 'new_badge_days', __( 'Giorni in cui un prodotto è considerato nuovo', 'igs-ecommerce' ), 1, 120, 1 );
        $this->add_text_field( 'new_badge_label', __( 'Etichetta badge novità', 'igs-ecommerce' ) );

        $this->add_checkbox_field( 'enable_checkout_fields', __( 'Aggiungi campi extra al checkout', 'igs-ecommerce' ) );
        $this->add_checkbox_field( 'require_codice_fiscale', __( 'Rendi obbligatorio il Codice Fiscale', 'igs-ecommerce' ) );
        $this->add_checkbox_field( 'require_partita_iva', __( 'Rendi obbligatoria la Partita IVA', 'igs-ecommerce' ) );
        $this->add_checkbox_field( 'enable_gift_message', __( 'Abilita messaggio regalo in checkout', 'igs-ecommerce' ) );
        $this->add_number_field( 'gift_message_max_length', __( 'Numero massimo di caratteri per il messaggio regalo', 'igs-ecommerce' ), 50, 500, 10 );
    }

    /**
     * Add submenu page under WooCommerce.
     */
    public function register_menu_page(): void {
        $parent_slug = class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php';

        add_submenu_page(
            $parent_slug,
            __( 'Personalizzazioni IGS', 'igs-ecommerce' ),
            __( 'Personalizzazioni IGS', 'igs-ecommerce' ),
            'manage_woocommerce',
            self::OPTION_KEY,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Render admin settings page.
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Personalizzazioni IGS', 'igs-ecommerce' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_KEY );
                do_settings_sections( self::OPTION_KEY );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize option values.
     *
     * @param array<string, mixed> $input Raw option values.
     * @return array<string, mixed>
     */
    public function sanitize_settings( array $input ): array {
        $output = $this->get_all();

        $checkboxes = [
            'enable_free_shipping_progress',
            'enable_discount_badge',
            'enable_new_badge',
            'enable_checkout_fields',
            'require_codice_fiscale',
            'require_partita_iva',
            'enable_gift_message',
        ];

        foreach ( $checkboxes as $checkbox ) {
            $output[ $checkbox ] = isset( $input[ $checkbox ] ) ? 1 : 0;
        }

        if ( isset( $input['free_shipping_threshold'] ) ) {
            $output['free_shipping_threshold'] = max( 0, (float) $input['free_shipping_threshold'] );
        }

        if ( isset( $input['free_shipping_goal_message'] ) ) {
            $output['free_shipping_goal_message'] = sanitize_text_field( $input['free_shipping_goal_message'] );
        }

        if ( isset( $input['free_shipping_success_message'] ) ) {
            $output['free_shipping_success_message'] = sanitize_text_field( $input['free_shipping_success_message'] );
        }

        if ( isset( $input['new_badge_days'] ) ) {
            $output['new_badge_days'] = max( 1, (int) $input['new_badge_days'] );
        }

        if ( isset( $input['new_badge_label'] ) ) {
            $output['new_badge_label'] = sanitize_text_field( $input['new_badge_label'] );
        }

        if ( isset( $input['gift_message_max_length'] ) ) {
            $output['gift_message_max_length'] = max( 20, (int) $input['gift_message_max_length'] );
        }

        return $output;
    }

    /**
     * Retrieve full option array merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function get_all(): array {
        $saved = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( $saved, $this->defaults );
    }

    /**
     * Retrieve specific option value.
     *
     * @param string     $key     Option key.
     * @param mixed|null $default Default if not set.
     * @return mixed
     */
    public function get( string $key, $default = null ) {
        $options = $this->get_all();

        return $options[ $key ] ?? $default;
    }

    /**
     * Check if a feature is enabled.
     */
    public function is_enabled( string $feature ): bool {
        return (bool) $this->get( 'enable_' . $feature, 0 );
    }

    /**
     * Add checkbox field to settings page.
     */
    private function add_checkbox_field( string $key, string $label ): void {
        add_settings_field(
            $key,
            esc_html( $label ),
            function () use ( $key ) {
                $value = (int) $this->get( $key );
                printf(
                    '<input type="checkbox" name="%1$s[%2$s]" value="1" %3$s />',
                    esc_attr( self::OPTION_KEY ),
                    esc_attr( $key ),
                    checked( $value, 1, false )
                );
            },
            self::OPTION_KEY,
            'igs_general_settings'
        );
    }

    /**
     * Add number input to settings page.
     */
    private function add_number_field( string $key, string $label, int $min, int $max, int $step ): void {
        add_settings_field(
            $key,
            esc_html( $label ),
            function () use ( $key, $min, $max, $step ) {
                $value = $this->get( $key );
                printf(
                    '<input type="number" name="%1$s[%2$s]" value="%3$s" min="%4$d" max="%5$d" step="%6$d" class="small-text" />',
                    esc_attr( self::OPTION_KEY ),
                    esc_attr( $key ),
                    esc_attr( $value ),
                    $min,
                    $max,
                    $step
                );
            },
            self::OPTION_KEY,
            'igs_general_settings'
        );
    }

    /**
     * Add text input to settings page.
     */
    private function add_text_field( string $key, string $label ): void {
        add_settings_field(
            $key,
            esc_html( $label ),
            function () use ( $key ) {
                $value = $this->get( $key );
                printf(
                    '<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
                    esc_attr( self::OPTION_KEY ),
                    esc_attr( $key ),
                    esc_attr( $value )
                );
            },
            self::OPTION_KEY,
            'igs_general_settings'
        );
    }
}
