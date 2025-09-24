<?php
/**
 * Product level metadata management.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WooCommerce product meta fields used by tours.
 */
class Product_Meta {
    /**
     * Hook everything up.
     */
    public static function init(): void {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'render_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'save_meta' ], 10, 1 );
    }

    /**
     * Enqueue admin assets when editing products.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'igs-admin-datepicker', Helpers\url( 'assets/css/admin-datepicker.css' ), [], IGS_ECOMMERCE_VERSION );
        wp_enqueue_style( 'igs-admin-product-meta', Helpers\url( 'assets/css/admin-product-meta.css' ), [], IGS_ECOMMERCE_VERSION );
        wp_enqueue_script( 'igs-admin-product-meta', Helpers\url( 'assets/js/admin-product-meta.js' ), [ 'jquery', 'jquery-ui-datepicker' ], IGS_ECOMMERCE_VERSION, true );

        wp_localize_script(
            'igs-admin-product-meta',
            'igsProductMeta',
            [
                'dateFormat'    => 'dd/mm/yy',
                'addLabel'      => __( 'Aggiungi', 'igs-ecommerce' ),
                'removeLabel'   => __( 'Rimuovi', 'igs-ecommerce' ),
                'startLabel'    => __( 'Partenza', 'igs-ecommerce' ),
                'endLabel'      => __( 'Ritorno', 'igs-ecommerce' ),
                'confirmRemove' => __( 'Eliminare questo intervallo di date?', 'igs-ecommerce' ),
            ]
        );
    }

    /**
     * Render the date ranges and tour country fields.
     */
    public static function render_fields(): void {
        global $post;

        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        $saved_ranges = get_post_meta( $post->ID, '_date_ranges', true );
        $country      = get_post_meta( $post->ID, '_paese_tour', true );

        if ( ! is_array( $saved_ranges ) ) {
            $saved_ranges = [];
        }

        echo '<div class="options_group igs-tour-product-options">';
        wp_nonce_field( 'igs_save_tour_product_meta', 'igs_tour_product_meta_nonce' );

        echo '<p class="form-field igs-date-ranges-field">';
        echo '<label>' . esc_html__( 'Date del tour', 'igs-ecommerce' ) . '</label>';
        echo '<button type="button" class="button igs-add-date-range">' . esc_html__( 'Aggiungi intervallo', 'igs-ecommerce' ) . '</button>';
        echo '</p>';

        echo '<div id="igs-date-ranges" class="igs-date-ranges">';

        foreach ( $saved_ranges as $range ) {
            $start = esc_attr( $range['start'] ?? '' );
            $end   = esc_attr( $range['end'] ?? '' );

            echo '<div class="igs-date-range-row">';
            echo '<input type="text" name="date_ranges[start][]" class="igs-date-field igs-date-start" value="' . $start . '" placeholder="' . esc_attr__( 'Partenza', 'igs-ecommerce' ) . '">';
            echo '<input type="text" name="date_ranges[end][]" class="igs-date-field igs-date-end" value="' . $end . '" placeholder="' . esc_attr__( 'Ritorno', 'igs-ecommerce' ) . '">';
            echo '<button type="button" class="button button-link igs-remove-date-range">' . esc_html__( 'Rimuovi', 'igs-ecommerce' ) . '</button>';
            echo '</div>';
        }

        echo '</div>';

        woocommerce_wp_text_input(
            [
                'id'          => '_paese_tour',
                'label'       => __( 'Paese del tour', 'igs-ecommerce' ),
                'placeholder' => __( 'Es. Italia', 'igs-ecommerce' ),
                'desc_tip'    => true,
                'description' => __( 'Inserisci il paese del tour', 'igs-ecommerce' ),
                'value'       => $country,
            ]
        );

        echo '</div>';
    }

    /**
     * Persist tour specific metadata.
     */
    public static function save_meta( int $post_id ): void {
        if ( empty( $_POST['igs_tour_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igs_tour_product_meta_nonce'] ) ), 'igs_save_tour_product_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $starts = isset( $_POST['date_ranges']['start'] ) && is_array( $_POST['date_ranges']['start'] ) ? wp_unslash( $_POST['date_ranges']['start'] ) : null;
        $ends   = isset( $_POST['date_ranges']['end'] ) && is_array( $_POST['date_ranges']['end'] ) ? wp_unslash( $_POST['date_ranges']['end'] ) : null;
        $ranges = Helpers\sanitize_date_ranges( $starts, $ends );

        if ( ! empty( $ranges ) ) {
            update_post_meta( $post_id, '_date_ranges', $ranges );
        } else {
            delete_post_meta( $post_id, '_date_ranges' );
        }

        if ( isset( $_POST['_paese_tour'] ) ) {
            $country = sanitize_text_field( wp_unslash( $_POST['_paese_tour'] ) );

            if ( '' !== $country ) {
                update_post_meta( $post_id, '_paese_tour', $country );
            } else {
                delete_post_meta( $post_id, '_paese_tour' );
            }
        }
    }
}
