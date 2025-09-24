<?php
/**
 * Customisations for product archive cards.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display additional metadata in product loops for tour products.
 */
class Loop_Display {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        add_action( 'woocommerce_after_shop_loop_item_title', [ __CLASS__, 'render_meta' ], 15 );
        add_action( 'woocommerce_after_shop_loop_item', [ __CLASS__, 'render_full_card_link' ], 20 );
        add_filter( 'woocommerce_loop_add_to_cart_link', [ __CLASS__, 'maybe_remove_add_to_cart' ], 10, 2 );
    }

    /**
     * Enqueue styles for loop cards when viewing shop pages.
     */
    public static function enqueue_styles(): void {
        if ( ! self::is_catalog_context() ) {
            return;
        }

        wp_enqueue_style( 'igs-product-loop', Helpers\url( 'assets/css/product-loop.css' ), [], IGS_ECOMMERCE_VERSION );
    }

    /**
     * Determine whether we are in a catalogue context.
     */
    private static function is_catalog_context(): bool {
        return is_shop() || is_product_taxonomy() || ( is_post_type_archive( 'product' ) && ! is_singular() );
    }

    /**
     * Render tour meta information below the product title.
     */
    public static function render_meta(): void {
        global $product;

        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        $ranges  = get_post_meta( $product->get_id(), '_date_ranges', true );
        $country = get_post_meta( $product->get_id(), '_paese_tour', true );

        $has_valid_dates = false;

        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $range    = reset( $ranges );
            $start    = $range['start'] ?? '';
            $end      = $range['end'] ?? '';
            $duration = ( $start && $end ) ? Helpers\calculate_duration( $start, $end ) : null;

            if ( $start && $end ) {
                echo '<div class="igs-loop-meta__dates">' . esc_html( $start ) . ' → ' . esc_html( $end ) . '</div>';
                $has_valid_dates = true;
            }

            if ( $duration ) {
                $label = _n( 'giorno', 'giorni', (int) $duration, 'igs-ecommerce' );
                echo '<div class="igs-loop-meta__duration">' . esc_html( $duration ) . ' ' . esc_html( $label ) . '</div>';
            }
        }

        if ( ! $has_valid_dates ) {
            echo '<div class="igs-loop-meta__dates">' . esc_html__( 'Date non disponibili', 'igs-ecommerce' ) . '</div>';
        }

        if ( $country ) {
            echo '<div class="igs-loop-meta__country">' . esc_html( $country ) . '</div>';
        }
    }

    /**
     * Replace the default add to cart button with a full-card link for tours.
     *
     * @param string     $html    Button HTML.
     * @param WC_Product $product Product instance.
     */
    public static function maybe_remove_add_to_cart( string $html, WC_Product $product ): string {
        if ( Helpers\is_tour_product( $product ) ) {
            return '';
        }

        return $html;
    }

    /**
     * Make the whole card clickable for tours.
     */
    public static function render_full_card_link(): void {
        global $product;

        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        $url   = get_permalink( $product->get_id() );
        $label = get_the_title( $product->get_id() );

        echo '<a class="igs-loop-card-link" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">' . esc_html__( 'Vai al tour', 'igs-ecommerce' ) . '</a>';
    }
}
