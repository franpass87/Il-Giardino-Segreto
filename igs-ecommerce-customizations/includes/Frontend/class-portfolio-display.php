<?php
/**
 * Frontend display helpers for the portfolio CPT.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Append tour dates and optional partner logo to portfolio titles.
 */
class Portfolio_Display {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_filter( 'the_title', [ __CLASS__, 'append_dates' ], 10, 2 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    /**
     * Enqueue minimal CSS when viewing portfolio content.
     */
    public static function enqueue_styles(): void {
        if ( is_post_type_archive( 'portfolio' ) || is_singular( 'portfolio' ) ) {
            wp_enqueue_style( 'igs-portfolio', Helpers\url( 'assets/css/portfolio.css' ), [], IGS_ECOMMERCE_VERSION );
        }
    }

    /**
     * Append formatted dates to the title output.
     */
    public static function append_dates( string $title, int $post_id ): string {
        if ( is_admin() || 'portfolio' !== get_post_type( $post_id ) ) {
            return $title;
        }

        $departure = get_post_meta( $post_id, '_data_partenza', true );
        $return    = get_post_meta( $post_id, '_data_arrivo', true );

        if ( ! $departure || ! $return ) {
            return $title;
        }

        $departure_ts = strtotime( $departure );
        $return_ts    = strtotime( $return );

        if ( ! $departure_ts || ! $return_ts ) {
            return $title;
        }

        $format       = is_singular( 'portfolio' ) ? 'j F Y' : 'd M Y';
        $departure_fmt = wp_date( $format, $departure_ts );
        $return_fmt    = wp_date( $format, $return_ts );
        $css_class     = is_singular( 'portfolio' ) ? 'igs-portfolio-date--single' : 'igs-portfolio-date--loop';

        $date_html = '<span class="igs-portfolio-date ' . esc_attr( $css_class ) . '">' . esc_html( $departure_fmt ) . ' → ' . esc_html( $return_fmt ) . '</span>';

        $logo = apply_filters( 'igs_portfolio_partner_logo', null, $post_id );
        $logo_html = '';

        if ( is_array( $logo ) && ! empty( $logo['src'] ) ) {
            $width  = isset( $logo['width'] ) ? (int) $logo['width'] : 100;
            $height = isset( $logo['height'] ) ? (int) $logo['height'] : 100;
            $alt    = isset( $logo['alt'] ) ? $logo['alt'] : __( 'Partner', 'igs-ecommerce' );

            $logo_html = '<span class="igs-portfolio-partner"><img src="' . esc_url( $logo['src'] ) . '" alt="' . esc_attr( $alt ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '"></span>';
        }

        return $title . $date_html . $logo_html;
    }
}
