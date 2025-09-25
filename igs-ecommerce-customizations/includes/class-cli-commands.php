<?php
/**
 * WP-CLI commands for maintenance tasks.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WP-CLI commands that help operating the plugin in production.
 */
class CLI_Commands {
    /**
     * Register commands when WP-CLI is available.
     */
    public static function register(): void {
        if ( ! class_exists( '\\WP_CLI' ) ) {
            return;
        }

        \WP_CLI::add_command( 'igs flush-caches', [ __CLASS__, 'flush_caches' ] );
    }

    /**
     * Flush plugin specific caches and transients.
     *
     * @param array<int,string> $args       Positional arguments (unused).
     * @param array<string,string> $assoc_args Associative arguments (unused).
     */
    public static function flush_caches( array $args = [], array $assoc_args = [] ): void {
        if ( ! class_exists( '\\WP_CLI' ) ) {
            return;
        }

        $summary = Maintenance::flush_all_caches();

        \WP_CLI::log( sprintf( __( 'Cache dei tour ripulita per %d prodotti.', 'igs-ecommerce' ), $summary['tour'] ) );
        \WP_CLI::log( sprintf( __( 'Eliminati %d transient di geocoding.', 'igs-ecommerce' ), $summary['geocode'] ) );
        \WP_CLI::log( sprintf( __( 'Eliminati %d transient di rate limiting.', 'igs-ecommerce' ), $summary['info'] ) );
        \WP_CLI::log( sprintf( __( 'Ripuliti %d transient di traduzione.', 'igs-ecommerce' ), $summary['translations'] ) );

        \WP_CLI::success( __( 'Cache del plugin svuotate.', 'igs-ecommerce' ) );
    }
}
