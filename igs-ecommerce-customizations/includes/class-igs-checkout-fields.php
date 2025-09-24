<?php
/**
 * Additional checkout fields feature.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add fiscal code, VAT and gift message fields to checkout.
 */
class Checkout_Fields {
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
     * Register hooks.
     */
    public function init(): void {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'register_fields' ] );
        add_action( 'woocommerce_checkout_process', [ $this, 'validate_fields' ] );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_fields' ] );
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'render_admin_data' ] );
        add_filter( 'woocommerce_email_order_meta_fields', [ $this, 'append_email_meta' ], 10, 3 );
    }

    /**
     * Add fields to checkout form.
     *
     * @param array<string, mixed> $fields Checkout fields.
     * @return array<string, mixed>
     */
    public function register_fields( array $fields ): array {
        $fields['billing']['billing_codice_fiscale'] = [
            'type'        => 'text',
            'label'       => __( 'Codice Fiscale', 'igs-ecommerce' ),
            'placeholder' => __( 'Inserisci il tuo Codice Fiscale', 'igs-ecommerce' ),
            'required'    => (bool) $this->settings->get( 'require_codice_fiscale', 1 ),
            'class'       => [ 'form-row-wide' ],
            'priority'    => 120,
        ];

        $fields['billing']['billing_partita_iva'] = [
            'type'        => 'text',
            'label'       => __( 'Partita IVA', 'igs-ecommerce' ),
            'placeholder' => __( 'Inserisci la Partita IVA', 'igs-ecommerce' ),
            'required'    => (bool) $this->settings->get( 'require_partita_iva', 0 ),
            'class'       => [ 'form-row-wide' ],
            'priority'    => 121,
        ];

        if ( $this->settings->is_enabled( 'gift_message' ) ) {
            $fields['order']['igs_gift_message'] = [
                'type'        => 'textarea',
                'label'       => __( 'Messaggio regalo', 'igs-ecommerce' ),
                'placeholder' => __( 'Scrivi un messaggio da includere nel pacco (facoltativo)', 'igs-ecommerce' ),
                'required'    => false,
                'class'       => [ 'form-row-wide' ],
                'priority'    => 22,
            ];
        }

        return $fields;
    }

    /**
     * Validate custom fields.
     */
    public function validate_fields(): void {
        $requires_cf  = (bool) $this->settings->get( 'require_codice_fiscale', 1 );
        $requires_piva = (bool) $this->settings->get( 'require_partita_iva', 0 );

        $codice_fiscale = isset( $_POST['billing_codice_fiscale'] ) ? wc_clean( wp_unslash( $_POST['billing_codice_fiscale'] ) ) : '';
        $partita_iva    = isset( $_POST['billing_partita_iva'] ) ? wc_clean( wp_unslash( $_POST['billing_partita_iva'] ) ) : '';
        $gift_message   = isset( $_POST['igs_gift_message'] ) ? wc_clean( wp_unslash( $_POST['igs_gift_message'] ) ) : '';

        if ( $requires_cf && empty( $codice_fiscale ) ) {
            wc_add_notice( __( 'Il Codice Fiscale è obbligatorio.', 'igs-ecommerce' ), 'error' );
        }

        if ( ! empty( $codice_fiscale ) && ! $this->is_valid_codice_fiscale( $codice_fiscale ) ) {
            wc_add_notice( __( 'Il Codice Fiscale non sembra valido.', 'igs-ecommerce' ), 'error' );
        }

        if ( $requires_piva && empty( $partita_iva ) ) {
            wc_add_notice( __( 'La Partita IVA è obbligatoria.', 'igs-ecommerce' ), 'error' );
        }

        if ( ! empty( $partita_iva ) && ! preg_match( '/^[0-9]{11}$/', $partita_iva ) ) {
            wc_add_notice( __( 'La Partita IVA deve contenere 11 cifre.', 'igs-ecommerce' ), 'error' );
        }

        if ( $this->settings->is_enabled( 'gift_message' ) && ! empty( $gift_message ) ) {
            $max = max( 20, (int) $this->settings->get( 'gift_message_max_length', 180 ) );
            if ( strlen( $gift_message ) > $max ) {
                wc_add_notice(
                    sprintf(
                        /* translators: %d: max length */
                        __( 'Il messaggio regalo può contenere al massimo %d caratteri.', 'igs-ecommerce' ),
                        $max
                    ),
                    'error'
                );
            }
        }
    }

    /**
     * Save custom field values to order meta.
     */
    public function save_fields( int $order_id ): void {
        if ( isset( $_POST['billing_codice_fiscale'] ) ) {
            update_post_meta( $order_id, '_billing_codice_fiscale', sanitize_text_field( wp_unslash( $_POST['billing_codice_fiscale'] ) ) );
        }

        if ( isset( $_POST['billing_partita_iva'] ) ) {
            update_post_meta( $order_id, '_billing_partita_iva', sanitize_text_field( wp_unslash( $_POST['billing_partita_iva'] ) ) );
        }

        if ( isset( $_POST['igs_gift_message'] ) ) {
            update_post_meta( $order_id, '_igs_gift_message', sanitize_textarea_field( wp_unslash( $_POST['igs_gift_message'] ) ) );
        }
    }

    /**
     * Display data in admin order screen.
     */
    public function render_admin_data( WC_Order $order ): void {
        $codice_fiscale = $order->get_meta( '_billing_codice_fiscale' );
        $partita_iva    = $order->get_meta( '_billing_partita_iva' );
        $gift_message   = $order->get_meta( '_igs_gift_message' );

        if ( $codice_fiscale ) {
            printf( '<p><strong>%s:</strong> %s</p>', esc_html__( 'Codice Fiscale', 'igs-ecommerce' ), esc_html( $codice_fiscale ) );
        }

        if ( $partita_iva ) {
            printf( '<p><strong>%s:</strong> %s</p>', esc_html__( 'Partita IVA', 'igs-ecommerce' ), esc_html( $partita_iva ) );
        }

        if ( $gift_message ) {
            printf( '<p><strong>%s:</strong> %s</p>', esc_html__( 'Messaggio regalo', 'igs-ecommerce' ), esc_html( $gift_message ) );
        }
    }

    /**
     * Append metadata to customer emails.
     *
     * @param array<string, array<string, string>> $fields Fields.
     * @param bool                                 $sent_to_admin Whether sent to admin.
     * @param WC_Order                              $order Order object.
     * @return array<string, array<string, string>>
     */
    public function append_email_meta( array $fields, bool $sent_to_admin, WC_Order $order ): array {
        $codice_fiscale = $order->get_meta( '_billing_codice_fiscale' );
        $partita_iva    = $order->get_meta( '_billing_partita_iva' );
        $gift_message   = $order->get_meta( '_igs_gift_message' );

        if ( $codice_fiscale ) {
            $fields['billing_codice_fiscale'] = [
                'label' => __( 'Codice Fiscale', 'igs-ecommerce' ),
                'value' => $codice_fiscale,
            ];
        }

        if ( $partita_iva ) {
            $fields['billing_partita_iva'] = [
                'label' => __( 'Partita IVA', 'igs-ecommerce' ),
                'value' => $partita_iva,
            ];
        }

        if ( $gift_message ) {
            $fields['igs_gift_message'] = [
                'label' => __( 'Messaggio regalo', 'igs-ecommerce' ),
                'value' => $gift_message,
            ];
        }

        return $fields;
    }

    /**
     * Validate Italian Codice Fiscale with basic pattern.
     */
    private function is_valid_codice_fiscale( string $value ): bool {
        $value = strtoupper( $value );

        if ( preg_match( '/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $value ) ) {
            return true;
        }

        return preg_match( '/^[0-9]{11}$/', $value ) === 1; // Accept numeric temporary codes.
    }
}
