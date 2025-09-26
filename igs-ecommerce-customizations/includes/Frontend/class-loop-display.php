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

        $ranges            = get_post_meta( $product->get_id(), '_date_ranges', true );
        $country_value_raw = get_post_meta( $product->get_id(), '_paese_tour', true );
        $country_label     = $country_value_raw ? sanitize_text_field( $country_value_raw ) : __( 'Paese non specificato', 'igs-ecommerce' );

        $details = [];
        $badges  = [];

        $dates_label   = __( 'Partenze', 'igs-ecommerce' );
        $duration_text = '';
        $duration      = null;
        $has_dates     = false;

        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $range = reset( $ranges );
            $start = isset( $range['start'] ) ? sanitize_text_field( $range['start'] ) : '';
            $end   = isset( $range['end'] ) ? sanitize_text_field( $range['end'] ) : '';

            if ( $start && $end ) {
                $details[] = [
                    'label' => $dates_label,
                    'value' => $start . ' → ' . $end,
                    'class' => '',
                ];
                $has_dates = true;
                $duration  = Helpers\calculate_duration( $start, $end );
            }
        }

        if ( ! $has_dates ) {
            $details[] = [
                'label' => $dates_label,
                'value' => __( 'Date non disponibili', 'igs-ecommerce' ),
                'class' => 'igs-loop-card__value--muted',
            ];
        }

        if ( $duration ) {
            $duration_label = _n( 'giorno', 'giorni', (int) $duration, 'igs-ecommerce' );
            $duration_text  = $duration . ' ' . $duration_label;

            $details[] = [
                'label' => __( 'Durata', 'igs-ecommerce' ),
                'value' => $duration_text,
                'class' => '',
            ];

            $badges[] = '<span class="igs-loop-card__badge igs-loop-card__badge--duration">' . esc_html( $duration_text ) . '</span>';
        }

        if ( $country_label ) {
            $details[] = [
                'label' => __( 'Paese', 'igs-ecommerce' ),
                'value' => $country_label,
                'class' => '',
            ];

            $badges[] = '<span class="igs-loop-card__badge igs-loop-card__badge--country">' . esc_html( $country_label ) . '</span>';
        }

        $average_rating = (float) $product->get_average_rating();
        $rating_count   = (int) $product->get_rating_count();

        if ( $average_rating > 0 && $rating_count > 0 ) {
            $rating_value = number_format_i18n( $average_rating, 1 );
            $reviews_text = sprintf(
                /* translators: 1: number of reviews. */
                _n( '%s recensione', '%s recensioni', $rating_count, 'igs-ecommerce' ),
                number_format_i18n( $rating_count )
            );

            $details[] = [
                'label' => __( 'Valutazione', 'igs-ecommerce' ),
                /* translators: 1: average rating, 2: number of reviews. */
                'value' => sprintf( __( '%1$s su 5 · %2$s', 'igs-ecommerce' ), $rating_value, $reviews_text ),
                'class' => '',
            ];

            $badges[] = '<span class="igs-loop-card__badge igs-loop-card__badge--rating">' . esc_html( $rating_value ) . '<span aria-hidden="true">★</span></span>';
        }

        if ( empty( $details ) ) {
            return;
        }

        echo '<div class="igs-loop-card__meta">';

        if ( ! empty( $badges ) ) {
            echo '<div class="igs-loop-card__badges" aria-hidden="true">' . implode( '', $badges ) . '</div>';
        }

        echo '<dl class="igs-loop-card__details">';

        foreach ( $details as $detail ) {
            echo '<div class="igs-loop-card__detail">';
            echo '<dt class="igs-loop-card__label">' . esc_html( $detail['label'] ) . '</dt>';
            $value_class = 'igs-loop-card__value' . ( ! empty( $detail['class'] ) ? ' ' . esc_attr( $detail['class'] ) : '' );
            echo '<dd class="' . $value_class . '">' . esc_html( $detail['value'] ) . '</dd>';
            echo '</div>';
        }

        echo '</dl>';
        echo '</div>';
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
