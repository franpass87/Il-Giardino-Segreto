<?php
/**
 * Custom columns for the WooCommerce product list.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the "Date tour" column to the product list table.
 */
class Admin_Columns {
    /**
     * Hook registration.
     */
    public static function init(): void {
        add_filter( 'manage_edit-product_columns', [ __CLASS__, 'add_column' ] );
        add_action( 'manage_product_posts_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
        add_action( 'admin_head', [ __CLASS__, 'print_styles' ] );
    }

    /**
     * Add the custom column after the title.
     *
     * @param array<string,string> $columns Columns.
     *
     * @return array<string,string>
     */
    public static function add_column( array $columns ): array {
        $new = [];

        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;

            if ( 'name' === $key ) {
                $new['tour_dates'] = __( 'Date tour', 'igs-ecommerce' );
            }
        }

        return $new;
    }

    /**
     * Render the date range for the column.
     */
    public static function render_column( string $column, int $post_id ): void {
        if ( 'tour_dates' !== $column ) {
            return;
        }

        $ranges = get_post_meta( $post_id, '_date_ranges', true );

        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $first = reset( $ranges );
            $start = esc_html( $first['start'] ?? '' );
            $end   = esc_html( $first['end'] ?? '' );

            if ( $start && $end ) {
                echo '<span class="igs-admin-tour-date">' . $start . ' → ' . $end . '</span>';
                return;
            }
        }

        echo '&mdash;';
    }

    /**
     * Provide simple column styling.
     */
    public static function print_styles(): void {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return;
        }

        echo '<style>
            .wp-list-table .column-tour_dates { width: 160px; }
            .wp-list-table .column-tour_dates .igs-admin-tour-date { display: inline-block; min-width: 140px; }
        </style>';
    }
}
