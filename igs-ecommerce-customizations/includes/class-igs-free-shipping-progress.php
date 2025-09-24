<?php
/**
 * Free shipping progress bar feature.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

use WC;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display dynamic notice with progress towards free shipping.
 */
class Free_Shipping_Progress {
    /**
     * Settings instance.
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
     * Hook feature into WooCommerce.
     */
    public function init(): void {
        add_action( 'woocommerce_before_cart', [ $this, 'render_progress' ] );
        add_action( 'woocommerce_checkout_before_order_review', [ $this, 'render_progress' ] );
    }

    /**
     * Output notice and progress bar.
     */
    public function render_progress(): void {
        if ( ! WC()->cart ) {
            return;
        }

        $threshold = (float) $this->settings->get( 'free_shipping_threshold', 0 );

        if ( $threshold <= 0 ) {
            return;
        }

        $cart_total = (float) WC()->cart->get_cart_contents_total();

        if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
            $cart_total += (float) WC()->cart->get_cart_contents_tax();
        }

        $cart_total = max( 0, $cart_total );

        $remaining = $threshold - $cart_total;
        $remaining = max( 0, $remaining );

        $percentage = $threshold > 0 ? min( 100, ( $cart_total / $threshold ) * 100 ) : 0;

        $message = $remaining > 0
            ? $this->replace_placeholders( $this->settings->get( 'free_shipping_goal_message' ), $remaining, $threshold, $cart_total )
            : $this->replace_placeholders( $this->settings->get( 'free_shipping_success_message' ), 0, $threshold, $cart_total );

        wc_print_notice( wp_kses_post( $message ), $remaining > 0 ? 'notice' : 'success' );

        echo '<div class="igs-progress-wrapper" aria-hidden="true">';
        printf(
            '<div class="igs-progress"><span class="igs-progress__bar" style="width:%1$.2f%%"></span></div>',
            $percentage
        );
        echo '</div>';
    }

    /**
     * Replace message placeholders with dynamic values.
     */
    private function replace_placeholders( string $message, float $remaining, float $threshold, float $cart_total ): string {
        $replacements = [
            '{remaining}' => wc_price( $remaining ),
            '{threshold}' => wc_price( $threshold ),
            '{total}'     => wc_price( $cart_total ),
        ];

        return strtr( $message, $replacements );
    }
}
