<?php
/**
 * Validate runtime dependencies for the plugin.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle dependency validation and related notices.
 */
class Dependencies {
    private const MIN_PHP_VERSION = '7.4';
    private const MIN_WP_VERSION  = '6.0';
    private const MIN_WC_VERSION  = '7.0';

    /**
     * Cache the validation result to avoid repeating the logic.
     *
     * @var bool|null
     */
    private static ?bool $validated = null;

    /**
     * Collected validation error messages.
     *
     * @var array<int,string>
     */
    private static array $errors = [];

    /**
     * Ensure all runtime requirements are satisfied.
     */
    public static function bootstrap(): bool {
        if ( null !== self::$validated ) {
            return self::$validated;
        }

        if ( self::validate_environment() ) {
            self::$validated = true;

            return true;
        }

        self::$validated = false;

        add_action( 'admin_notices', [ __CLASS__, 'render_missing_dependency_notice' ] );
        add_action( 'network_admin_notices', [ __CLASS__, 'render_missing_dependency_notice' ] );

        return false;
    }

    /**
     * Validate dependencies during activation and stop the process when requirements are not met.
     */
    public static function on_activation(): void {
        self::$validated = null;
        self::$errors    = [];

        if ( self::validate_environment() ) {
            self::$validated = true;

            return;
        }

        self::$validated = false;

        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin       = plugin_basename( IGS_ECOMMERCE_FILE );
        $network_wide = false;

        if ( is_multisite() ) {
            $network_param = filter_input( INPUT_GET, 'networkwide', FILTER_VALIDATE_BOOLEAN );

            if ( null !== $network_param ) {
                $network_wide = (bool) $network_param;
            } elseif ( function_exists( 'is_network_admin' ) ) {
                $network_wide = is_network_admin();
            }
        }

        deactivate_plugins( $plugin, false, $network_wide );

        $message  = '<p>' . esc_html__( 'IGS Ecommerce Customizations non può essere attivato perché alcuni requisiti non sono soddisfatti.', 'igs-ecommerce' ) . '</p>';
        $message .= '<p>' . esc_html__( 'Correggi i problemi riportati di seguito e riprova ad attivare il plugin.', 'igs-ecommerce' ) . '</p>';
        $message .= self::build_errors_list_markup();

        if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( '\\WP_CLI' ) ) {
            \WP_CLI::error( wp_strip_all_tags( $message ) );
        }

        wp_die(
            wp_kses_post( $message ),
            esc_html__( 'Attivazione plugin fallita', 'igs-ecommerce' ),
            [
                'response'  => 500,
                'back_link' => true,
            ]
        );
    }

    /**
     * Determine whether WooCommerce is available.
     */
    private static function has_woocommerce(): bool {
        if ( class_exists( '\\WooCommerce', false ) ) {
            return true;
        }

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            return true;
        }

        if ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Display an admin notice when a dependency is missing.
     */
    public static function render_missing_dependency_notice(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( empty( self::$errors ) ) {
            return;
        }

        echo '<div class="notice notice-error">';
        echo '<p>' . esc_html__( 'IGS Ecommerce Customizations non può avviarsi perché alcuni requisiti non sono soddisfatti.', 'igs-ecommerce' ) . '</p>';
        echo '<p>' . esc_html__( 'Per favore, risolvi i seguenti problemi e riprova ad attivare il plugin.', 'igs-ecommerce' ) . '</p>';
        echo self::build_errors_list_markup();
        echo '</div>';
    }

    /**
     * Validate the runtime environment and collect errors if present.
     */
    private static function validate_environment(): bool {
        self::$errors = [];

        if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
            self::$errors[] = sprintf(
                /* translators: 1: Minimum PHP version, 2: Current PHP version */
                __( 'IGS Ecommerce Customizations richiede PHP %1$s o superiore. Versione corrente: %2$s.', 'igs-ecommerce' ),
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
        }

        $wp_version = self::get_wordpress_version();

        if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
            self::$errors[] = sprintf(
                /* translators: 1: Minimum WordPress version, 2: Current WordPress version */
                __( 'IGS Ecommerce Customizations richiede WordPress %1$s o superiore. Versione corrente: %2$s.', 'igs-ecommerce' ),
                self::MIN_WP_VERSION,
                $wp_version
            );
        }

        if ( ! self::has_woocommerce() ) {
            self::$errors[] = __( 'WooCommerce non è installato o attivo.', 'igs-ecommerce' );
        } else {
            $woocommerce_version = self::get_woocommerce_version();

            if ( null === $woocommerce_version ) {
                self::$errors[] = __( 'Impossibile determinare la versione di WooCommerce installata.', 'igs-ecommerce' );
            } elseif ( version_compare( $woocommerce_version, self::MIN_WC_VERSION, '<' ) ) {
                self::$errors[] = sprintf(
                    /* translators: 1: Minimum WooCommerce version, 2: Current WooCommerce version */
                    __( 'IGS Ecommerce Customizations richiede WooCommerce %1$s o superiore. Versione corrente: %2$s.', 'igs-ecommerce' ),
                    self::MIN_WC_VERSION,
                    $woocommerce_version
                );
            }
        }

        return empty( self::$errors );
    }

    /**
     * Retrieve the current WordPress version string.
     */
    private static function get_wordpress_version(): string {
        $version = get_bloginfo( 'version' );

        if ( is_string( $version ) && '' !== $version ) {
            return $version;
        }

        global $wp_version;

        if ( isset( $wp_version ) && is_string( $wp_version ) ) {
            return $wp_version;
        }

        return '0';
    }

    /**
     * Retrieve the active WooCommerce version if available.
     */
    private static function get_woocommerce_version(): ?string {
        if ( defined( 'WC_VERSION' ) ) {
            return WC_VERSION;
        }

        if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
            return WOOCOMMERCE_VERSION;
        }

        if ( class_exists( '\\WooCommerce', false ) ) {
            $instance = \WooCommerce::instance();

            if ( $instance && isset( $instance->version ) && is_string( $instance->version ) ) {
                return $instance->version;
            }
        }

        return null;
    }

    /**
     * Generate an HTML list for the collected validation errors.
     */
    private static function build_errors_list_markup(): string {
        if ( empty( self::$errors ) ) {
            return '';
        }

        $items = '';

        foreach ( self::$errors as $message ) {
            $items .= '<li>' . esc_html( $message ) . '</li>';
        }

        return '<ul>' . $items . '</ul>';
    }
}
