<?php
/**
 * Portfolio meta boxes.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manage portfolio start/end date meta box.
 */
class Portfolio_Meta {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_box' ] );
        add_action( 'save_post_portfolio', [ __CLASS__, 'save_meta' ] );
    }

    /**
     * Register the meta box.
     */
    public static function register_meta_box(): void {
        add_meta_box(
            'date_tour_meta',
            __( 'Date del tour', 'igs-ecommerce' ),
            [ __CLASS__, 'render' ],
            'portfolio',
            'side',
            'default'
        );
    }

    /**
     * Render the controls.
     */
    public static function render( \WP_Post $post ): void {
        $departure = get_post_meta( $post->ID, '_data_partenza', true );
        $return    = get_post_meta( $post->ID, '_data_arrivo', true );

        wp_nonce_field( 'igs_save_portfolio_dates', 'igs_portfolio_dates_nonce' );

        echo '<p><label for="igs_data_partenza">' . esc_html__( 'Data partenza', 'igs-ecommerce' ) . '</label>';
        echo '<input type="date" id="igs_data_partenza" name="data_partenza" value="' . esc_attr( $departure ) . '" class="widefat" /></p>';

        echo '<p><label for="igs_data_arrivo">' . esc_html__( 'Data arrivo', 'igs-ecommerce' ) . '</label>';
        echo '<input type="date" id="igs_data_arrivo" name="data_arrivo" value="' . esc_attr( $return ) . '" class="widefat" /></p>';
    }

    /**
     * Save the portfolio dates.
     */
    public static function save_meta( int $post_id ): void {
        if ( empty( $_POST['igs_portfolio_dates_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igs_portfolio_dates_nonce'] ) ), 'igs_save_portfolio_dates' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( [ 'data_partenza', 'data_arrivo' ] as $field ) {
            if ( isset( $_POST[ $field ] ) && '' !== $_POST[ $field ] ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            } else {
                delete_post_meta( $post_id, '_' . $field );
            }
        }
    }
}
