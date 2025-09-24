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
        add_action( 'template_redirect', [ __CLASS__, 'remove_breadcrumb' ] );
        add_filter( 'woocommerce_page_title', [ __CLASS__, 'filter_title' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        add_filter( 'woocommerce_return_to_shop_redirect', [ __CLASS__, 'filter_return_url' ] );
        add_filter( 'woocommerce_return_to_shop_text', [ __CLASS__, 'filter_return_text' ] );
    }

    /**
     * Remove the breadcrumb on the main shop archive.
     */
    public static function remove_breadcrumb(): void {
        if ( is_shop() ) {
            remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
        }
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
}
