<?php
/**
 * Shared helper functions.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Helpers;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const TOUR_PRODUCT_CACHE_GROUP = 'igs_tour_product_flags';
const TOUR_PRODUCT_CACHE_TTL   = 3600;
const TOUR_META_KEYS           = [ '_date_ranges', '_paese_tour' ];

/**
 * Retrieve the absolute path for a file relative to the plugin root.
 */
function path( string $relative ): string {
    return IGS_ECOMMERCE_PATH . ltrim( $relative, '/' );
}

/**
 * Retrieve the public URL for a file relative to the plugin root.
 */
function url( string $relative ): string {
    return IGS_ECOMMERCE_URL . ltrim( $relative, '/' );
}

/**
 * Determine whether the provided product should be handled as a tour.
 *
 * @param int|WC_Product|null $product Product instance or ID.
 */
function is_tour_product( $product = null ): bool {
    if ( null === $product ) {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
    } elseif ( is_numeric( $product ) ) {
        $product = function_exists( 'wc_get_product' ) ? wc_get_product( $product ) : null;
    }

    if ( ! $product instanceof WC_Product ) {
        return false;
    }

    global $igs_ecommerce_tour_cache;

    if ( ! is_array( $igs_ecommerce_tour_cache ) ) {
        $igs_ecommerce_tour_cache = [];
    }

    $product_id = $product->get_id();

    if ( $product_id > 0 && array_key_exists( $product_id, $igs_ecommerce_tour_cache ) ) {
        $is_tour = (bool) $igs_ecommerce_tour_cache[ $product_id ];

        return (bool) apply_filters( 'igs_is_tour_product', $is_tour, $product );
    }

    if ( $product_id > 0 ) {
        $found  = false;
        $cached = wp_cache_get( $product_id, TOUR_PRODUCT_CACHE_GROUP, false, $found );

        if ( $found ) {
            $is_tour = (bool) $cached;
            $igs_ecommerce_tour_cache[ $product_id ] = $is_tour;

            return (bool) apply_filters( 'igs_is_tour_product', $is_tour, $product );
        }
    }

    $is_tour = false;

    if ( taxonomy_exists( 'product_cat' ) ) {
        $tour_terms = [ 'tour', 'tours', 'garden-tour', 'viaggi', 'travel' ];

        foreach ( $tour_terms as $term ) {
            if ( has_term( $term, 'product_cat', $product_id ) ) {
                $is_tour = true;
                break;
            }
        }
    }

    if ( ! $is_tour ) {
        $date_ranges = get_post_meta( $product_id, '_date_ranges', true );
        $country     = get_post_meta( $product_id, '_paese_tour', true );
        $is_tour     = ( is_array( $date_ranges ) && ! empty( $date_ranges ) ) || ! empty( $country );
    }

    if ( $product_id > 0 ) {
        $igs_ecommerce_tour_cache[ $product_id ] = $is_tour;
        wp_cache_set( $product_id, $is_tour, TOUR_PRODUCT_CACHE_GROUP, TOUR_PRODUCT_CACHE_TTL );
    }

    /**
     * Filter whether a product should be treated as a tour.
     */
    return (bool) apply_filters( 'igs_is_tour_product', $is_tour, $product );
}

/**
 * Convert a dd/mm/YYYY date string to a DateTime object.
 */
function parse_tour_date( string $raw_date ): ?\DateTime {
    $raw_date = trim( $raw_date );

    if ( '' === $raw_date ) {
        return null;
    }

    $timezone = null;

    if ( function_exists( 'wp_timezone' ) ) {
        $wp_timezone = wp_timezone();

        if ( $wp_timezone instanceof \DateTimeZone ) {
            $timezone = $wp_timezone;
        }
    }

    $date = $timezone instanceof \DateTimeZone
        ? \DateTime::createFromFormat( '!d/m/Y', $raw_date, $timezone )
        : \DateTime::createFromFormat( '!d/m/Y', $raw_date );

    if ( ! $date instanceof \DateTime ) {
        return null;
    }

    $errors = \DateTime::getLastErrors();

    if ( is_array( $errors ) && ( (int) ( $errors['warning_count'] ?? 0 ) > 0 || (int) ( $errors['error_count'] ?? 0 ) > 0 ) ) {
        return null;
    }

    return $date;
}

/**
 * Normalize the date ranges payload for storage.
 *
 * Ensures ranges are valid, deduplicated and sorted chronologically so that the
 * earliest interval is stored first.
 *
 * @param array<int,string>|null $starts Start dates.
 * @param array<int,string>|null $ends   End dates.
 *
 * @return array<int,array<string,string>>
 */
function sanitize_date_ranges( ?array $starts, ?array $ends ): array {
    if ( empty( $starts ) ) {
        return [];
    }

    $normalised = [];

    foreach ( $starts as $index => $start_raw ) {
        $start_raw = is_string( $start_raw ) ? trim( $start_raw ) : '';
        $end_raw   = isset( $ends[ $index ] ) && is_string( $ends[ $index ] ) ? trim( $ends[ $index ] ) : '';

        $start_date = parse_tour_date( $start_raw );
        $end_date   = parse_tour_date( $end_raw );

        if ( ! $start_date || ! $end_date || $end_date < $start_date ) {
            continue;
        }

        $start_formatted = $start_date->format( 'd/m/Y' );
        $end_formatted   = $end_date->format( 'd/m/Y' );
        $key             = $start_formatted . '|' . $end_formatted;

        if ( isset( $normalised[ $key ] ) ) {
            continue;
        }

        $normalised[ $key ] = [
            'start'   => $start_formatted,
            'end'     => $end_formatted,
            'startTs' => $start_date->getTimestamp(),
            'endTs'   => $end_date->getTimestamp(),
        ];
    }

    if ( empty( $normalised ) ) {
        return [];
    }

    $sorted = array_values( $normalised );

    usort(
        $sorted,
        static function ( array $a, array $b ): int {
            if ( $a['startTs'] === $b['startTs'] ) {
                return $a['endTs'] <=> $b['endTs'];
            }

            return $a['startTs'] <=> $b['startTs'];
        }
    );

    return array_map(
        static function ( array $range ): array {
            return [
                'start' => $range['start'],
                'end'   => $range['end'],
            ];
        },
        $sorted
    );
}

/**
 * Calculate the number of days between two valid dd/mm/YYYY strings.
 */
function calculate_duration( string $start, string $end ): ?int {
    $start_date = parse_tour_date( $start );
    $end_date   = parse_tour_date( $end );

    if ( ! $start_date || ! $end_date || $end_date < $start_date ) {
        return null;
    }

    return $start_date->diff( $end_date )->days + 1;
}

/**
 * Normalise a latitude string into a safe decimal representation.
 */
function normalize_latitude( $value ): ?string {
    return normalize_coordinate_value( $value, -90.0, 90.0 );
}

/**
 * Normalise a longitude string into a safe decimal representation.
 */
function normalize_longitude( $value ): ?string {
    return normalize_coordinate_value( $value, -180.0, 180.0 );
}

/**
 * Normalise an HTML5 date (YYYY-MM-DD) string.
 */
function normalize_html5_date( $value ): ?string {
    if ( ! is_scalar( $value ) ) {
        return null;
    }

    $normalized = trim( (string) $value );

    if ( '' === $normalized ) {
        return null;
    }

    $timezone = null;

    if ( function_exists( 'wp_timezone' ) ) {
        $wp_timezone = wp_timezone();

        if ( $wp_timezone instanceof \DateTimeZone ) {
            $timezone = $wp_timezone;
        }
    }

    $date = $timezone instanceof \DateTimeZone
        ? \DateTime::createFromFormat( '!Y-m-d', $normalized, $timezone )
        : \DateTime::createFromFormat( '!Y-m-d', $normalized );

    if ( ! $date instanceof \DateTime ) {
        return null;
    }

    $errors = \DateTime::getLastErrors();

    if ( is_array( $errors ) && ( (int) ( $errors['warning_count'] ?? 0 ) > 0 || (int) ( $errors['error_count'] ?? 0 ) > 0 ) ) {
        return null;
    }

    return $date->format( 'Y-m-d' );
}

/**
 * Internal helper that validates and formats coordinate values.
 *
 * @param mixed $value Raw coordinate value.
 */
function normalize_coordinate_value( $value, float $min, float $max ): ?string {
    if ( ! is_scalar( $value ) ) {
        return null;
    }

    $normalized = trim( (string) $value );

    if ( '' === $normalized ) {
        return null;
    }

    $normalized = str_replace( ',', '.', $normalized );

    if ( ! is_numeric( $normalized ) ) {
        return null;
    }

    $float = (float) $normalized;

    if ( $float < $min || $float > $max ) {
        return null;
    }

    $formatted = sprintf( '%.6F', $float );

    return rtrim( rtrim( $formatted, '0' ), '.' );
}

/**
 * Remove cached tour classification for a product.
 */
function clear_tour_product_cache( int $product_id ): void {
    if ( $product_id <= 0 ) {
        return;
    }

    global $igs_ecommerce_tour_cache;

    if ( is_array( $igs_ecommerce_tour_cache ) && array_key_exists( $product_id, $igs_ecommerce_tour_cache ) ) {
        unset( $igs_ecommerce_tour_cache[ $product_id ] );
    }

    wp_cache_delete( $product_id, TOUR_PRODUCT_CACHE_GROUP );
}

/**
 * Register hooks that keep the tour cache in sync with product changes.
 */
function register_tour_product_cache_invalidation(): void {
    add_action( 'clean_post_cache', __NAMESPACE__ . '\\handle_clean_post_cache', 10, 2 );
    add_action( 'set_object_terms', __NAMESPACE__ . '\\handle_term_assignment', 10, 6 );
    add_action( 'added_post_meta', __NAMESPACE__ . '\\handle_tour_meta_update', 10, 4 );
    add_action( 'updated_post_meta', __NAMESPACE__ . '\\handle_tour_meta_update', 10, 4 );
    add_action( 'deleted_post_meta', __NAMESPACE__ . '\\handle_tour_meta_delete', 10, 4 );
}

/**
 * Invalidate cache when a product post cache is cleared.
 *
 * @internal
 */
function handle_clean_post_cache( int $post_id, $post ): void {
    if ( 'product' !== get_post_type( $post_id ) ) {
        return;
    }

    clear_tour_product_cache( $post_id );
}

/**
 * Invalidate cache when product categories change.
 *
 * @internal
 */
function handle_term_assignment( int $object_id, $terms, $tt_ids, string $taxonomy, $append, $old_tt_ids ): void {
    if ( 'product_cat' !== $taxonomy ) {
        return;
    }

    clear_tour_product_cache( $object_id );
}

/**
 * Check whether a meta key affects tour detection.
 */
function is_tour_meta_key( string $meta_key ): bool {
    return in_array( $meta_key, TOUR_META_KEYS, true );
}

/**
 * Invalidate cache when tour-related metadata changes.
 *
 * @internal
 */
function handle_tour_meta_update( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
    if ( ! is_tour_meta_key( $meta_key ) ) {
        return;
    }

    if ( 'product' !== get_post_type( $object_id ) ) {
        return;
    }

    clear_tour_product_cache( $object_id );
}

/**
 * Invalidate cache when tour-related metadata is deleted.
 *
 * @internal
 */
function handle_tour_meta_delete( $meta_ids, int $object_id, string $meta_key, $meta_value ): void {
    if ( ! is_tour_meta_key( $meta_key ) ) {
        return;
    }

    if ( 'product' !== get_post_type( $object_id ) ) {
        return;
    }

    clear_tour_product_cache( $object_id );
}
