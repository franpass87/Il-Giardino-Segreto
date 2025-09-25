<?php
/**
 * Handle plugin upgrade routines.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Execute maintenance tasks when the plugin version changes.
 */
class Upgrades {
    private const OPTION_VERSION = 'igs_ecommerce_version';

    /**
     * Register hooks used to detect and process upgrades.
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'maybe_run_upgrades' ], 1 );
    }

    /**
     * Run upgrade routines when the stored version differs from the current one.
     */
    public static function maybe_run_upgrades(): void {
        if ( ! defined( 'IGS_ECOMMERCE_VERSION' ) ) {
            return;
        }

        $current_version = IGS_ECOMMERCE_VERSION;
        $stored_version  = get_option( self::OPTION_VERSION, '' );

        if ( ! is_string( $stored_version ) ) {
            $stored_version = '';
        }

        if ( $stored_version === $current_version ) {
            return;
        }

        // Skip expensive routines on the very first install.
        if ( '' !== $stored_version ) {
            $summary = Maintenance::flush_all_caches();

            /**
             * Fires after the plugin finished running its upgrade routines.
             *
             * @param string $stored_version  Previously stored version.
             * @param string $current_version Current plugin version.
             * @param array{tour:int,geocode:int,info:int,translations:int} $summary Cache flush summary.
             */
            do_action( 'igs_after_plugin_upgrade', $stored_version, $current_version, $summary );
        }

        update_option( self::OPTION_VERSION, $current_version );
    }
}
