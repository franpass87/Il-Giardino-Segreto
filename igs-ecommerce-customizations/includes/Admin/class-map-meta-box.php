<?php
/**
 * Itinerary map meta box.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manage the itinerary map meta box and the geocoding AJAX helper.
 */
class Map_Meta_Box {
    /**
     * Hook everything up.
     */
    public static function init(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_box' ] );
        add_action( 'save_post_product', [ __CLASS__, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_igs_lookup_coordinates', [ __CLASS__, 'ajax_lookup_coordinates' ] );
    }

    /**
     * Register the meta box.
     */
    public static function register_meta_box(): void {
        add_meta_box(
            'mappa_tappe_meta',
            __( 'Mappa del viaggio', 'igs-ecommerce' ),
            [ __CLASS__, 'render' ],
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Enqueue admin assets required for the meta box.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_style( 'igs-admin-map-meta', Helpers\url( 'assets/css/admin-map-meta.css' ), [], IGS_ECOMMERCE_VERSION );
        wp_enqueue_script( 'igs-admin-map-meta', Helpers\url( 'assets/js/admin-map-meta.js' ), [ 'jquery' ], IGS_ECOMMERCE_VERSION, true );

        wp_localize_script(
            'igs-admin-map-meta',
            'igsMapMeta',
            [
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'igs_lookup_coordinates' ),
                'removeConfirm' => __( 'Eliminare questa tappa?', 'igs-ecommerce' ),
                'noResults'     => __( 'Località non trovata. Riprova con un nome più specifico.', 'igs-ecommerce' ),
                'emptyQuery'    => __( 'Inserisci il nome della località prima di cercare.', 'igs-ecommerce' ),
                'errorMessage'  => __( 'Impossibile recuperare le coordinate al momento. Riprovare più tardi.', 'igs-ecommerce' ),
                'fetching'      => __( 'Ricerca in corso…', 'igs-ecommerce' ),
                'findLabel'     => __( 'Trova coordinate', 'igs-ecommerce' ),
            ]
        );
    }

    /**
     * Render the meta box controls.
     */
    public static function render( \WP_Post $post ): void {
        $country = get_post_meta( $post->ID, '_mappa_paese', true );
        $stops   = get_post_meta( $post->ID, '_mappa_tappe', true );

        if ( ! is_array( $stops ) ) {
            $stops = [];
        }

        wp_nonce_field( 'igs_save_map_meta', 'igs_map_meta_nonce' );

        echo '<p><label for="mappa_paese">' . esc_html__( 'Paese', 'igs-ecommerce' ) . '</label>';
        echo '<input type="text" id="mappa_paese" name="mappa_paese" value="' . esc_attr( $country ) . '" class="widefat" /></p>';

        echo '<div id="igs-map-stops" class="igs-map-stops">';

        foreach ( $stops as $index => $stop ) {
            self::render_stop_fields( $index, $stop );
        }

        echo '</div>';

        echo '<p><button type="button" class="button igs-add-map-stop">' . esc_html__( 'Aggiungi tappa', 'igs-ecommerce' ) . '</button></p>';
        echo '<p class="description">' . sprintf( esc_html__( 'Shortcode: %s', 'igs-ecommerce' ), '<code>[mappa_viaggio id="' . (int) $post->ID . '"]</code>' ) . '</p>';

        // Template for JS cloning.
        echo '<script type="text/template" id="igs-map-stop-template">';
        self::render_stop_fields( '__index__', [] );
        echo '</script>';
    }

    /**
     * Render a single itinerary stop row.
     *
     * @param int|string $index Index for the row.
     * @param array      $stop  Stop data.
     */
    private static function render_stop_fields( $index, array $stop ): void {
        $name        = esc_attr( $stop['nome'] ?? '' );
        $lat         = esc_attr( $stop['lat'] ?? '' );
        $lon         = esc_attr( $stop['lon'] ?? '' );
        $description = esc_textarea( $stop['descrizione'] ?? '' );

        echo '<div class="igs-map-stop">';
        echo '<div class="igs-map-stop__header">';
        echo '<label>' . esc_html__( 'Nome località', 'igs-ecommerce' ) . '<input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][nome]" value="' . $name . '" class="widefat" /></label>';
        echo '<div class="igs-map-stop__actions">';
        echo '<button type="button" class="button igs-find-coordinates">' . esc_html__( 'Trova coordinate', 'igs-ecommerce' ) . '</button>';
        echo '<button type="button" class="button button-link-delete igs-remove-map-stop">' . esc_html__( 'Rimuovi tappa', 'igs-ecommerce' ) . '</button>';
        echo '</div>';
        echo '</div>';

        echo '<div class="igs-map-stop__coords">';
        echo '<label>' . esc_html__( 'Latitudine', 'igs-ecommerce' ) . '<input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lat]" value="' . $lat . '" class="widefat" /></label>';
        echo '<label>' . esc_html__( 'Longitudine', 'igs-ecommerce' ) . '<input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lon]" value="' . $lon . '" class="widefat" /></label>';
        echo '</div>';

        echo '<label>' . esc_html__( 'Descrizione', 'igs-ecommerce' ) . '<textarea name="mappa_tappe[' . esc_attr( $index ) . '][descrizione]" rows="2" class="widefat">' . $description . '</textarea></label>';
        echo '</div>';
    }

    /**
     * Save the itinerary map data.
     */
    public static function save_meta( int $post_id ): void {
        if ( empty( $_POST['igs_map_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['igs_map_meta_nonce'] ) ), 'igs_save_map_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $post_id ) ) {
            return;
        }

        $country = isset( $_POST['mappa_paese'] ) ? sanitize_text_field( wp_unslash( $_POST['mappa_paese'] ) ) : '';

        if ( '' !== $country ) {
            update_post_meta( $post_id, '_mappa_paese', $country );
        } else {
            delete_post_meta( $post_id, '_mappa_paese' );
        }

        $stops = [];

        if ( isset( $_POST['mappa_tappe'] ) && is_array( $_POST['mappa_tappe'] ) ) {
            foreach ( wp_unslash( $_POST['mappa_tappe'] ) as $stop ) {
                $name = isset( $stop['nome'] ) ? sanitize_text_field( $stop['nome'] ) : '';
                $lat  = isset( $stop['lat'] ) ? Helpers\normalize_latitude( $stop['lat'] ) : null;
                $lon  = isset( $stop['lon'] ) ? Helpers\normalize_longitude( $stop['lon'] ) : null;
                $desc = isset( $stop['descrizione'] ) ? sanitize_textarea_field( $stop['descrizione'] ) : '';

                if ( '' === $name ) {
                    continue;
                }

                $stops[] = [
                    'nome'        => $name,
                    'lat'         => $lat ?? '',
                    'lon'         => $lon ?? '',
                    'descrizione' => $desc,
                ];
            }
        }

        if ( ! empty( $stops ) ) {
            update_post_meta( $post_id, '_mappa_tappe', array_values( $stops ) );
        } else {
            delete_post_meta( $post_id, '_mappa_tappe' );
        }
    }

    /**
     * AJAX endpoint that proxies requests to the Nominatim API with caching and rate limiting.
     */
    public static function ajax_lookup_coordinates(): void {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'igs_lookup_coordinates' ) ) {
            wp_send_json_error( [ 'message' => __( 'Verifica di sicurezza fallita.', 'igs-ecommerce' ) ], 400 );
        }

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permessi insufficienti.', 'igs-ecommerce' ) ], 403 );
        }

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

        if ( '' === $query ) {
            wp_send_json_error( [ 'message' => __( 'Specifica una località.', 'igs-ecommerce' ) ], 400 );
        }

        $cache_key = 'igs_geocode_' . md5( strtolower( $query ) );
        $cached    = get_transient( $cache_key );

        if ( $cached ) {
            wp_send_json_success( $cached );
        }

        $rate_limit_key = self::get_geocode_rate_limit_key();
        $transient_key  = 'igs_geocode_rl_' . md5( $rate_limit_key ?: 'global' );

        if ( get_transient( $transient_key ) ) {
            if ( ! headers_sent() ) {
                header( 'Retry-After: 2' );
            }

            wp_send_json_error( [ 'message' => __( 'Attendi qualche istante prima di effettuare una nuova ricerca.', 'igs-ecommerce' ) ], 429 );
        }

        // Set a short rate limiting transient (2 seconds).
        set_transient( $transient_key, time(), 2 );

        $user_agent = sprintf( 'IGS-Ecommerce/%s (%s)', IGS_ECOMMERCE_VERSION, home_url() );

        $response = wp_remote_get(
            'https://nominatim.openstreetmap.org/search',
            [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => $user_agent,
                    'Referer'    => home_url(),
                    'Accept'     => 'application/json',
                ],
                'body'    => [
                    'format' => 'json',
                    'q'      => $query,
                    'limit'  => 5,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            delete_transient( $transient_key );
            wp_send_json_error( [ 'message' => __( 'Servizio non disponibile.', 'igs-ecommerce' ) ], 503 );
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( 200 !== $code ) {
            delete_transient( $transient_key );
            wp_send_json_error( [ 'message' => __( 'Risposta non valida dal servizio.', 'igs-ecommerce' ) ], $code ?: 500 );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data ) || ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => __( 'Località non trovata.', 'igs-ecommerce' ) ], 404 );
        }

        $first = $data[0];

        if ( ! is_array( $first ) ) {
            delete_transient( $transient_key );
            wp_send_json_error( [ 'message' => __( 'Località non trovata.', 'igs-ecommerce' ) ], 404 );
        }

        $lat = Helpers\normalize_latitude( $first['lat'] ?? '' );
        $lon = Helpers\normalize_longitude( $first['lon'] ?? '' );

        if ( null === $lat || null === $lon ) {
            delete_transient( $transient_key );
            wp_send_json_error( [ 'message' => __( 'Coordinate non valide restituite dal servizio.', 'igs-ecommerce' ) ], 502 );
        }

        $label = isset( $first['display_name'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $first['display_name'] ) ) : '';

        $result = [
            'lat'   => $lat,
            'lon'   => $lon,
            'label' => $label,
        ];

        set_transient( $cache_key, $result, DAY_IN_SECONDS );

        wp_send_json_success( $result );
    }

    /**
     * Build a rate limit key based on the current user or client IP.
     */
    private static function get_geocode_rate_limit_key(): ?string {
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
}
