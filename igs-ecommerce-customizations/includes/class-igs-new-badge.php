<?php
/**
 * New product badge feature.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display "new" badge for recently published products.
 */
class New_Badge {
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
     * Attach hooks for output.
     */
    public function init(): void {
        add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'render_loop_badge' ], 8 );
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_single_badge' ], 6 );
    }

    /**
     * Render badge in product loop.
     */
    public function render_loop_badge(): void {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        if ( ! $this->should_display_badge( $product ) ) {
            return;
        }

        printf(
            '<span class="igs-badge igs-badge--new">%s</span>',
            esc_html( $this->settings->get( 'new_badge_label', __( 'Novità', 'igs-ecommerce' ) ) )
        );
    }

    /**
     * Render badge on single product page.
     */
    public function render_single_badge(): void {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        if ( ! $this->should_display_badge( $product ) ) {
            return;
        }

        printf(
            '<span class="igs-badge igs-badge--new igs-badge--single">%s</span>',
            esc_html( $this->settings->get( 'new_badge_label', __( 'Novità', 'igs-ecommerce' ) ) )
        );
    }

    /**
     * Check whether a product should show the new badge.
     */
    private function should_display_badge( WC_Product $product ): bool {
        $days = max( 1, (int) $this->settings->get( 'new_badge_days', 30 ) );
        $timestamp = $product->get_date_created() ? $product->get_date_created()->getTimestamp() : 0;

        if ( ! $timestamp ) {
            return false;
        }

        $limit = strtotime( sprintf( '-%d days', $days ) );

        return $timestamp >= $limit;
    }
}
