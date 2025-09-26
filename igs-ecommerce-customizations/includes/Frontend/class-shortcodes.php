<?php
/**
 * Shortcodes for tour features and maps.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide frontend shortcodes for tour pages.
 */
class Shortcodes {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
    }

    /**
     * Register shortcode callbacks.
     */
    public static function register_shortcodes(): void {
        add_shortcode( 'protagonista_tour', [ __CLASS__, 'shortcode_protagonista' ] );
        add_shortcode( 'livello_culturale', fn() => self::render_bar_feature( 'livello_culturale', __( 'Cultura', 'igs-ecommerce' ) ) );
        add_shortcode( 'livello_passeggiata', fn() => self::render_bar_feature( 'livello_passeggiata', __( 'Passeggiata', 'igs-ecommerce' ) ) );
        add_shortcode( 'livello_piuma', fn() => self::render_bar_feature( 'livello_piuma', __( 'Comfort', 'igs-ecommerce' ) ) );
        add_shortcode( 'livello_esclusivita', fn() => self::render_bar_feature( 'livello_esclusivita', __( 'Esclusività', 'igs-ecommerce' ) ) );
        add_shortcode( 'mappa_viaggio', [ __CLASS__, 'shortcode_map' ] );
    }

    /**
     * Register reusable assets.
     */
    public static function register_assets(): void {
        wp_register_style( 'igs-shortcodes', Helpers\url( 'assets/css/shortcodes.css' ), [], IGS_ECOMMERCE_VERSION );

        $leaflet_style_url = apply_filters(
            'igs_leaflet_style_url',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
        );

        $leaflet_script_url = apply_filters(
            'igs_leaflet_script_url',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
        );

        wp_register_style( 'igs-leaflet', $leaflet_style_url, [], '1.9.4' );
        wp_register_script( 'igs-leaflet', $leaflet_script_url, [], '1.9.4', true );

        wp_register_style( 'igs-tour-map', Helpers\url( 'assets/css/map.css' ), [ 'igs-leaflet' ], IGS_ECOMMERCE_VERSION );
        wp_register_script( 'igs-tour-map', Helpers\url( 'assets/js/map.js' ), [ 'igs-leaflet' ], IGS_ECOMMERCE_VERSION, true );

        $default_tile_layer = [
            'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
            'options'     => [],
        ];

        $tile_layer = apply_filters( 'igs_tour_map_tile_layer', $default_tile_layer );

        if ( ! is_array( $tile_layer ) ) {
            $tile_layer = $default_tile_layer;
        }

        $tile_url = isset( $tile_layer['url'] ) && is_string( $tile_layer['url'] ) ? trim( $tile_layer['url'] ) : '';

        if ( '' === $tile_url || ! preg_match( '#^https?://#i', $tile_url ) || false !== strpos( $tile_url, '"' ) || false !== strpos( $tile_url, "'" ) ) {
            $tile_url = $default_tile_layer['url'];
        }

        $tile_attribution = isset( $tile_layer['attribution'] ) && is_string( $tile_layer['attribution'] ) ? wp_kses_post( $tile_layer['attribution'] ) : $default_tile_layer['attribution'];

        $tile_options = [];

        if ( isset( $tile_layer['options'] ) && is_array( $tile_layer['options'] ) ) {
            foreach ( $tile_layer['options'] as $option_key => $option_value ) {
                if ( ! is_string( $option_key ) ) {
                    continue;
                }

                $sanitized_key = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $option_key );

                if ( '' === $sanitized_key ) {
                    continue;
                }

                if ( is_bool( $option_value ) || is_numeric( $option_value ) ) {
                    $tile_options[ $sanitized_key ] = $option_value;
                } elseif ( is_string( $option_value ) ) {
                    $tile_options[ $sanitized_key ] = sanitize_text_field( $option_value );
                }
            }
        }

        wp_localize_script(
            'igs-tour-map',
            'igsTourMapConfig',
            [
                'tileUrl'         => $tile_url,
                'tileAttribution' => $tile_attribution,
                'tileOptions'     => $tile_options,
            ]
        );
    }

    /**
     * Output the protagonist block.
     */
    public static function shortcode_protagonista(): string {
        if ( ! is_singular( 'product' ) ) {
            return '';
        }

        $value = get_post_meta( get_the_ID(), '_protagonista_tour', true );

        if ( ! $value ) {
            return '';
        }

        wp_enqueue_style( 'igs-shortcodes' );

        return '<div class="igs-garden-feature" data-feature="plant" role="group" aria-label="' . esc_attr__( 'Pianta protagonista', 'igs-ecommerce' ) . '">'
            . '<div class="igs-garden-feature__card">'
            . '<div class="igs-garden-feature__label">' . esc_html__( 'Pianta', 'igs-ecommerce' ) . '</div>'
            . '<div class="igs-garden-feature__value">' . esc_html( $value ) . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the bar feature shortcode content.
     */
    private static function render_bar_feature( string $meta_key, string $label ): string {
        if ( ! is_singular( 'product' ) ) {
            return '';
        }

        $value = (int) get_post_meta( get_the_ID(), '_' . $meta_key, true );

        if ( $value < 1 || $value > 5 ) {
            return '';
        }

        wp_enqueue_style( 'igs-shortcodes' );

        $bars       = '';
        $aria_label = sprintf( _n( '%s livello su 5', '%s livelli su 5', $value, 'igs-ecommerce' ), number_format_i18n( $value ) );

        for ( $i = 1; $i <= 5; $i++ ) {
            $filled = $i <= $value ? 'igs-garden-bars__item--active' : '';
            $bars  .= '<span class="igs-garden-bars__item ' . esc_attr( $filled ) . '" style="--igs-garden-bar-index:' . esc_attr( (string) $i ) . ';" aria-hidden="true"></span>';
        }

        return '<div class="igs-garden-feature" data-feature="' . esc_attr( $meta_key ) . '" data-level="' . esc_attr( (string) $value ) . '" role="img" aria-label="' . esc_attr( $aria_label ) . '">'
            . '<div class="igs-garden-feature__card">'
            . '<div class="igs-garden-feature__label">' . esc_html( $label ) . '</div>'
            . '<div class="igs-garden-bars" aria-hidden="true">' . $bars . '</div>'
            . '<div class="igs-garden-bars__scale" aria-hidden="true">'
            . '<span class="igs-garden-bars__scale-label igs-garden-bars__scale-label--min">' . esc_html__( 'Basso', 'igs-ecommerce' ) . '</span>'
            . '<span class="igs-garden-bars__scale-label igs-garden-bars__scale-label--max">' . esc_html__( 'Alto', 'igs-ecommerce' ) . '</span>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the itinerary map shortcode.
     *
     * @param array<string,string> $atts Shortcode attributes.
     */
    public static function shortcode_map( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => '' ], $atts );
        $post_id = (int) $atts['id'];

        if ( $post_id <= 0 ) {
            return '';
        }

        $stops = get_post_meta( $post_id, '_mappa_tappe', true );

        if ( ! is_array( $stops ) || empty( $stops ) ) {
            return '';
        }

        $points = [];

        foreach ( $stops as $stop ) {
            $name = isset( $stop['nome'] ) ? sanitize_text_field( $stop['nome'] ) : '';
            $lat  = isset( $stop['lat'] ) ? Helpers\normalize_latitude( $stop['lat'] ) : null;
            $lon  = isset( $stop['lon'] ) ? Helpers\normalize_longitude( $stop['lon'] ) : null;
            $desc = isset( $stop['descrizione'] ) ? sanitize_textarea_field( $stop['descrizione'] ) : '';

            if ( null === $lat || null === $lon ) {
                continue;
            }

            $points[] = [
                'name'        => $name,
                'lat'         => $lat,
                'lon'         => $lon,
                'description' => $desc,
            ];
        }

        if ( empty( $points ) ) {
            return '';
        }

        $map_id      = 'igs-tour-map-' . $post_id . '-' . wp_rand( 1000, 9999 );
        $country_raw = get_post_meta( $post_id, '_mappa_paese', true );
        $country     = is_string( $country_raw ) ? sanitize_text_field( $country_raw ) : '';

        wp_enqueue_style( 'igs-leaflet' );
        wp_enqueue_style( 'igs-tour-map' );
        wp_enqueue_script( 'igs-leaflet' );
        wp_enqueue_script( 'igs-tour-map' );

        wp_localize_script(
            'igs-tour-map',
            'igsTourMapStrings',
            [
                'missingLeaflet' => __( 'La mappa non è disponibile al momento. Riprova più tardi.', 'igs-ecommerce' ),
            ]
        );

        $data = [
            'points'  => $points,
            'country' => $country,
        ];

        $encoded = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

        if ( false === $encoded ) {
            return '';
        }

        return '<div class="igs-tour-map-wrapper">'
            . '<div id="' . esc_attr( $map_id ) . '" class="igs-tour-map" data-map="' . esc_attr( $encoded ) . '"></div>'
            . '<noscript>' . esc_html__( 'Abilita JavaScript per visualizzare la mappa del tour.', 'igs-ecommerce' ) . '</noscript>'
            . '</div>';
    }
}
