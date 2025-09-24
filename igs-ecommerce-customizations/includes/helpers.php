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

    $product_id = $product->get_id();
    $is_tour    = false;

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

    $date = \DateTime::createFromFormat( 'd/m/Y', $raw_date );

    if ( ! $date instanceof \DateTime ) {
        return null;
    }

    return $date;
}

/**
 * Normalize the date ranges payload for storage.
 *
 * @param array<int,string>|null $starts Start dates.
 * @param array<int,string>|null $ends   End dates.
 *
 * @return array<int,array<string,string>>
 */
function sanitize_date_ranges( ?array $starts, ?array $ends ): array {
    $ranges = [];

    if ( empty( $starts ) ) {
        return $ranges;
    }

    foreach ( $starts as $index => $start_raw ) {
        $start_raw = is_string( $start_raw ) ? trim( $start_raw ) : '';
        $end_raw   = isset( $ends[ $index ] ) && is_string( $ends[ $index ] ) ? trim( $ends[ $index ] ) : '';

        $start_date = parse_tour_date( $start_raw );
        $end_date   = parse_tour_date( $end_raw );

        if ( ! $start_date || ! $end_date || $end_date < $start_date ) {
            continue;
        }

        $ranges[] = [
            'start' => $start_date->format( 'd/m/Y' ),
            'end'   => $end_date->format( 'd/m/Y' ),
        ];
    }

    return $ranges;
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
