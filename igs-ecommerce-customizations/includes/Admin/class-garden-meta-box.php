<?php
/**
 * Additional garden tour meta box.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle the "Dettagli Garden Tour" meta box.
 */
class Garden_Meta_Box {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_box' ] );
        add_action( 'save_post_product', [ __CLASS__, 'save_meta' ] );
    }

    /**
     * Register the meta box.
     */
    public static function register_meta_box(): void {
        add_meta_box(
            'garden_details_meta',
            __( 'Dettagli Garden Tour', 'igs-ecommerce' ),
            [ __CLASS__, 'render' ],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render the form controls.
     */
    public static function render( \WP_Post $post ): void {
        $fields = [
            'protagonista_tour'   => [ 'label' => __( 'Pianta', 'igs-ecommerce' ), 'type' => 'text' ],
            'livello_culturale'   => [ 'label' => __( 'Cultura (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
            'livello_passeggiata' => [ 'label' => __( 'Passeggiata (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
            'livello_piuma'       => [ 'label' => __( 'Comfort (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
            'livello_esclusivita' => [ 'label' => __( 'Esclusività (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
        ];

        wp_nonce_field( 'igs_save_garden_meta', 'igs_garden_meta_nonce' );

        echo '<table class="form-table igs-garden-meta-table">';

        foreach ( $fields as $key => $field ) {
            $value = get_post_meta( $post->ID, '_' . $key, true );

            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
            echo '<td>';

            if ( 'number' === $field['type'] ) {
                printf(
                    '<input type="number" id="%1$s" name="%1$s" value="%2$s" min="1" max="5" step="1" class="small-text" />',
                    esc_attr( $key ),
                    esc_attr( $value )
                );
            } else {
                printf(
                    '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />',
                    esc_attr( $key ),
                    esc_attr( $value )
                );
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Save the garden meta values.
     */
    public static function save_meta( int $post_id ): void {
        if ( empty( $_POST['igs_garden_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igs_garden_meta_nonce'] ) ), 'igs_save_garden_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $text_fields    = [ 'protagonista_tour' ];
        $numeric_fields = [ 'livello_culturale', 'livello_passeggiata', 'livello_piuma', 'livello_esclusivita' ];

        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

                if ( '' !== $value ) {
                    update_post_meta( $post_id, '_' . $field, $value );
                } else {
                    delete_post_meta( $post_id, '_' . $field );
                }
            }
        }

        foreach ( $numeric_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = (int) sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

                if ( $value >= 1 && $value <= 5 ) {
                    update_post_meta( $post_id, '_' . $field, $value );
                } else {
                    delete_post_meta( $post_id, '_' . $field );
                }
            }
        }
    }
}
