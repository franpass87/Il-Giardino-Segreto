<?php
/**
 * Shared maintenance utilities for cache and transient management.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide reusable maintenance helpers for CLI commands and upgrade routines.
 */
class Maintenance {
    /**
     * Flush all plugin related caches and return a summary.
     *
     * @return array{tour:int,geocode:int,info:int,translations:int}
     */
    public static function flush_all_caches(): array {
        $summary = [
            'tour'         => self::flush_tour_cache(),
            'geocode'      => self::flush_geocode_caches(),
            'info'         => self::flush_info_rate_limit_caches(),
            'translations' => self::flush_translation_caches(),
        ];

        /**
         * Fires after all plugin caches have been flushed.
         *
         * @param array{tour:int,geocode:int,info:int,translations:int} $summary Flush summary.
         */
        do_action( 'igs_after_flush_all_caches', $summary );

        return $summary;
    }

    /**
     * Reset cached tour classification results.
     */
    public static function flush_tour_cache(): int {
        global $igs_ecommerce_tour_cache;

        if ( is_array( $igs_ecommerce_tour_cache ) ) {
            $igs_ecommerce_tour_cache = [];
        }

        global $wpdb;

        if ( ! isset( $wpdb->posts ) ) {
            return 0;
        }

        $ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product'" );

        if ( empty( $ids ) ) {
            return 0;
        }

        $ids   = array_map( 'intval', $ids );
        $ids   = array_values( array_unique( $ids ) );
        $count = 0;

        foreach ( $ids as $id ) {
            wp_cache_delete( $id, Helpers\TOUR_PRODUCT_CACHE_GROUP );
            $count++;
        }

        return $count;
    }

    /**
     * Remove cached geocoding responses and rate limits.
     */
    public static function flush_geocode_caches(): int {
        $total = 0;

        $total += self::flush_prefixed_transients( 'igs_geocode_' );
        $total += self::flush_prefixed_transients( 'igs_geocode_rl_' );

        return $total;
    }

    /**
     * Clear booking/info rate limiting windows.
     */
    public static function flush_info_rate_limit_caches(): int {
        return self::flush_prefixed_transients( 'igs_info_rl_' );
    }

    /**
     * Flush cached translation catalogues.
     */
    public static function flush_translation_caches(): int {
        $count = self::flush_prefixed_transients( 'igs_translation_', true );
        Translations::flush_runtime_cache();

        return $count;
    }

    /**
     * Delete transients with the provided prefix across single and multisite tables.
     *
     * @param string $prefix               Transient prefix, without the `_transient_` part.
     * @param bool   $clear_translation_oc Whether to clear the translation object cache group.
     */
    private static function flush_prefixed_transients( string $prefix, bool $clear_translation_oc = false ): int {
        $count = self::flush_transients_in_table( $prefix, false, $clear_translation_oc );

        if ( is_multisite() ) {
            $count += self::flush_transients_in_table( $prefix, true, $clear_translation_oc );
        }

        return $count;
    }

    /**
     * Delete transients from a specific storage table.
     */
    private static function flush_transients_in_table( string $prefix, bool $network, bool $clear_translation_oc ): int {
        global $wpdb;

        $table   = $network ? $wpdb->sitemeta : $wpdb->options;
        $column  = $network ? 'meta_key' : 'option_name';
        $pattern = $network ? '_site_transient_' : '_transient_';

        if ( empty( $table ) ) {
            return 0;
        }

        $like = $wpdb->esc_like( $pattern . $prefix ) . '%';
        $sql  = $wpdb->prepare( "SELECT {$column} FROM {$table} WHERE {$column} LIKE %s", $like );
        $rows = $wpdb->get_col( $sql );

        if ( empty( $rows ) ) {
            return 0;
        }

        $deleted = 0;

        foreach ( $rows as $option_name ) {
            if ( 0 !== strpos( $option_name, $pattern ) ) {
                continue;
            }

            $key = substr( $option_name, strlen( $pattern ) );

            if ( $network ) {
                delete_site_transient( $key );
            } else {
                delete_transient( $key );
            }

            if ( $clear_translation_oc ) {
                wp_cache_delete( $key, Translations::get_cache_group() );
            }

            $deleted++;
        }

        return $deleted;
    }
}
