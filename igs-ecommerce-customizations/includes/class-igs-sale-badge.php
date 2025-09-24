<?php
/**
 * Discount badge customization.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Replace the default sale badge with discount percentage.
 */
class Sale_Badge {
    /**
     * Settings handler.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Register filters.
     */
    public function init(): void {
        add_filter( 'woocommerce_sale_flash', [ $this, 'render_badge' ], 10, 3 );
    }

    /**
     * Render badge with percentage when possible.
     *
     * @param string      $html    Original HTML.
     * @param WC_Product  $product Product object.
     * @param string      $context Context.
     * @return string
     */
    public function render_badge( string $html, $post, $product ): string {
        if ( ! $product instanceof WC_Product ) {
            return $html;
        }

        $regular_price = $this->get_regular_price( $product );
        $sale_price    = $this->get_sale_price( $product );

        if ( $regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price ) {
            return sprintf( '<span class="igs-badge igs-badge--sale">%s</span>', esc_html__( 'In offerta', 'igs-ecommerce' ) );
        }

        $discount = round( ( 1 - ( $sale_price / $regular_price ) ) * 100 );
        $discount = max( 1, $discount );

        return sprintf(
            '<span class="igs-badge igs-badge--sale">-%s%%</span>',
            esc_html( $discount )
        );
    }

    /**
     * Retrieve base regular price.
     */
    private function get_regular_price( WC_Product $product ): float {
        if ( $product->is_type( 'variable' ) ) {
            return (float) $product->get_variation_regular_price( 'max', true );
        }

        if ( $product->is_type( 'grouped' ) ) {
            $prices = [];
            foreach ( $product->get_children() as $child_id ) {
                $child = wc_get_product( $child_id );
                if ( $child ) {
                    $prices[] = (float) $child->get_regular_price();
                }
            }
            return $prices ? max( $prices ) : 0.0;
        }

        return (float) $product->get_regular_price();
    }

    /**
     * Retrieve sale price.
     */
    private function get_sale_price( WC_Product $product ): float {
        if ( $product->is_type( 'variable' ) ) {
            return (float) $product->get_variation_sale_price( 'min', true );
        }

        if ( $product->is_type( 'grouped' ) ) {
            $prices = [];
            foreach ( $product->get_children() as $child_id ) {
                $child = wc_get_product( $child_id );
                if ( $child && $child->is_on_sale() ) {
                    $prices[] = (float) $child->get_sale_price();
                }
            }
            return $prices ? min( $prices ) : 0.0;
        }

        return (float) $product->get_sale_price();
    }
}
