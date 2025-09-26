<?php
/**
 * Shop archive tweaks.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adjust WooCommerce shop headings and layout for tours.
 */
class Shop_Page {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_filter( 'woocommerce_page_title', [ __CLASS__, 'filter_title' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        add_filter( 'woocommerce_return_to_shop_redirect', [ __CLASS__, 'filter_return_url' ] );
        add_filter( 'woocommerce_return_to_shop_text', [ __CLASS__, 'filter_return_text' ] );
        add_filter( 'woocommerce_breadcrumb_defaults', [ __CLASS__, 'breadcrumb_defaults' ] );
    }

    /**
     * Customise the shop page title.
     */
    public static function filter_title( string $title ): string {
        if ( is_shop() ) {
            return __( 'Destinazioni fuori dai sentieri battuti', 'igs-ecommerce' );
        }

        return $title;
    }

    /**
     * Enqueue minor CSS adjustments for the archive header.
     */
    public static function enqueue_styles(): void {
        if ( ! is_shop() ) {
            return;
        }

        wp_enqueue_style( 'igs-shop-page', Helpers\url( 'assets/css/shop-page.css' ), [], IGS_ECOMMERCE_VERSION );
    }

    /**
     * Change the redirect for the return to shop button.
     */
    public static function filter_return_url( string $url ): string {
        return home_url( '/' );
    }

    /**
     * Customise the return to shop label.
     */
    public static function filter_return_text( string $text ): string {
        return __( 'Ritorna al sito web', 'igs-ecommerce' );
    }

    /**
     * Customise breadcrumb markup to a compact pill-based layout.
     *
     * @param array<string,mixed> $defaults Existing defaults.
     */
    public static function breadcrumb_defaults( array $defaults ): array {
        if ( ! is_shop() ) {
            return $defaults;
        }

        $defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb igs-breadcrumb" aria-label="' . esc_attr__( 'Percorso di navigazione', 'igs-ecommerce' ) . '"><ol class="igs-breadcrumb__list">';
        $defaults['wrap_after']  = '</ol></nav>';
        $defaults['before']      = '<li class="igs-breadcrumb__item">';
        $defaults['after']       = '</li>';
        $defaults['delimiter']   = '<span class="igs-breadcrumb__separator" aria-hidden="true">/</span>';

        return $defaults;
    }
}
