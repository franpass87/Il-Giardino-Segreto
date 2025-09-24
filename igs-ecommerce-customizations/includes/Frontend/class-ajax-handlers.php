<?php
/**
 * AJAX endpoints used by the booking modal.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;
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

        $product = wc_get_product( $tour_id );

        if ( ! $product instanceof WC_Product || ! Helpers\is_tour_product( $product ) ) {
            wp_send_json_error( [ 'message' => __( 'Tour non disponibile.', 'igs-ecommerce' ) ], 404 );
        }

        self::enforce_info_rate_limit();

        $tour_title = $product->get_title();
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

        $recipients = self::determine_info_recipients( $admin_mail, $tour_id, $name, $email, $comment );

        if ( empty( $recipients ) ) {
            wp_send_json_error( [ 'message' => __( "Impossibile inviare l'email. Riprova più tardi.", 'igs-ecommerce' ) ], 500 );
        }

        $headers   = [ 'Content-Type: text/html; charset=UTF-8' ];
        $from_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $from_mail = sanitize_email( $admin_mail );

        if ( $from_mail && is_email( $from_mail ) ) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_mail . '>';
        }

        if ( is_email( $email ) ) {
            $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
        }

        $sent = wp_mail( $recipients, $subject, '<html><body>' . $body . '</body></html>', $headers );

        if ( $sent ) {
            wp_send_json_success( [ 'message' => __( 'Email inviata con successo.', 'igs-ecommerce' ) ] );
        }

        wp_send_json_error( [ 'message' => __( "Impossibile inviare l'email. Riprova più tardi.", 'igs-ecommerce' ) ], 500 );
    }

    /**
     * Enforce a configurable rate limit for information requests.
     */
    private static function enforce_info_rate_limit(): void {
        $defaults = [
            'window'       => defined( 'MINUTE_IN_SECONDS' ) ? 10 * MINUTE_IN_SECONDS : 600,
            'max_requests' => 5,
        ];
        $settings = apply_filters( 'igs_tour_info_rate_limit', $defaults );

        $window       = isset( $settings['window'] ) ? max( 1, (int) $settings['window'] ) : $defaults['window'];
        $max_requests = isset( $settings['max_requests'] ) ? (int) $settings['max_requests'] : $defaults['max_requests'];

        if ( $max_requests <= 0 ) {
            return;
        }

        $key = self::resolve_rate_limit_key();

        if ( null === $key ) {
            return;
        }

        $transient_key = 'igs_info_rl_' . md5( $key );
        $attempts      = (int) get_transient( $transient_key );

        if ( $attempts >= $max_requests ) {
            wp_send_json_error( [ 'message' => __( 'Hai raggiunto il limite di richieste. Riprova più tardi.', 'igs-ecommerce' ) ], 429 );
        }

        set_transient( $transient_key, $attempts + 1, $window );
    }

    /**
     * Build the rate limit key using either the current user or the client IP.
     */
    private static function resolve_rate_limit_key(): ?string {
        if ( is_user_logged_in() ) {
            return 'user_' . get_current_user_id();
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if ( '' === $ip ) {
            return null;
        }

        $ip = filter_var( wp_unslash( $ip ), FILTER_VALIDATE_IP );

        if ( false === $ip ) {
            return null;
        }

        return 'ip_' . $ip;
    }

    /**
     * Determine the recipient list for the tour info email.
     *
     * @param string $default Default admin email.
     * @param int    $tour_id Tour identifier.
     * @param string $name    Requester name.
     * @param string $email   Requester email.
     * @param string $comment Optional message.
     *
     * @return array<int,string>
     */
    private static function determine_info_recipients( string $default, int $tour_id, string $name, string $email, string $comment ): array {
        $recipient = apply_filters( 'igs_tour_info_recipient', $default, $tour_id, $name, $email, $comment );
        $candidate = [];

        foreach ( (array) $recipient as $item ) {
            $sanitized = sanitize_email( $item );

            if ( $sanitized && is_email( $sanitized ) ) {
                $candidate[] = $sanitized;
            }
        }

        if ( empty( $candidate ) ) {
            $fallback = sanitize_email( $default );

            if ( $fallback && is_email( $fallback ) ) {
                $candidate[] = $fallback;
            }
        }

        return array_values( array_unique( $candidate ) );
    }
}
