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
        $hero_lede  = '';

        if ( $excerpt ) {
            $hero_lede = wp_trim_words( wp_strip_all_tags( (string) $excerpt ), 26, '&hellip;' );
        }

        $image_id        = $product->get_image_id();
        $image_url       = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src();
        $hero_style_vars = [];

        if ( $image_url ) {
            $hero_style_vars[] = '--igs-tour-hero-image:url(' . esc_url( (string) $image_url ) . ')';
        }

        if ( $image_id ) {
            $retina_image = wp_get_attachment_image_src( $image_id, '2048x2048' );

            if ( is_array( $retina_image ) && ! empty( $retina_image[0] ) && $retina_image[0] !== $image_url ) {
                $hero_style_vars[] = '--igs-tour-hero-image-set:image-set(url(' . esc_url( (string) $image_url ) . ') 1x, url(' . esc_url( (string) $retina_image[0] ) . ') 2x)';
            }
        }

        if ( $hero_style_vars ) {
            $hero_style_attr = ' style="' . esc_attr( implode( ';', $hero_style_vars ) . ';' ) . '"';
        } else {
            $hero_style_attr = '';
        }

        $first_range = is_array( $ranges ) && ! empty( $ranges ) ? reset( $ranges ) : null;
        $dates_html  = $first_range ? esc_html( $first_range['start'] ?? '' ) . ' → ' . esc_html( $first_range['end'] ?? '' ) : esc_html__( 'Date non disponibili', 'igs-ecommerce' );

        $duration = null;

        if ( $first_range && ! empty( $first_range['start'] ) && ! empty( $first_range['end'] ) ) {
            $duration = Helpers\calculate_duration( $first_range['start'], $first_range['end'] );
        }

        $primary_category   = '';
        $subcategory_label  = '';
        $breadcrumb_segments = [];
        $categories          = wc_get_product_terms( $product_id, 'product_cat', [
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        ] );

        if ( is_array( $categories ) && ! empty( $categories ) ) {
            $primary_term = null;
            $max_depth    = -1;

            foreach ( $categories as $term ) {
                if ( ! $term instanceof \WP_Term ) {
                    continue;
                }

                $ancestors = get_ancestors( $term->term_id, 'product_cat' );
                $depth     = is_array( $ancestors ) ? count( $ancestors ) : 0;

                if ( $depth > $max_depth ) {
                    $max_depth    = $depth;
                    $primary_term = $term;
                }
            }

            if ( ! $primary_term instanceof \WP_Term ) {
                $primary_term = $categories[0];
            }

            if ( $primary_term instanceof \WP_Term ) {
                $trail_ids = array_reverse( get_ancestors( $primary_term->term_id, 'product_cat' ) );

                if ( is_array( $trail_ids ) ) {
                    foreach ( $trail_ids as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'product_cat' );

                        if ( $ancestor instanceof \WP_Term && ! is_wp_error( $ancestor ) ) {
                            $breadcrumb_segments[] = $ancestor;
                        }
                    }
                }

                $breadcrumb_segments[] = $primary_term;
            }
        }

        if ( ! empty( $breadcrumb_segments ) ) {
            $first_segment = $breadcrumb_segments[0];

            if ( $first_segment instanceof \WP_Term ) {
                $primary_category = $first_segment->name;
            }

            $last_segment = $breadcrumb_segments[ count( $breadcrumb_segments ) - 1 ];

            if ( $last_segment instanceof \WP_Term ) {
                $subcategory_label = $last_segment->name;
            }

            if ( $primary_category === $subcategory_label ) {
                $subcategory_label = '';
            }
        }

        $breadcrumb_items = [];

        $shop_page_id = wc_get_page_id( 'shop' );

        if ( $shop_page_id > 0 && 'publish' === get_post_status( $shop_page_id ) ) {
            $breadcrumb_items[] = [
                'label' => get_the_title( $shop_page_id ),
                'url'   => get_permalink( $shop_page_id ),
            ];
        }

        if ( ! empty( $breadcrumb_segments ) ) {
            foreach ( $breadcrumb_segments as $segment ) {
                if ( ! $segment instanceof \WP_Term ) {
                    continue;
                }

                $term_link = get_term_link( $segment );

                if ( is_wp_error( $term_link ) ) {
                    continue;
                }

                $breadcrumb_items[] = [
                    'label' => $segment->name,
                    'url'   => $term_link,
                ];
            }
        }

        $breadcrumb_items[] = [
            'label' => get_the_title( $product_id ),
            'url'   => '',
        ];

        $price_html = self::filter_price_html( '', $product );
        $raw_price  = $product->get_price();
        $installment_html = '';

        if ( '' !== $raw_price && is_numeric( $raw_price ) ) {
            $price_number = (float) wc_clean( $raw_price );

            if ( $price_number > 0 ) {
                $installments     = apply_filters( 'igs_tour_installment_count', 3, $product );
                $installments     = max( 2, (int) $installments );
                $installment_cost = $price_number / $installments;
                $amount_formatted = wc_price( $installment_cost );
                $installment_html = '<p class="igs-tour-layout__installment" role="status">';
                $installment_html .= '<span class="igs-tour-layout__badge" aria-hidden="true"></span>';
                $installment_html .= '<span class="igs-tour-layout__installment-text">' . esc_html__( 'Pagamento a rate disponibile', 'igs-ecommerce' ) . '</span>';
                /* translators: 1: formatted installment amount, 2: number of installments. */
                $installment_html .= '<span class="igs-tour-layout__installment-amount">' . sprintf( esc_html__( '%1$s al mese per %2$s rate', 'igs-ecommerce' ), wp_kses_post( $amount_formatted ), esc_html( (string) $installments ) ) . '</span>';
                $installment_html .= '<span class="igs-tour-layout__installment-note">' . esc_html__( 'Esempio senza interessi, salvo approvazione.', 'igs-ecommerce' ) . '</span>';
                $installment_html .= '</p>';
            }
        }

        $country_label = $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' );

        echo '<div class="igs-tour-hero"' . $hero_style_attr . '>';
        echo '<div class="igs-tour-hero__overlay" aria-hidden="true"></div>';

        if ( ! empty( $breadcrumb_items ) ) {
            echo '<nav class="igs-tour-hero__breadcrumb" aria-label="' . esc_attr__( 'Percorso', 'igs-ecommerce' ) . '">';
            echo '<ol class="igs-tour-hero__breadcrumb-list">';

            $total_items = count( $breadcrumb_items );

            foreach ( $breadcrumb_items as $index => $item ) {
                $is_last = ( $index === $total_items - 1 );
                $label   = isset( $item['label'] ) ? $item['label'] : '';
                $url     = isset( $item['url'] ) ? $item['url'] : '';

                echo '<li class="igs-tour-hero__breadcrumb-item">';

                if ( ! $is_last && $url ) {
                    echo '<a class="igs-tour-hero__breadcrumb-link" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
                } else {
                    echo '<span class="igs-tour-hero__breadcrumb-current" aria-current="page">' . esc_html( $label ) . '</span>';
                }

                echo '</li>';
            }

            echo '</ol>';
            echo '</nav>';
        }

        echo '<div class="igs-tour-hero__content">';
        echo '<h1 class="igs-tour-hero__title">' . esc_html( get_the_title( $product_id ) ) . '</h1>';

        if ( $hero_lede ) {
            echo '<p class="igs-tour-hero__lede">' . esc_html( $hero_lede ) . '</p>';
        }
        echo '<div class="igs-tour-hero__meta">';

        if ( $primary_category ) {
            echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--category">' . esc_html( $primary_category ) . '</span>';
        }

        if ( $subcategory_label ) {
            echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--subcategory">' . esc_html( $subcategory_label ) . '</span>';
        }

        echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--country">' . esc_html( $country_label ) . '</span>';
        echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--dates">' . $dates_html . '</span>';

        if ( $duration ) {
            $label = _n( 'giorno', 'giorni', (int) $duration, 'igs-ecommerce' );
            echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--duration">' . esc_html( $duration ) . ' ' . esc_html( $label ) . '</span>';
        }

        $average_rating = (float) $product->get_average_rating();
        $rating_count   = (int) $product->get_rating_count();

        if ( $average_rating > 0 && $rating_count > 0 ) {
            $rating_text = sprintf(
                /* translators: 1: average rating, 2: number of reviews. */
                _n( '%1$s su 5 · %2$s recensione', '%1$s su 5 · %2$s recensioni', $rating_count, 'igs-ecommerce' ),
                number_format_i18n( $average_rating, 1 ),
                number_format_i18n( $rating_count )
            );

            echo '<span class="igs-tour-hero__meta-item igs-tour-hero__meta-item--rating">' . esc_html( $rating_text ) . '</span>';
        }

        echo '</div>';
        $scroll_text      = __( 'Scopri il programma', 'igs-ecommerce' );
        $scroll_aria_text = __( 'Vai direttamente al programma del tour', 'igs-ecommerce' );

        echo '<a class="igs-tour-hero__scroll" href="#igs-tour-layout" data-scroll-target="#igs-tour-layout" aria-label="' . esc_attr( $scroll_aria_text ) . '">';
        echo '<span class="igs-tour-hero__scroll-text">' . esc_html( $scroll_text ) . '</span>';
        echo '</a>';
        echo '</div>';
        echo '</div>';

        echo '<div id="igs-tour-layout" class="igs-tour-layout">';
        echo '<div class="igs-tour-layout__content">' . wp_kses_post( $excerpt ) . '</div>';
        echo '<aside class="igs-tour-layout__sidebar" aria-labelledby="igs-tour-sidebar-title">';
        echo '<header class="igs-tour-layout__sidebar-header">';
        echo '<span class="igs-tour-layout__sidebar-icon" aria-hidden="true"></span>';
        echo '<div class="igs-tour-layout__sidebar-heading">';
        echo '<span class="igs-tour-layout__sidebar-title" id="igs-tour-sidebar-title">' . esc_html__( 'Dettagli del tour', 'igs-ecommerce' ) . '</span>';
        echo '<span class="igs-tour-layout__sidebar-subtitle">' . esc_html__( 'Cosa è incluso', 'igs-ecommerce' ) . '</span>';
        echo '</div>';
        echo '</header>';
        echo '<div class="igs-tour-layout__price" aria-live="polite">' . $price_html . '</div>';

        if ( $installment_html ) {
            echo $installment_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if ( $duration ) {
            $label = _n( 'giorno', 'giorni', (int) $duration, 'igs-ecommerce' );
            echo '<div class="igs-tour-layout__fact">';
            echo '<span class="igs-tour-layout__fact-icon" aria-hidden="true"></span>';
            echo '<div class="igs-tour-layout__fact-copy">';
            echo '<span class="igs-tour-layout__fact-label">' . esc_html__( 'Durata', 'igs-ecommerce' ) . '</span>';
            echo '<span class="igs-tour-layout__fact-value">' . esc_html( $duration ) . ' ' . esc_html( $label ) . '</span>';
            echo '</div>';
            echo '</div>';
        }

        $services = [
            [
                'icon'  => 'ticket',
                'label' => __( 'Ingressi ai siti e giardini', 'igs-ecommerce' ),
            ],
            [
                'icon'  => 'hotel',
                'label' => __( 'Pernottamento incluso', 'igs-ecommerce' ),
            ],
            [
                'icon'  => 'transfer',
                'label' => __( 'Trasferimenti in loco', 'igs-ecommerce' ),
            ],
            [
                'icon'  => 'meal',
                'label' => __( 'Pasti da itinerario', 'igs-ecommerce' ),
            ],
            [
                'icon'  => 'guide',
                'label' => __( 'Guida locale', 'igs-ecommerce' ),
            ],
        ];

        echo '<ul class="igs-tour-layout__services" role="list">';

        foreach ( $services as $service ) {
            echo '<li class="igs-tour-service igs-tour-service--' . esc_attr( $service['icon'] ) . '">';
            echo '<span class="igs-tour-service__icon" aria-hidden="true"></span>';
            echo '<span class="igs-tour-service__text">' . esc_html( $service['label'] ) . '</span>';
            echo '</li>';
        }

        echo '</ul>';

        echo '<footer class="igs-tour-layout__footer">';
        echo '<span class="igs-tour-layout__footer-label">' . esc_html__( 'Prossima partenza da', 'igs-ecommerce' ) . '</span>';
        echo '<span class="igs-tour-layout__country">' . esc_html( $country_label ) . '</span>';
        echo '</footer>';
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

        $display_price = wc_get_price_to_display( $product );

        if ( $display_price > 0 ) {
            return wc_price( (float) $display_price, [ 'decimals' => 0 ] );
        }

        return '<span class="igs-tour-price--placeholder">' . esc_html__( 'info in arrivo', 'igs-ecommerce' ) . '</span>';
    }
}
