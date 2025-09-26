<?php
/**
 * Booking modal for tour products.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders and powers the CTA modal used to book a tour.
 */
class Booking_Modal {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_modal' ] );
    }

    /**
     * Load CSS/JS when viewing a tour product.
     */
    public static function enqueue_assets(): void {
        if ( ! is_product() ) {
            return;
        }

        $product = wc_get_product( get_queried_object_id() );

        if ( ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        wp_enqueue_style( 'igs-booking-modal', Helpers\url( 'assets/css/booking-modal.css' ), [], IGS_ECOMMERCE_VERSION );
        wp_enqueue_script( 'igs-booking-modal', Helpers\url( 'assets/js/booking-modal.js' ), [ 'jquery' ], IGS_ECOMMERCE_VERSION, true );

        $variations = self::get_pricing_options( $product );
        $currency   = get_woocommerce_currency_symbol();

        $decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
        $locale   = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

        wp_localize_script(
            'igs-booking-modal',
            'igsBookingModal',
            [
                'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
                'addToCartNonce'  => wp_create_nonce( 'igs_add_to_cart' ),
                'infoNonce'       => wp_create_nonce( 'igs_tour_info' ),
                'checkoutUrl'     => wc_get_checkout_url(),
                'productId'       => $product ? $product->get_id() : 0,
                'currency'        => $currency,
                'decimals'        => $decimals,
                'variations'      => $variations,
                'locale'          => $locale,
                'i18n'            => [
                    'open'           => __( 'Scopri e Prenota', 'igs-ecommerce' ),
                    'close'          => __( 'Chiudi finestra', 'igs-ecommerce' ),
                    'selectOption'   => __( "Per favore, seleziona un'opzione prima di procedere.", 'igs-ecommerce' ),
                    'loading'        => __( 'Attendi...', 'igs-ecommerce' ),
                    'addToCart'      => __( 'Procedi al Checkout', 'igs-ecommerce' ),
                    'addError'       => __( 'Si è verificato un errore. Riprova.', 'igs-ecommerce' ),
                    'networkError'   => __( 'Errore di comunicazione. Riprova.', 'igs-ecommerce' ),
                    'infoTitle'      => __( 'Richiedi informazioni', 'igs-ecommerce' ),
                    'infoButton'     => __( 'Richiedi Informazioni', 'igs-ecommerce' ),
                    'backButton'     => __( 'Torna alla Prenotazione', 'igs-ecommerce' ),
                    'infoSuccess'    => __( 'Grazie! La tua richiesta è stata inviata. Ti risponderemo al più presto.', 'igs-ecommerce' ),
                    'infoError'      => __( 'Errore. Assicurati di aver compilato tutti i campi.', 'igs-ecommerce' ),
                    'infoNetwork'    => __( 'Si è verificato un errore di comunicazione con il server.', 'igs-ecommerce' ),
                    'quantityLabel'  => __( 'Numero persone', 'igs-ecommerce' ),
                    'increase'       => __( 'Aumenta quantità', 'igs-ecommerce' ),
                    'decrease'       => __( 'Diminuisci quantità', 'igs-ecommerce' ),
                    'send'           => __( 'Invia Richiesta', 'igs-ecommerce' ),
                    'sending'        => __( 'Invio in corso...', 'igs-ecommerce' ),
                    'name'           => __( 'Nome', 'igs-ecommerce' ),
                    'email'          => __( 'Email', 'igs-ecommerce' ),
                    'message'        => __( 'La tua richiesta (opzionale)', 'igs-ecommerce' ),
                    'validationInfo' => __( 'Per favore, compila nome ed email.', 'igs-ecommerce' ),
                ],
            ]
        );
    }

    /**
     * Print modal markup in the footer when needed.
     */
    public static function render_modal(): void {
        if ( ! is_product() ) {
            return;
        }

        global $product;

        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            return;
        }

        $options = self::get_pricing_options( $product );

        if ( empty( $options ) ) {
            return;
        }

        $product_title = $product->get_title();

        echo '<div class="igs-booking-cta" id="igs-booking-cta">';
        echo '<span class="igs-booking-cta__helper" aria-hidden="true">' . esc_html__( 'Prenotazione rapida', 'igs-ecommerce' ) . '</span>';
        echo '<button type="button" class="igs-booking-cta__button" data-modal-target="igs-booking-modal" aria-haspopup="dialog" aria-controls="igs-booking-modal" aria-expanded="false">' . esc_html__( 'Scopri e Prenota', 'igs-ecommerce' ) . '</button>';
        echo '</div>';

        echo '<div class="igs-booking-modal" id="igs-booking-modal" aria-hidden="true">';
        echo '<div class="igs-booking-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="igs-booking-modal-title">';
        echo '<div class="igs-booking-modal__header">';
        echo '<h3 id="igs-booking-modal-title">' . esc_html( $product_title ) . '</h3>';
        echo '<button type="button" class="igs-booking-modal__close" aria-label="' . esc_attr__( 'Chiudi finestra', 'igs-ecommerce' ) . '">×</button>';
        echo '</div>';
        echo '<div class="igs-booking-modal__views">';

        self::render_booking_view( $product, $options );
        self::render_info_view( $product );

        echo '</div>'; // views
        echo '</div>'; // dialog
        echo '</div>'; // modal
    }

    /**
     * Render the booking view contents.
     */
    private static function render_booking_view( WC_Product $product, array $options ): void {
        echo '<div class="igs-booking-view" data-view="booking">';
        echo '<form id="igs-booking-form" class="igs-booking-form" novalidate>';

        echo '<fieldset class="igs-booking-form__group">';
        echo '<legend id="igs-booking-options-label">' . esc_html__( 'Scegli la tua opzione', 'igs-ecommerce' ) . '</legend>';
        $options_json = wp_json_encode( $options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

        if ( false === $options_json ) {
            $options_json = '[]';
        }

        echo '<div class="igs-booking-form__options" id="igs-booking-options" role="radiogroup" aria-labelledby="igs-booking-options-label" data-options="' . esc_attr( $options_json ) . '"></div>';
        echo '</fieldset>';

        echo '<fieldset class="igs-booking-form__group igs-booking-form__group--quantity">';
        echo '<legend>' . esc_html__( 'Numero persone', 'igs-ecommerce' ) . '</legend>';
        echo '<div class="igs-booking-quantity">';
        echo '<button type="button" class="igs-booking-quantity__button" data-direction="down" aria-label="' . esc_attr__( 'Diminuisci quantità', 'igs-ecommerce' ) . '">−</button>';
        echo '<input type="text" name="quantity" id="igs-booking-quantity" value="1" readonly />';
        echo '<button type="button" class="igs-booking-quantity__button" data-direction="up" aria-label="' . esc_attr__( 'Aumenta quantità', 'igs-ecommerce' ) . '">+</button>';
        echo '</div>';
        echo '</fieldset>';

        echo '<div class="igs-booking-total" id="igs-booking-total" role="status" aria-live="polite">' . wp_kses_post( wc_price( 0 ) ) . '</div>';
        echo '</form>';

        echo '<div class="igs-booking-modal__footer">';
        echo '<button type="button" class="igs-booking-primary" id="igs-booking-submit">' . esc_html__( 'Procedi al Checkout', 'igs-ecommerce' ) . '</button>';
        echo '<button type="button" class="igs-booking-secondary" data-view-target="info">' . esc_html__( 'Richiedi Informazioni', 'igs-ecommerce' ) . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render the information request view.
     */
    private static function render_info_view( WC_Product $product ): void {
        echo '<div class="igs-booking-view" data-view="info" hidden>';
        echo '<div class="igs-booking-info-message" id="igs-booking-info-success" hidden role="status" aria-live="polite" tabindex="-1">' . esc_html__( 'Grazie! La tua richiesta è stata inviata. Ti risponderemo al più presto.', 'igs-ecommerce' ) . '</div>';
        echo '<form id="igs-info-form" class="igs-booking-form" novalidate>';
        echo '<input type="hidden" name="tour_id" value="' . esc_attr( $product->get_id() ) . '">';

        echo '<label class="igs-booking-form__label" for="igs-info-name">' . esc_html__( 'Nome', 'igs-ecommerce' ) . '</label>';
        echo '<input type="text" id="igs-info-name" name="info_name" required />';

        echo '<label class="igs-booking-form__label" for="igs-info-email">' . esc_html__( 'Email', 'igs-ecommerce' ) . '</label>';
        echo '<input type="email" id="igs-info-email" name="info_email" required />';

        echo '<label class="igs-booking-form__label" for="igs-info-message">' . esc_html__( 'La tua richiesta (opzionale)', 'igs-ecommerce' ) . '</label>';
        echo '<textarea id="igs-info-message" name="info_comment"></textarea>';
        echo '</form>';

        echo '<div class="igs-booking-modal__footer">';
        echo '<button type="button" class="igs-booking-primary" id="igs-info-submit">' . esc_html__( 'Invia Richiesta', 'igs-ecommerce' ) . '</button>';
        echo '<button type="button" class="igs-booking-secondary" data-view-target="booking">' . esc_html__( 'Torna alla Prenotazione', 'igs-ecommerce' ) . '</button>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Retrieve valid pricing options for the modal.
     *
     * @param WC_Product $product Product instance.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function get_pricing_options( WC_Product $product ): array {
        $options = [];

        if ( $product instanceof WC_Product_Variable ) {
            foreach ( $product->get_available_variations() as $variation_data ) {
                $variation = wc_get_product( $variation_data['variation_id'] );

                if ( ! $variation instanceof WC_Product_Variation || ! $variation->is_in_stock() ) {
                    continue;
                }

                $display_price = (float) wc_get_price_to_display( $variation );

                if ( $display_price <= 0 ) {
                    continue;
                }

                $label = wc_get_formatted_variation( $variation, true, false, false );

                if ( '' === $label ) {
                    $label = $variation->get_name();
                }

                $attributes = [];

                foreach ( $variation->get_attributes() as $key => $value ) {
                    $sanitized_key = sanitize_key( $key );

                    if ( '' === $sanitized_key ) {
                        continue;
                    }

                    $attributes[ wc_variation_attribute_name( $sanitized_key ) ] = is_scalar( $value ) ? wc_clean( (string) $value ) : '';
                }

                $options[] = [
                    'id'    => $variation->get_id(),
                    'label' => $label,
                    'price' => $display_price,
                    'price_text' => wp_strip_all_tags( wc_price( $display_price ) ),
                    'attributes' => $attributes,
                ];
            }
        } elseif ( $product instanceof WC_Product_Simple ) {
            $display_price = (float) wc_get_price_to_display( $product );

            if ( $display_price > 0 ) {
                $options[] = [
                    'id'    => 0,
                    'label' => __( 'Opzione unica', 'igs-ecommerce' ),
                    'price' => $display_price,
                    'price_text' => wp_strip_all_tags( wc_price( $display_price ) ),
                    'attributes' => [],
                ];
            }
        }

        return $options;
    }

}
