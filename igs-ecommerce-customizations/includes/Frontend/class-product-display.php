<?php
/**
 * Custom single product layout for tours.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;
use WC_Product;
use WC_Product_Variable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Override the WooCommerce single product template for tour products.
 */
class Product_Display {
    /**
     * Whether hooks have already been adjusted for the current request.
     */
    private static bool $hooks_applied = false;

    /**
     * Register hooks.
     */
    public static function init(): void {
        add_filter( 'woocommerce_get_price_html', [ __CLASS__, 'filter_price_html' ], 100, 2 );
        add_action( 'wp', [ __CLASS__, 'maybe_setup_hooks' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Remove the default single product pieces when the current product is a tour.
     */
    public static function maybe_setup_hooks(): void {
        if ( self::$hooks_applied || ! is_product() ) {
            return;
        }

        $product = wc_get_product( get_queried_object_id() );

        if ( ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
        remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

        add_action( 'woocommerce_before_single_product_summary', [ __CLASS__, 'render_layout' ], 1 );

        self::$hooks_applied = true;
    }

    /**
     * Enqueue CSS for the custom layout.
     */
    public static function enqueue_assets(): void {
        if ( ! is_product() ) {
            return;
        }

        $product = wc_get_product( get_queried_object_id() );

        if ( ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        wp_enqueue_style( 'igs-single-product', Helpers\url( 'assets/css/single-product.css' ), [], IGS_ECOMMERCE_VERSION );
    }

    /**
     * Render the hero and sidebar layout.
     */
    public static function render_layout(): void {
        global $product;

        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        $product_id = $product->get_id();
        $ranges     = get_post_meta( $product_id, '_date_ranges', true );
        $country    = get_post_meta( $product_id, '_paese_tour', true );
        $excerpt    = apply_filters( 'woocommerce_short_description', $product->get_short_description() );

        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src();

        $first_range = is_array( $ranges ) && ! empty( $ranges ) ? reset( $ranges ) : null;
        $dates_html  = $first_range ? esc_html( $first_range['start'] ?? '' ) . ' → ' . esc_html( $first_range['end'] ?? '' ) : esc_html__( 'Date non disponibili', 'igs-ecommerce' );

        $duration = null;

        if ( $first_range && ! empty( $first_range['start'] ) && ! empty( $first_range['end'] ) ) {
            $duration = Helpers\calculate_duration( $first_range['start'], $first_range['end'] );
        }

        $price_html = self::filter_price_html( '', $product );

        echo '<div class="igs-tour-hero" style="--igs-tour-hero-image:url(' . esc_url( (string) $image_url ) . ')">';
        echo '<div class="igs-tour-hero__overlay"></div>';
        echo '<div class="igs-tour-hero__content">';
        echo '<h1 class="igs-tour-hero__title">' . esc_html( get_the_title( $product_id ) ) . '</h1>';
        echo '<div class="igs-tour-hero__country">' . ( $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' ) ) . '</div>';
        echo '<div class="igs-tour-hero__dates">' . $dates_html . '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="igs-tour-layout">';
        echo '<div class="igs-tour-layout__content">' . wp_kses_post( $excerpt ) . '</div>';
        echo '<aside class="igs-tour-layout__sidebar">';
        echo '<div class="igs-tour-layout__price">' . $price_html . '</div>';
        echo '<div class="igs-tour-layout__installment">' . esc_html__( 'Pagamento a rate disponibile', 'igs-ecommerce' ) . '</div>';

        if ( $duration ) {
            $label = _n( 'giorno', 'giorni', (int) $duration, 'igs-ecommerce' );
            echo '<div class="igs-tour-layout__duration"><strong>' . esc_html( $duration ) . '</strong> ' . esc_html( $label ) . '</div>';
        }

        echo '<ul class="igs-tour-layout__services">';
        echo '<li>🪷 ' . esc_html__( 'Ingressi ai siti e giardini', 'igs-ecommerce' ) . '</li>';
        echo '<li>🏨 ' . esc_html__( 'Pernottamento incluso', 'igs-ecommerce' ) . '</li>';
        echo '<li>🚌 ' . esc_html__( 'Trasferimenti in loco', 'igs-ecommerce' ) . '</li>';
        echo '<li>🍽️ ' . esc_html__( 'Pasti da itinerario', 'igs-ecommerce' ) . '</li>';
        echo '<li>🗺️ ' . esc_html__( 'Guida locale', 'igs-ecommerce' ) . '</li>';
        echo '</ul>';

        echo '<div class="igs-tour-layout__country-band">' . ( $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' ) ) . '</div>';
        echo '</aside>';
        echo '</div>';
    }

    /**
     * Simplify price formatting for tours.
     *
     * @param string     $price_html Existing HTML.
     * @param WC_Product $product    Product instance.
     */
    public static function filter_price_html( $price_html, $product ) {
        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            return $price_html;
        }

        if ( $product instanceof WC_Product_Variable ) {
            $min_price = $product->get_variation_price( 'min', true );

            if ( $min_price > 0 ) {
                return sprintf( __( 'da %s', 'igs-ecommerce' ), wc_price( $min_price, [ 'decimals' => 0 ] ) );
            }

            return '<span class="igs-tour-price--placeholder">' . esc_html__( 'info in arrivo', 'igs-ecommerce' ) . '</span>';
        }

        $raw_price = $product->get_price();

        if ( is_numeric( $raw_price ) && $raw_price > 0 ) {
            return wc_price( (float) $raw_price, [ 'decimals' => 0 ] );
        }

        return '<span class="igs-tour-price--placeholder">' . esc_html__( 'info in arrivo', 'igs-ecommerce' ) . '</span>';
    }
}
