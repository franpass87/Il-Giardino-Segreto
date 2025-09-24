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
    /**
     * Cache the validation result to avoid repeating the logic.
     *
     * @var bool|null
     */
    private static ?bool $validated = null;

    /**
     * Ensure all runtime requirements are satisfied.
     */
    public static function bootstrap(): bool {
        if ( null !== self::$validated ) {
            return self::$validated;
        }

        if ( self::has_woocommerce() ) {
            self::$validated = true;

            return true;
        }

        self::$validated = false;

        add_action( 'admin_notices', [ __CLASS__, 'render_missing_dependency_notice' ] );
        add_action( 'network_admin_notices', [ __CLASS__, 'render_missing_dependency_notice' ] );

        return false;
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

        echo '<div class="notice notice-error"><p>';
        echo wp_kses_post(
            sprintf(
                /* translators: %s: WooCommerce */
                __( 'IGS Ecommerce Customizations richiede %s per funzionare correttamente. Installa e attiva il plugin prima di utilizzarlo.', 'igs-ecommerce' ),
                '<strong>WooCommerce</strong>'
            )
        );
        echo '</p></div>';
    }
}
