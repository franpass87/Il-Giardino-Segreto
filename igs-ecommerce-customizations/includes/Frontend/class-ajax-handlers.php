<?php
/**
 * AJAX endpoints used by the booking modal.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use WC_Product;
use WC_Product_Variation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle booking modal AJAX requests.
 */
class Ajax_Handlers {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'wp_ajax_igs_add_to_cart', [ __CLASS__, 'add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_igs_add_to_cart', [ __CLASS__, 'add_to_cart' ] );
        add_action( 'wp_ajax_igs_tour_info', [ __CLASS__, 'send_info_request' ] );
        add_action( 'wp_ajax_nopriv_igs_tour_info', [ __CLASS__, 'send_info_request' ] );
    }

    /**
     * Add the selected tour to the WooCommerce cart.
     */
    public static function add_to_cart(): void {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'igs_add_to_cart' ) ) {
            wp_send_json_error( [ 'message' => __( 'Verifica di sicurezza fallita.', 'igs-ecommerce' ) ], 400 );
        }

        $product_id   = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
        $quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( wp_unslash( $_POST['quantity'] ) ) ) : 1;
        $variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( [ 'message' => __( 'Prodotto non valido.', 'igs-ecommerce' ) ], 400 );
        }

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( [ 'message' => __( 'WooCommerce non è attivo.', 'igs-ecommerce' ) ], 500 );
        }

        $product = wc_get_product( $product_id );

        if ( ! $product instanceof WC_Product ) {
            wp_send_json_error( [ 'message' => __( 'Prodotto non disponibile.', 'igs-ecommerce' ) ], 404 );
        }

        if ( apply_filters( 'igs_booking_should_empty_cart', false, $product ) ) {
            WC()->cart->empty_cart();
        }

        $added = false;

        if ( $variation_id > 0 ) {
            $variation = wc_get_product( $variation_id );

            if ( ! $variation instanceof WC_Product_Variation || $variation->get_parent_id() !== $product_id ) {
                wp_send_json_error( [ 'message' => __( 'Variazione non valida.', 'igs-ecommerce' ) ], 400 );
            }

            if ( ! $variation->is_in_stock() ) {
                wp_send_json_error( [ 'message' => __( 'La combinazione selezionata non è disponibile.', 'igs-ecommerce' ) ], 400 );
            }

            $added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
        } else {
            if ( ! $product->is_in_stock() ) {
                wp_send_json_error( [ 'message' => __( 'Il tour non è attualmente disponibile.', 'igs-ecommerce' ) ], 400 );
            }

            $added = WC()->cart->add_to_cart( $product_id, $quantity );
        }

        if ( $added ) {
            wp_send_json_success( [ 'message' => __( 'Prodotto aggiunto al carrello.', 'igs-ecommerce' ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Impossibile aggiungere il prodotto al carrello.', 'igs-ecommerce' ) ] );
    }

    /**
     * Send the information request email.
     */
    public static function send_info_request(): void {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'igs_tour_info' ) ) {
            wp_send_json_error( [ 'message' => __( 'Verifica di sicurezza fallita.', 'igs-ecommerce' ) ], 400 );
        }

        $tour_id = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
        $name    = isset( $_POST['info_name'] ) ? sanitize_text_field( wp_unslash( $_POST['info_name'] ) ) : '';
        $email   = isset( $_POST['info_email'] ) ? sanitize_email( wp_unslash( $_POST['info_email'] ) ) : '';
        $comment = isset( $_POST['info_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['info_comment'] ) ) : '';

        if ( ! $tour_id || '' === $name || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Per favore, compila correttamente nome ed email.', 'igs-ecommerce' ) ], 400 );
        }

        $product    = wc_get_product( $tour_id );
        $tour_title = $product ? $product->get_title() : __( 'Tour non specificato', 'igs-ecommerce' );
        $admin_mail = get_option( 'admin_email' );

        $subject = sprintf( __( 'Richiesta informazioni per il tour: %s', 'igs-ecommerce' ), $tour_title );

        $body  = '<h2>' . esc_html__( 'Nuova Richiesta Informazioni', 'igs-ecommerce' ) . '</h2>';
        $body .= '<p>' . sprintf( esc_html__( 'Hai ricevuto una nuova richiesta per il tour: %s', 'igs-ecommerce' ), '<strong>' . esc_html( $tour_title ) . '</strong>' ) . '</p>';
        $body .= '<ul>';
        $body .= '<li><strong>' . esc_html__( 'Nome', 'igs-ecommerce' ) . ':</strong> ' . esc_html( $name ) . '</li>';
        $body .= '<li><strong>' . esc_html__( 'Email', 'igs-ecommerce' ) . ':</strong> ' . esc_html( $email ) . '</li>';
        $body .= '</ul>';

        if ( '' !== $comment ) {
            $body .= '<h4>' . esc_html__( 'Messaggio', 'igs-ecommerce' ) . ':</h4>';
            $body .= '<p>' . nl2br( esc_html( $comment ) ) . '</p>';
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . $admin_mail . '>',
            'Reply-To: ' . $name . ' <' . $email . '>',
        ];

        $sent = wp_mail( $admin_mail, $subject, '<html><body>' . $body . '</body></html>', $headers );

        if ( $sent ) {
            wp_send_json_success( [ 'message' => __( 'Email inviata con successo.', 'igs-ecommerce' ) ] );
        }

        wp_send_json_error( [ 'message' => __( "Impossibile inviare l'email. Riprova più tardi.", 'igs-ecommerce' ) ], 500 );
    }
}
