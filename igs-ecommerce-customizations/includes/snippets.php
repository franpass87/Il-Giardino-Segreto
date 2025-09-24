<?php
/**
 * Custom snippets collection for Il Giardino Segreto.
 *
 * @package IGS_Ecommerce_Customizations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ensure we can safely enqueue inline styles even if WooCommerce styles are not yet loaded.
 *
 * @param string $css CSS to print.
 */
function igs_add_inline_style( string $css ): void {
    $css = trim( $css );

    if ( '' === $css ) {
        return;
    }

    $handle = 'igs-ecommerce-customizations-inline';

    if ( ! wp_style_is( $handle, 'enqueued' ) ) {
        wp_register_style( $handle, false, [], null );
        wp_enqueue_style( $handle );
    }

    wp_add_inline_style( $handle, $css );
}

/**
 * Remove default WooCommerce single product layout pieces.
 */
function igs_customize_single_product_hooks(): void {
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
}
add_action( 'init', 'igs_customize_single_product_hooks' );

/**
 * Enqueue admin scripts for the date range repeater with datepickers.
 *
 * @param string $hook Current admin page hook.
 */
function gi_tour_admin_scripts( string $hook ): void {
    if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( ! $screen || 'product' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    wp_add_inline_script(
        'jquery-ui-datepicker',
        <<<'JS'
jQuery(function($){
  function initDatepicker(ctx){
    ctx.find('.date-field').datepicker({ dateFormat:'dd/mm/yy', minDate:0 });
  }
  initDatepicker($('#date_ranges_list'));

  $(document).on('click','.add-date-range',function(){
    var row = $(
      '<div class="date-range-row" style="margin-bottom:10px;">'+
        '<input type="text" name="date_ranges[start][]" class="date-field" placeholder="Partenza" style="width:120px;margin-right:5px;">'+
        '<input type="text" name="date_ranges[end][]"   class="date-field" placeholder="Ritorno"  style="width:120px;margin-right:5px;">'+
        '<button type="button" class="button remove-date-range">Rimuovi</button>'+
      '</div>'
    );
    $('#date_ranges_list').append(row);
    initDatepicker(row);
  });

  $(document).on('click','.remove-date-range',function(){
    $(this).closest('.date-range-row').remove();
  });
});
JS
    );
}
add_action( 'admin_enqueue_scripts', 'gi_tour_admin_scripts', 10, 1 );

/**
 * Render additional product data fields in the general tab.
 */
function gi_tour_product_options(): void {
    echo '<div id="date_ranges_wrapper" class="options_group">';
    echo '<p class="form-field"><label>Date del tour</label> <button type="button" class="button add-date-range">' . esc_html__( 'Aggiungi', 'igs-ecommerce' ) . '</button></p>';
    echo '<div id="date_ranges_list">';

    $saved = get_post_meta( get_the_ID(), '_date_ranges', true );

    if ( is_array( $saved ) ) {
        foreach ( $saved as $range ) {
            printf(
                '<div class="date-range-row" style="margin-bottom:10px;">
                    <input type="text" name="date_ranges[start][]" class="date-field" value="%1$s" placeholder="Partenza" style="width:120px;margin-right:5px;">
                    <input type="text" name="date_ranges[end][]" class="date-field" value="%2$s" placeholder="Ritorno" style="width:120px;margin-right:5px;">
                    <button type="button" class="button remove-date-range">%3$s</button>
                </div>',
                esc_attr( $range['start'] ?? '' ),
                esc_attr( $range['end'] ?? '' ),
                esc_html__( 'Rimuovi', 'igs-ecommerce' )
            );
        }
    }

    echo '</div>';
    echo '</div>';

    woocommerce_wp_text_input(
        [
            'id'          => '_paese_tour',
            'label'       => __( 'Paese del tour', 'igs-ecommerce' ),
            'placeholder' => __( 'Es. Italia', 'igs-ecommerce' ),
            'desc_tip'    => true,
            'description' => __( 'Inserisci il paese del tour', 'igs-ecommerce' ),
        ]
    );
}
add_action( 'woocommerce_product_options_general_product_data', 'gi_tour_product_options' );

/**
 * Save custom product meta fields.
 *
 * @param int $post_id Product ID.
 */
function gi_tour_save_meta( int $post_id ): void {
    if ( isset( $_POST['date_ranges']['start'] ) && is_array( $_POST['date_ranges']['start'] ) ) {
        $starts = wp_unslash( $_POST['date_ranges']['start'] );
        $ends   = isset( $_POST['date_ranges']['end'] ) && is_array( $_POST['date_ranges']['end'] ) ? wp_unslash( $_POST['date_ranges']['end'] ) : [];
        $ranges = [];

        foreach ( $starts as $index => $start_raw ) {
            $start_raw = is_string( $start_raw ) ? $start_raw : '';
            $end_raw   = $ends[ $index ] ?? '';
            $end_raw   = is_string( $end_raw ) ? $end_raw : '';

            if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $start_raw ) && preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $end_raw ) ) {
                $ranges[] = [
                    'start' => sanitize_text_field( $start_raw ),
                    'end'   => sanitize_text_field( $end_raw ),
                ];
            }
        }

        if ( ! empty( $ranges ) ) {
            update_post_meta( $post_id, '_date_ranges', $ranges );
        } else {
            delete_post_meta( $post_id, '_date_ranges' );
        }
    } else {
        delete_post_meta( $post_id, '_date_ranges' );
    }

    if ( isset( $_POST['_paese_tour'] ) ) {
        update_post_meta( $post_id, '_paese_tour', sanitize_text_field( wp_unslash( $_POST['_paese_tour'] ) ) );
    }
}
add_action( 'woocommerce_process_product_meta', 'gi_tour_save_meta' );

/**
 * Show prices without decimals and provide fallbacks when price is missing.
 *
 * @param string     $price   Original price HTML.
 * @param WC_Product $product Product instance.
 *
 * @return string
 */
function custom_price_no_decimals_safe( $price, $product ) {
    if ( $product instanceof WC_Product_Variable ) {
        $min_price = $product->get_variation_price( 'min', true );

        if ( is_numeric( $min_price ) && $min_price > 0 ) {
            return sprintf( /* translators: price from */ __( 'da € %s', 'igs-ecommerce' ), number_format( (float) $min_price, 0, ',', '.' ) );
        }

        return '<span class="no-price"></span>';
    }

    if ( $product instanceof WC_Product ) {
        $value = $product->get_price();

        if ( is_numeric( $value ) && $value > 0 ) {
            return sprintf( '€ %s', number_format( (float) $value, 0, ',', '.' ) );
        }
    }

    return '<span class="no-price">' . esc_html__( 'info in arrivo', 'igs-ecommerce' ) . '</span>';
}
add_filter( 'woocommerce_get_price_html', 'custom_price_no_decimals_safe', 100, 2 );

/**
 * Enqueue styles for the single product layout.
 */
function igs_enqueue_single_product_styles(): void {
    if ( ! is_product() ) {
        return;
    }

    igs_add_inline_style(
        '.custom-hero {' .
        'position:relative;' .
        'left:50%;right:50%;' .
        'width:100vw;' .
        'margin-left:-50vw;margin-right:-50vw;' .
        'height:50vh;' .
        'background-size:cover;' .
        'background-position:center;' .
        'display:flex;' .
        'align-items:center;' .
        'justify-content:center;' .
        '}' .
        '.custom-hero::before {' .
        'content:"";' .
        'position:absolute;top:0;left:0;' .
        'width:100%;height:100%;' .
        'background:rgba(0,0,0,0.3);' .
        '}' .
        '.custom-hero-content {' .
        'position:relative;z-index:1;' .
        'text-align:center;color:#fff;padding:0 20px;' .
        '}' .
        '.custom-hero-content h1 {' .
        'font-size:3em;margin-bottom:.3em;' .
        '}' .
        '.custom-hero-content .country,' .
        '.custom-hero-content .dates {' .
        'font-size:1.2em;margin-bottom:.2em;' .
        '}' .
        '.custom-tour-wrapper {' .
        'max-width:1200px;margin:40px auto;padding:0 20px;' .
        '}' .
        '.custom-tour-columns {' .
        'display:flex;flex-wrap:nowrap;gap:40px;' .
        '}' .
        '.custom-tour-desc {' .
        'flex:2;min-width:0;font-size:1.1em;line-height:1.6;' .
        '}' .
        '.custom-tour-sidebar {' .
        'flex:1;min-width:0;background:#fff;border-radius:12px;' .
        'box-shadow:0 4px 12px rgba(0,0,0,0.1);' .
        'padding:20px;display:flex;flex-direction:column;align-items:center;' .
        '}' .
        '.custom-tour-sidebar .price {' .
        'font-size:2em;font-weight:bold;margin-bottom:10px;' .
        '}' .
        '.custom-tour-sidebar .installment {' .
        'font-size:.95em;color:#777;margin-bottom:20px;' .
        '}' .
        '.custom-tour-sidebar .duration {' .
        'font-size:1.1em;margin-bottom:20px;' .
        '}' .
        '.custom-tour-sidebar .country-band {' .
        'background:#f0f0f0;padding:10px;border-radius:0 0 12px 12px;margin:-20px -20px 0;font-weight:bold;' .
        '}' .
        '@media (min-width:769px){.custom-hero{height:70vh;}}' .
        '@media (max-width:768px){.custom-tour-columns{flex-direction:column;}}' .
        '.tour-services {' .
        'display:flex;flex-direction:column;gap:0.2em;margin:0.2em 0;' .
        '}' .
        '.tour-services span {' .
        'font-size:0.95em;color:#555;font-weight:bold;display:flex;align-items:center;gap:0.4em;' .
        '}'
    );
}
add_action( 'wp_enqueue_scripts', 'igs_enqueue_single_product_styles' );

/**
 * Custom frontend single product layout with hero and sidebar.
 */
function custom_tour_product_layout(): void {
    if ( ! is_product() ) {
        return;
    }

    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $product_id = $product->get_id();
    $ranges     = get_post_meta( $product_id, '_date_ranges', true );
    $country    = get_post_meta( $product_id, '_paese_tour', true );
    $excerpt    = apply_filters( 'woocommerce_short_description', $product->get_short_description() );

    $image_id  = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src();

    echo '<div class="custom-hero" style="background-image:url(' . esc_url( (string) $image_url ) . ')">';
    echo '<div class="custom-hero-content">';
    echo '<h1>' . esc_html( get_the_title( $product_id ) ) . '</h1>';
    echo '<div class="country">' . ( $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' ) ) . '</div>';

    if ( is_array( $ranges ) && ! empty( $ranges ) ) {
        $first_range = reset( $ranges );
        echo '<div class="dates">' . esc_html( $first_range['start'] ?? '' ) . ' → ' . esc_html( $first_range['end'] ?? '' ) . '</div>';
    } else {
        echo '<div class="dates">' . esc_html__( 'Date non disponibili', 'igs-ecommerce' ) . '</div>';
    }

    echo '</div>';
    echo '</div>';

    echo '<div class="custom-tour-wrapper">';
    echo '<div class="custom-tour-columns">';
    echo '<div class="custom-tour-desc">' . wp_kses_post( $excerpt ) . '</div>';
    echo '<div class="custom-tour-sidebar">';

    $price_html = custom_price_no_decimals_safe( '', $product );
    echo '<div class="price">' . $price_html . '</div>';

    echo '<div class="installment">' . esc_html__( 'Pagamento a rate disponibile', 'igs-ecommerce' ) . '</div>';

    if ( is_array( $ranges ) && ! empty( $ranges ) ) {
        $first_range = reset( $ranges );
        $start       = isset( $first_range['start'] ) ? DateTime::createFromFormat( 'd/m/Y', $first_range['start'] ) : false;
        $end         = isset( $first_range['end'] ) ? DateTime::createFromFormat( 'd/m/Y', $first_range['end'] ) : false;

        if ( $start && $end && $end >= $start ) {
            $days = $start->diff( $end )->days + 1;
            echo '<div class="duration"><strong>' . esc_html( $days ) . ' ' . esc_html__( 'giorni', 'igs-ecommerce' ) . '</strong></div>';
        }
    }

    echo '<div class="tour-services">';
    echo '<span>🪷 ' . esc_html__( 'Ingressi ai siti e giardini', 'igs-ecommerce' ) . '</span>';
    echo '<span>🏨 ' . esc_html__( 'Pernottamento incluso', 'igs-ecommerce' ) . '</span>';
    echo '<span>🚌 ' . esc_html__( 'Trasferimenti in loco', 'igs-ecommerce' ) . '</span>';
    echo '<span>🍽️ ' . esc_html__( 'Pasti da itinerario', 'igs-ecommerce' ) . '</span>';
    echo '<span>🗺️ ' . esc_html__( 'Guida locale', 'igs-ecommerce' ) . '</span>';
    echo '</div>';

    echo '<div class="country-band">' . ( $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' ) ) . '</div>';

    echo '</div>';
    echo '</div>';
    echo '</div>';
}
add_action( 'woocommerce_before_single_product_summary', 'custom_tour_product_layout', 1 );

/**
 * Output inline CSS for additional loop styling.
 */
function igs_enqueue_loop_styles(): void {
    igs_add_inline_style(
        '.loop-tour-dates,.loop-tour-duration,.loop-tour-country{' .
        'font-size:0.9em;color:#555;margin-top:0.3em;' .
        '}' .
        '.woocommerce ul.products li.product .woocommerce-loop-product__title{' .
        'line-height:1.4em;min-height:calc(1.4em * 3);margin-bottom:0.5em;overflow:visible;' .
        '}' .
        '.woocommerce ul.products li.product{' .
        'border-radius:10px;overflow:hidden;position:relative;' .
        '}' .
        '.woocommerce ul.products li.product a{' .
        'display:block;border-radius:10px;' .
        '}' .
        '.woocommerce ul.products li.product .full-card-link{' .
        'position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;text-indent:-9999px;' .
        '}'
    );
}
add_action( 'wp_enqueue_scripts', 'igs_enqueue_loop_styles' );

/**
 * Display tour metadata inside product loops.
 */
function gi_tour_show_loop_meta(): void {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $ranges  = get_post_meta( $product->get_id(), '_date_ranges', true );
    $country = get_post_meta( $product->get_id(), '_paese_tour', true );

    $has_valid_dates = false;

    if ( is_array( $ranges ) && ! empty( $ranges ) ) {
        $range = reset( $ranges );
        $start = isset( $range['start'] ) ? DateTime::createFromFormat( 'd/m/Y', $range['start'] ) : false;
        $end   = isset( $range['end'] ) ? DateTime::createFromFormat( 'd/m/Y', $range['end'] ) : false;

        if ( $start && $end && $end >= $start ) {
            echo '<div class="loop-tour-dates">' . esc_html( $range['start'] ?? '' ) . ' → ' . esc_html( $range['end'] ?? '' ) . '</div>';
            echo '<div class="loop-tour-duration">' . esc_html( $start->diff( $end )->days + 1 ) . ' ' . esc_html__( 'giorni', 'igs-ecommerce' ) . '</div>';
            $has_valid_dates = true;
        }
    }

    if ( ! $has_valid_dates ) {
        echo '<div class="loop-tour-dates">' . esc_html__( 'Date non disponibili', 'igs-ecommerce' ) . '</div>';
        echo '<div class="loop-tour-duration">' . esc_html__( 'Durata non disponibile', 'igs-ecommerce' ) . '</div>';
    }

    if ( $country ) {
        echo '<div class="loop-tour-country">' . esc_html( $country ) . '</div>';
    }
}
add_action( 'woocommerce_after_shop_loop_item_title', 'gi_tour_show_loop_meta', 15 );

/**
 * Remove the default add to cart/read more button from product loops.
 */
function igs_remove_loop_add_to_cart(): void {
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
}
add_action( 'init', 'igs_remove_loop_add_to_cart' );
add_filter( 'woocommerce_loop_add_to_cart_link', '__return_empty_string', 10 );

/**
 * Make the entire product card clickable with an overlay link.
 */
function igs_render_full_card_link(): void {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $url   = get_permalink( $product->get_id() );
    $label = get_the_title( $product->get_id() );

    echo '<a href="' . esc_url( $url ) . '" class="full-card-link" aria-label="' . esc_attr( $label ) . '">' . esc_html__( 'Vai al tour', 'igs-ecommerce' ) . '</a>';
}
add_action( 'woocommerce_after_shop_loop_item', 'igs_render_full_card_link', 20 );

/**
 * Register additional meta boxes for garden tour features.
 */
function igs_register_garden_meta_box(): void {
    add_meta_box( 'garden_details_meta', __( 'Dettagli Garden Tour', 'igs-ecommerce' ), 'igs_render_garden_meta_box', 'product', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'igs_register_garden_meta_box' );

/**
 * Render fields for the garden tour feature meta box.
 *
 * @param WP_Post $post Current post object.
 */
function igs_render_garden_meta_box( WP_Post $post ): void {
    $fields = [
        'protagonista_tour'   => [ 'label' => __( 'Pianta', 'igs-ecommerce' ), 'type' => 'text' ],
        'livello_culturale'   => [ 'label' => __( 'Cultura (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
        'livello_passeggiata' => [ 'label' => __( 'Passeggiata (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
        'livello_piuma'       => [ 'label' => __( 'Comfort (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
        'livello_esclusivita' => [ 'label' => __( 'Esclusività (1–5)', 'igs-ecommerce' ), 'type' => 'number' ],
    ];

    foreach ( $fields as $key => $field ) {
        $value = get_post_meta( $post->ID, '_' . $key, true );
        echo '<p><label style="width:180px; display:inline-block;">' . esc_html( $field['label'] ) . ':</label>';
        echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" ';

        if ( 'number' === $field['type'] ) {
            echo 'min="1" max="5" ';
        }

        echo 'style="width:200px;" /></p>';
    }
}

/**
 * Save garden tour meta box values.
 *
 * @param int $post_id Current post ID.
 */
function igs_save_garden_meta( int $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'product' !== $_POST['post_type'] ) {
        return;
    }

    $keys = [ 'protagonista_tour', 'livello_culturale', 'livello_passeggiata', 'livello_piuma', 'livello_esclusivita' ];

    foreach ( $keys as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
        }
    }
}
add_action( 'save_post', 'igs_save_garden_meta' );

add_shortcode( 'protagonista_tour', 'igs_shortcode_protagonista_tour' );
add_shortcode( 'livello_culturale', function () {
    return igs_render_bar_feature( 'livello_culturale', __( 'Cultura', 'igs-ecommerce' ) );
} );
add_shortcode( 'livello_passeggiata', function () {
    return igs_render_bar_feature( 'livello_passeggiata', __( 'Passeggiata', 'igs-ecommerce' ) );
} );
add_shortcode( 'livello_piuma', function () {
    return igs_render_bar_feature( 'livello_piuma', __( 'Comfort', 'igs-ecommerce' ) );
} );
add_shortcode( 'livello_esclusivita', function () {
    return igs_render_bar_feature( 'livello_esclusivita', __( 'Esclusività', 'igs-ecommerce' ) );
} );

/**
 * Shortcode renderer for the plant protagonist block.
 *
 * @return string
 */
function igs_shortcode_protagonista_tour(): string {
    if ( ! is_singular( 'product' ) ) {
        return '';
    }

    $text = get_post_meta( get_the_ID(), '_protagonista_tour', true );

    if ( ! $text ) {
        return '';
    }

    return '<div class="garden-feature" style="margin-bottom:12px;">'
        . '<div style="font-weight:bold; font-family:\'the-seasons-regular\'; margin-bottom:8px;">' . esc_html__( 'Pianta', 'igs-ecommerce' ) . '</div>'
        . '<div style="min-height:32px; display:flex; align-items:center; justify-content:center;">'
        . esc_html( $text )
        . '</div>'
        . '</div>';
}

/**
 * Render the feature bars for the garden feature shortcodes.
 *
 * @param string $meta_key Meta key suffix.
 * @param string $label    Label for the block.
 *
 * @return string
 */
function igs_render_bar_feature( string $meta_key, string $label ): string {
    if ( ! is_singular( 'product' ) ) {
        return '';
    }

    $value = (int) get_post_meta( get_the_ID(), '_' . $meta_key, true );

    if ( $value < 1 || $value > 5 ) {
        return '';
    }

    $bars = '';

    for ( $i = 1; $i <= 5; $i++ ) {
        $filled = $i <= $value ? '#00665e' : '#ccc';
        $bars  .= '<div style="width:14px; height:8px; border-radius:4px; background:' . esc_attr( $filled ) . ';"></div>';
    }

    return '<div class="garden-feature" style="margin-bottom:12px;">'
        . '<div style="font-weight:bold; font-family:\'the-seasons-regular\'; margin-bottom:8px;">' . esc_html( $label ) . '</div>'
        . '<div style="min-height:32px; display:flex; justify-content:center; align-items:center; gap:4px;">' . $bars . '</div>'
        . '</div>';
}

/**
 * Booking modal markup, styles and scripts.
 */
function gs_custom_booking_modal(): void {
    if ( ! is_product() ) {
        return;
    }

    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $price = $product->get_price();

    if ( '' === $price ) {
        return;
    }

    $product_id    = $product->get_id();
    $product_title = $product->get_title();

    ?>
    <style>
        :root {
            --brand-color: #0e5763;
            --brand-color-hover: #0a434c;
            --background-light: #f8f9fa;
            --text-color: #333;
            --border-color: #dee2e6;
            --font-main: 'foundersgrotesk', sans-serif;
        }
        #gs-fixed-cta {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 999;
            padding: 10px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
        }
        #gs-open-modal {
            width: 100%;
            padding: 14px;
            font-family: var(--font-main);
            font-size: 1.1rem;
            font-weight: 500;
            color: #fff;
            background-color: var(--brand-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        #gs-open-modal:hover {
            background-color: var(--brand-color-hover);
            transform: scale(1.02);
        }
        #gs-tour-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 15px;
            font-family: var(--font-main);
        }
        .gs-modal-content {
            background: #fff;
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        #gs-tour-modal.is-visible .gs-modal-content {
            transform: scale(1);
            opacity: 1;
        }
        .gs-modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gs-modal-header h3 { margin: 0; font-size: 1.3rem; color: var(--text-color); }
        .gs-close-modal {
            font-size: 1.8rem; line-height: 1;
            border: none; background: none; cursor: pointer;
            color: #888; transition: color 0.2s;
        }
        .gs-close-modal:hover { color: #000; }
        .gs-modal-body { padding: 20px; }
        #gs-tour-modal .info-view,
        #gs-tour-modal.info-view-active .booking-view { display: none; }
        #gs-tour-modal.info-view-active .info-view,
        #gs-tour-modal .booking-view { display: block; }
        .gs-form-group { margin-bottom: 15px; }
        .gs-form-group > label { margin-bottom: 8px; font-size: 1rem; font-weight: 500; color: #555; display: block; }
        .gs-form-group .variation-label {
            display: block;
            font-size: 1rem;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .variation-label:last-of-type { margin-bottom: 0; }
        .qty-control { display: flex; align-items: center; gap: 8px; }
        .qty-control button {
            background: var(--brand-color); color: #fff; border: none;
            width: 35px; height: 35px; font-size: 1.5rem;
            border-radius: 50%; cursor: pointer; transition: background-color 0.2s;
        }
        .qty-control button:hover { background-color: var(--brand-color-hover); }
        .qty-control input {
            width: 50px; height: 35px; text-align: center;
            border: 1px solid var(--border-color); border-radius: 6px; font-size: 1.1rem;
        }
        #tour-price-total {
            text-align: center; font-size: 1.6rem; font-weight: bold;
            color: var(--brand-color); margin: 20px 0; background: var(--background-light);
            padding: 12px; border-radius: 8px;
        }
        #info-form input[type="text"],
        #info-form input[type="email"],
        #info-form textarea {
            width: 100%; padding: 12px; border: 1px solid var(--border-color);
            border-radius: 6px; font-size: 1rem;
        }
        #info-form textarea { min-height: 100px; }
        #info-success-message {
            display: none; padding: 20px; background-color: #e0f8e9;
            color: #1e6a3c; border-radius: 8px; text-align: center;
        }
        .gs-modal-footer {
            padding: 15px 20px;
            background: var(--background-light);
            border-top: 1px solid var(--border-color);
            display: flex; flex-direction: column; gap: 10px;
        }
        .gs-btn { padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-family: var(--font-main); transition: all 0.3s ease; }
        .gs-btn-primary { background: var(--brand-color); color: #fff; }
        .gs-btn-primary:hover { background: var(--brand-color-hover); }
        .gs-btn-secondary { background: none; border: 1px solid var(--border-color); color: var(--text-color); }
        .gs-btn-secondary:hover { background: var(--border-color); color: #000; }
        @media (max-width: 768px) {
            #gs-fixed-cta { padding: 0; }
            #gs-open-modal { border-radius: 0; }
            .gs-modal-header, .gs-modal-body, .gs-modal-footer {
                padding-left: 15px; padding-right: 15px;
            }
        }
    </style>
    <div id="gs-fixed-cta">
        <button id="gs-open-modal"><?php echo esc_html__( 'Scopri e Prenota', 'igs-ecommerce' ); ?></button>
    </div>
    <div id="gs-tour-modal">
        <div class="gs-modal-content">
            <div class="gs-modal-header">
                <h3><?php echo esc_html( $product_title ); ?></h3>
                <button class="gs-close-modal" aria-label="<?php esc_attr_e( 'Chiudi finestra', 'igs-ecommerce' ); ?>">×</button>
            </div>
            <div class="booking-view">
                <div class="gs-modal-body">
                    <form id="tour-booking-form" onsubmit="return false;">
                        <div class="gs-form-group">
                            <label><?php esc_html_e( 'Scegli la tua opzione:', 'igs-ecommerce' ); ?></label>
                            <?php if ( $product->is_type( 'variable' ) ) : ?>
                                <?php foreach ( $product->get_available_variations() as $variation ) : ?>
                                    <?php if ( $variation['is_in_stock'] && $variation['display_price'] > 0 ) : ?>
                                        <label class="variation-label">
                                            <input type="radio" name="variation_id" value="<?php echo esc_attr( $variation['variation_id'] ); ?>" data-price="<?php echo esc_attr( $variation['display_price'] ); ?>">
                                            <?php echo esc_html( implode( ' / ', $variation['attributes'] ) ); ?>
                                            <span class="variation-price">(€<?php echo esc_html( number_format( (float) $variation['display_price'], 2, ',', '.' ) ); ?>)</span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php elseif ( $product->is_type( 'simple' ) ) : ?>
                                <label class="variation-label">
                                    <input type="radio" name="variation_id" value="0" data-price="<?php echo esc_attr( $product->get_price() ); ?>" checked style="display:none;">
                                    <span><?php esc_html_e( 'Opzione unica', 'igs-ecommerce' ); ?></span>
                                    <span class="variation-price">(€<?php echo esc_html( number_format( (float) $product->get_price(), 2, ',', '.' ) ); ?>)</span>
                                </label>
                            <?php else : ?>
                                <p><?php esc_html_e( 'Non ci sono opzioni di acquisto disponibili per questo prodotto.', 'igs-ecommerce' ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="gs-form-group">
                            <label for="tour-quantity"><?php esc_html_e( 'Numero persone:', 'igs-ecommerce' ); ?></label>
                            <div class="qty-control">
                                <button type="button" class="qty-minus" aria-label="<?php esc_attr_e( 'Diminuisci quantità', 'igs-ecommerce' ); ?>">−</button>
                                <input type="text" id="tour-quantity" name="quantity" value="1" readonly>
                                <button type="button" class="qty-plus" aria-label="<?php esc_attr_e( 'Aumenta quantità', 'igs-ecommerce' ); ?>">+</button>
                            </div>
                        </div>
                        <div id="tour-price-total">€0,00</div>
                    </form>
                </div>
                <div class="gs-modal-footer">
                    <button type="button" id="submit-booking" class="gs-btn gs-btn-primary"><?php esc_html_e( 'Procedi al Checkout', 'igs-ecommerce' ); ?></button>
                    <button type="button" id="go-to-info" class="gs-btn gs-btn-secondary"><?php esc_html_e( 'Richiedi Informazioni', 'igs-ecommerce' ); ?></button>
                </div>
            </div>
            <div class="info-view">
                <div class="gs-modal-body">
                    <div id="info-success-message">
                        <strong><?php esc_html_e( 'Grazie!', 'igs-ecommerce' ); ?></strong><br><?php esc_html_e( 'La tua richiesta è stata inviata. Ti risponderemo al più presto.', 'igs-ecommerce' ); ?>
                    </div>
                    <form id="info-form" onsubmit="return false;">
                        <input type="hidden" name="tour_id" value="<?php echo esc_attr( $product_id ); ?>">
                        <div class="gs-form-group">
                            <label for="info_name"><?php esc_html_e( 'Nome', 'igs-ecommerce' ); ?></label>
                            <input type="text" id="info_name" name="info_name" required>
                        </div>
                        <div class="gs-form-group">
                            <label for="info_email"><?php esc_html_e( 'Email', 'igs-ecommerce' ); ?></label>
                            <input type="email" id="info_email" name="info_email" required>
                        </div>
                        <div class="gs-form-group">
                            <label for="info_comment"><?php esc_html_e( 'La tua richiesta (opzionale)', 'igs-ecommerce' ); ?></label>
                            <textarea id="info_comment" name="info_comment"></textarea>
                        </div>
                    </form>
                </div>
                <div class="gs-modal-footer">
                    <button type="button" id="submit-info" class="gs-btn gs-btn-primary"><?php esc_html_e( 'Invia Richiesta', 'igs-ecommerce' ); ?></button>
                    <button type="button" id="back-to-booking" class="gs-btn gs-btn-secondary"><?php esc_html_e( 'Torna alla Prenotazione', 'igs-ecommerce' ); ?></button>
                </div>
            </div>
        </div>
    </div>
    <script>
    jQuery(function($) {
        const $modal = $('#gs-tour-modal');
        const $bookingForm = $('#tour-booking-form');
        const $infoForm = $('#info-form');
        const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
        const checkoutUrl = '<?php echo esc_js( wc_get_checkout_url() ); ?>';

        function updatePrice() {
            const selectedOption = $bookingForm.find('input[name="variation_id"]:checked');
            if (!selectedOption.length) {
                return;
            }
            const unitPrice = parseFloat(selectedOption.data('price')) || 0;
            const quantity = parseInt($bookingForm.find('input[name="quantity"]').val(), 10) || 1;
            const total = (unitPrice * quantity).toFixed(2).replace('.', ',');
            $('#tour-price-total').text('€' + total);
        }

        function openModal() {
            if (!$bookingForm.find('input[name="variation_id"]:checked').length) {
                $bookingForm.find('input[name="variation_id"]').first().prop('checked', true);
            }
            updatePrice();
            $modal.removeClass('info-view-active');
            $('#info-success-message').hide();
            $infoForm.show();
            $('.info-view .gs-modal-footer').show();
            $modal.css('display', 'flex').addClass('is-visible');
        }

        function closeModal() {
            $modal.removeClass('is-visible');
            setTimeout(() => $modal.hide(), 300);
        }

        $('#gs-open-modal').on('click', openModal);

        $modal.on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('gs-close-modal')) {
                closeModal();
            }
        });

        $('#go-to-info').on('click', () => $modal.addClass('info-view-active'));
        $('#back-to-booking').on('click', () => $modal.removeClass('info-view-active'));

        $bookingForm.on('change', 'input[name="variation_id"]', updatePrice);

        $('.qty-plus').on('click', () => {
            const $input = $bookingForm.find('input[name="quantity"]');
            $input.val(parseInt($input.val(), 10) + 1);
            updatePrice();
        });

        $('.qty-minus').on('click', () => {
            const $input = $bookingForm.find('input[name="quantity"]');
            const val = parseInt($input.val(), 10);
            if (val > 1) {
                $input.val(val - 1);
                updatePrice();
            }
        });

        $('#submit-booking').on('click', function() {
            const $button = $(this);
            const $selected = $bookingForm.find('input[name="variation_id"]:checked');
            const variationId = $selected.val();

            if (typeof variationId === 'undefined') {
                window.alert('<?php echo esc_js( __( "Per favore, seleziona un'opzione prima di procedere.", 'igs-ecommerce' ) ); ?>');
                return;
            }

            $button.prop('disabled', true).text('<?php echo esc_js( __( 'Attendi...', 'igs-ecommerce' ) ); ?>');

            $.post(ajaxUrl, {
                action: 'gs_tour_add_to_cart',
                nonce: '<?php echo wp_create_nonce( 'add_to_cart_nonce' ); ?>',
                tour_id: <?php echo (int) $product_id; ?>,
                variation_id: variationId,
                quantity: $bookingForm.find('input[name="quantity"]').val()
            }).done(function(response) {
                if (response.success) {
                    window.location.href = checkoutUrl;
                } else {
                    window.alert(response.data.message || '<?php echo esc_js( __( 'Si è verificato un errore. Riprova.', 'igs-ecommerce' ) ); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js( __( 'Procedi al Checkout', 'igs-ecommerce' ) ); ?>');
                }
            }).fail(function() {
                window.alert('<?php echo esc_js( __( 'Errore di comunicazione. Riprova.', 'igs-ecommerce' ) ); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js( __( 'Procedi al Checkout', 'igs-ecommerce' ) ); ?>');
            });
        });

        $('#submit-info').on('click', function() {
            const $button = $(this);
            if ($('#info_name').val() === '' || $('#info_email').val() === '') {
                window.alert('<?php echo esc_js( __( 'Per favore, compila nome ed email.', 'igs-ecommerce' ) ); ?>');
                return;
            }

            $button.prop('disabled', true).text('<?php echo esc_js( __( 'Invio in corso...', 'igs-ecommerce' ) ); ?>');

            $.post(ajaxUrl, {
                action: 'gs_handle_tour_info_request',
                nonce: '<?php echo wp_create_nonce( 'tour_info_nonce' ); ?>',
                tour_id: <?php echo (int) $product_id; ?>,
                info_name: $('#info_name').val(),
                info_email: $('#info_email').val(),
                info_comment: $('#info_comment').val()
            }).done(function(response) {
                if (response.success) {
                    $infoForm.hide();
                    $('.info-view .gs-modal-footer').hide();
                    $('#info-success-message').fadeIn();
                } else {
                    window.alert(response.data.message || '<?php echo esc_js( __( 'Errore. Assicurati di aver compilato tutti i campi.', 'igs-ecommerce' ) ); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js( __( 'Invia Richiesta', 'igs-ecommerce' ) ); ?>');
                }
            }).fail(function() {
                window.alert('<?php echo esc_js( __( 'Si è verificato un errore di comunicazione con il server.', 'igs-ecommerce' ) ); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js( __( 'Invia Richiesta', 'igs-ecommerce' ) ); ?>');
            });
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'gs_custom_booking_modal' );

add_action( 'wp_ajax_nopriv_gs_tour_add_to_cart', 'gs_tour_add_to_cart' );
add_action( 'wp_ajax_gs_tour_add_to_cart', 'gs_tour_add_to_cart' );

/**
 * AJAX handler to add the selected tour to the cart.
 */
function gs_tour_add_to_cart(): void {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'add_to_cart_nonce' ) ) {
        wp_send_json_error( [ 'message' => __( 'Verifica di sicurezza fallita.', 'igs-ecommerce' ) ] );
    }

    if ( ! isset( $_POST['tour_id'], $_POST['quantity'] ) ) {
        wp_send_json_error( [ 'message' => __( 'Dati mancanti.', 'igs-ecommerce' ) ] );
    }

    $product_id   = absint( wp_unslash( $_POST['tour_id'] ) );
    $quantity     = max( 1, absint( wp_unslash( $_POST['quantity'] ) ) );
    $variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        wp_send_json_error( [ 'message' => __( 'WooCommerce non è attivo.', 'igs-ecommerce' ) ] );
    }

    WC()->cart->empty_cart();

    $added = false;

    if ( $variation_id > 0 ) {
        $added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
    } else {
        $added = WC()->cart->add_to_cart( $product_id, $quantity );
    }

    if ( $added ) {
        wp_send_json_success( [ 'message' => __( 'Prodotto aggiunto al carrello.', 'igs-ecommerce' ) ] );
    }

    wp_send_json_error( [ 'message' => __( 'Impossibile aggiungere il prodotto al carrello. Potrebbe non essere disponibile.', 'igs-ecommerce' ) ] );
}

add_action( 'wp_ajax_nopriv_gs_handle_tour_info_request', 'gs_handle_tour_info_request' );
add_action( 'wp_ajax_gs_handle_tour_info_request', 'gs_handle_tour_info_request' );

/**
 * AJAX handler for the "request information" form.
 */
function gs_handle_tour_info_request(): void {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tour_info_nonce' ) ) {
        wp_send_json_error( [ 'message' => __( 'Verifica di sicurezza fallita.', 'igs-ecommerce' ) ] );
    }

    $tour_id = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
    $name    = isset( $_POST['info_name'] ) ? sanitize_text_field( wp_unslash( $_POST['info_name'] ) ) : '';
    $email   = isset( $_POST['info_email'] ) ? sanitize_email( wp_unslash( $_POST['info_email'] ) ) : '';
    $comment = isset( $_POST['info_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['info_comment'] ) ) : '';

    if ( empty( $name ) || empty( $tour_id ) || ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Per favore, compila correttamente nome ed email.', 'igs-ecommerce' ) ] );
    }

    $admin_email = get_option( 'admin_email' );
    $product     = wc_get_product( $tour_id );
    $tour_title  = $product ? $product->get_title() : __( 'Tour non specificato', 'igs-ecommerce' );

    $subject = sprintf( __( 'Richiesta informazioni per il tour: %s', 'igs-ecommerce' ), $tour_title );

    $body  = "<html><body style='font-family: sans-serif;'>";
    $body .= '<h2>' . esc_html__( 'Nuova Richiesta Informazioni', 'igs-ecommerce' ) . '</h2>';
    $body .= '<p>' . sprintf( esc_html__( 'Hai ricevuto una nuova richiesta per il tour: %s', 'igs-ecommerce' ), '<strong>' . esc_html( $tour_title ) . '</strong>' ) . '</p>';
    $body .= '<ul>';
    $body .= '<li><strong>' . esc_html__( 'Nome', 'igs-ecommerce' ) . ':</strong> ' . esc_html( $name ) . '</li>';
    $body .= '<li><strong>' . esc_html__( 'Email', 'igs-ecommerce' ) . ':</strong> ' . esc_html( $email ) . '</li>';
    $body .= '</ul>';

    if ( ! empty( $comment ) ) {
        $body .= '<h4>' . esc_html__( 'Messaggio', 'igs-ecommerce' ) . ':</h4>';
        $body .= "<p style='border-left: 3px solid #eee; padding-left: 15px; font-style: italic;'>" . nl2br( esc_html( $comment ) ) . '</p>';
    }

    $body .= '</body></html>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo( 'name' ) . ' <' . $admin_email . '>',
        'Reply-To: ' . esc_attr( $name ) . ' <' . esc_attr( $email ) . '>',
    ];

    if ( wp_mail( $admin_email, $subject, $body, $headers ) ) {
        wp_send_json_success( [ 'message' => __( 'Email inviata con successo.', 'igs-ecommerce' ) ] );
    }

    wp_send_json_error( [ 'message' => __( "Impossibile inviare l'email. Riprova più tardi.", 'igs-ecommerce' ) ] );
}

/**
 * Register custom metabox for portfolio tour dates.
 */
function igs_register_portfolio_dates_meta_box(): void {
    add_meta_box( 'date_tour_meta', __( 'Date del Tour', 'igs-ecommerce' ), 'igs_render_portfolio_dates_meta_box', 'portfolio', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'igs_register_portfolio_dates_meta_box' );

/**
 * Render portfolio date meta box.
 *
 * @param WP_Post $post Post object.
 */
function igs_render_portfolio_dates_meta_box( WP_Post $post ): void {
    $departure = get_post_meta( $post->ID, '_data_partenza', true );
    $return    = get_post_meta( $post->ID, '_data_arrivo', true );

    echo '<label for="data_partenza">' . esc_html__( 'Data Partenza:', 'igs-ecommerce' ) . '</label><br />';
    echo '<input type="date" name="data_partenza" value="' . esc_attr( $departure ) . '"><br /><br />';
    echo '<label for="data_arrivo">' . esc_html__( 'Data Arrivo:', 'igs-ecommerce' ) . '</label><br />';
    echo '<input type="date" name="data_arrivo" value="' . esc_attr( $return ) . '">';
}

/**
 * Save portfolio dates meta values.
 *
 * @param int $post_id Post ID.
 */
function igs_save_portfolio_dates_meta( int $post_id ): void {
    if ( isset( $_POST['data_partenza'] ) ) {
        update_post_meta( $post_id, '_data_partenza', sanitize_text_field( wp_unslash( $_POST['data_partenza'] ) ) );
    }

    if ( isset( $_POST['data_arrivo'] ) ) {
        update_post_meta( $post_id, '_data_arrivo', sanitize_text_field( wp_unslash( $_POST['data_arrivo'] ) ) );
    }
}
add_action( 'save_post', 'igs_save_portfolio_dates_meta' );

/**
 * Append tour dates (and partner logo) to portfolio titles.
 *
 * @param string $title   Post title.
 * @param int    $post_id Post ID.
 *
 * @return string
 */
function igs_portfolio_title_dates( string $title, int $post_id ): string {
    if ( is_admin() || 'portfolio' !== get_post_type( $post_id ) ) {
        return $title;
    }

    $departure = get_post_meta( $post_id, '_data_partenza', true );
    $return    = get_post_meta( $post_id, '_data_arrivo', true );

    if ( ! $departure || ! $return ) {
        return $title;
    }

    setlocale( LC_TIME, 'it_IT.UTF-8' );
    $departure_fmt = ucfirst( strftime( '%d %b %Y', strtotime( $departure ) ) );
    $return_fmt    = ucfirst( strftime( '%d %b %Y', strtotime( $return ) ) );

    $css_class = is_singular( 'portfolio' ) ? 'tour-date-single' : 'tour-date-loop';
    $date_html = '<div class="' . esc_attr( $css_class ) . '">' . esc_html( $departure_fmt ) . ' → ' . esc_html( $return_fmt ) . '</div>';

    $logo_html = '';

    if ( ! is_singular( 'portfolio' ) && has_term( 'tour-in-partnership', 'portfolio_category', $post_id ) ) {
        $logo_html = '<div class="partner-logo-loop"><img src="https://www.italiangardentour.com/wp-content/uploads/2025/07/mob_LOGO_grandigiardiniitaliani_1c.png" width="100" height="100" alt="' . esc_attr__( 'Partner: Grandi Giardini Italiani', 'igs-ecommerce' ) . '"></div>';
    }

    return $title . $date_html . $logo_html;
}
add_filter( 'the_title', 'igs_portfolio_title_dates', 10, 2 );
/**
 * Remove WooCommerce breadcrumb on the shop page.
 */
add_action( 'template_redirect', function () {
    if ( is_shop() ) {
        remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    }
} );

/**
 * Customize the shop page title.
 */
add_filter( 'woocommerce_page_title', 'igs_shop_page_title' );
/**
 * Inject custom shop page styles.
 */
add_action( 'wp_head', 'igs_shop_page_styles' );

/**
 * Change shop page title.
 *
 * @param string $title Original title.
 *
 * @return string
 */
function igs_shop_page_title( string $title ): string {
    if ( is_shop() ) {
        return 'Destinazioni fuori dai sentieri battuti';
    }

    return $title;
}

/**
 * Inline styles for the shop page header.
 */
function igs_shop_page_styles(): void {
    if ( ! is_shop() ) {
        return;
    }

    echo '<style>
        .woocommerce-products-header h1.page-title,
        .woocommerce-page .page-title,
        .woocommerce .page-title {
            text-align: center !important;
            margin-top: 35px;
        }
        nav.woocommerce-breadcrumb {
            display: none !important;
        }
    </style>';
}

/**
 * Add custom product column for tour dates in admin.
 *
 * @param array<string,string> $columns Columns list.
 *
 * @return array<string,string>
 */
function gi_tour_add_date_column( array $columns ): array {
    $new = [];

    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;

        if ( 'name' === $key ) {
            $new['tour_dates'] = 'Date tour';
        }
    }

    return $new;
}
add_filter( 'manage_edit-product_columns', 'gi_tour_add_date_column' );

/**
 * Populate the custom tour dates column.
 *
 * @param string $column  Column ID.
 * @param int    $post_id Product ID.
 */
function gi_tour_fill_date_column( string $column, int $post_id ): void {
    if ( 'tour_dates' !== $column ) {
        return;
    }

    $ranges = get_post_meta( $post_id, '_date_ranges', true );

    if ( is_array( $ranges ) && ! empty( $ranges[0]['start'] ) ) {
        echo '<span class="tour-date-cell">' . esc_html( $ranges[0]['start'] ) . ' → ' . esc_html( $ranges[0]['end'] ?? '' ) . '</span>';
        return;
    }

    echo '—';
}
add_action( 'manage_product_posts_custom_column', 'gi_tour_fill_date_column', 10, 2 );

/**
 * Add admin CSS for the custom tour dates column.
 */
function gi_tour_admin_column_css(): void {
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();

    if ( ! $screen || 'edit-product' !== $screen->id ) {
        return;
    }

    echo '<style>
      .widefat .column-tour_dates {
        white-space: nowrap;
        width: 140px;
      }
      .widefat .column-tour_dates .tour-date-cell {
        display: inline-block;
        min-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    </style>';
}
add_action( 'admin_head', 'gi_tour_admin_column_css' );

/**
 * Change the return to shop button in empty checkout.
 */
add_filter( 'woocommerce_return_to_shop_redirect', function () {
    return home_url();
} );

add_filter( 'woocommerce_return_to_shop_text', function () {
    return 'Ritorna al sito web';
} );

/**
 * Admin page for managing global string replacements.
 */
function gw_global_strings_admin_menu(): void {
    add_options_page(
        'Gestione Testo Globale',
        'Gestione Testo',
        'manage_options',
        'gw-global-strings',
        'gw_global_strings_page_html'
    );
}
add_action( 'admin_menu', 'gw_global_strings_admin_menu' );

/**
 * Register settings for the translation manager.
 */
function gw_global_strings_settings_init(): void {
    register_setting( 'gw_global_strings_group', 'gw_string_replacements_global', [
        'sanitize_callback' => 'wp_kses_post',
    ] );

    add_settings_section(
        'gw_global_strings_section',
        'Regole di Sostituzione Testo',
        'gw_global_strings_section_callback',
        'gw-global-strings'
    );

    add_settings_field(
        'gw_string_replacements_global_field',
        'Inserisci le tue regole',
        'gw_global_strings_textarea_callback',
        'gw-global-strings',
        'gw_global_strings_section'
    );
}
add_action( 'admin_init', 'gw_global_strings_settings_init' );

/**
 * Render settings section description.
 */
function gw_global_strings_section_callback(): void {
    echo '<p>Inserisci una regola per riga. Separa il testo originale dal nuovo testo usando il simbolo della pipe "|".</p>';
    echo '<strong>Esempio:</strong> <code>Prodotti correlati | Ti potrebbe interessare anche</code>';
}

/**
 * Render textarea field for replacement rules.
 */
function gw_global_strings_textarea_callback(): void {
    $options = get_option( 'gw_string_replacements_global' );
    echo '<textarea name="gw_string_replacements_global" rows="10" cols="80" style="width: 100%;">' . esc_textarea( $options ) . '</textarea>';
}

/**
 * Render plugin settings page markup.
 */
function gw_global_strings_page_html(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields( 'gw_global_strings_group' );
    do_settings_sections( 'gw-global-strings' );
    submit_button( 'Salva Modifiche' );
    echo '</form>';
    echo '</div>';
}

add_filter( 'gettext', 'gw_apply_global_string_replacements', 20, 3 );
add_filter( 'ngettext', 'gw_apply_global_string_replacements', 20, 3 );

/**
 * Apply text replacements across the site.
 *
 * @param string $translated_text Translated text.
 * @param string $text            Original text.
 * @param string $domain          Text domain.
 *
 * @return string
 */
function gw_apply_global_string_replacements( string $translated_text, string $text, string $domain ): string {
    $replacements = get_option( 'gw_string_replacements_global' );

    if ( empty( $replacements ) ) {
        return $translated_text;
    }

    static $rules = null;

    if ( null === $rules ) {
        $rules = [];
        $lines = explode( "\n", $replacements );

        foreach ( $lines as $line ) {
            if ( strpos( $line, '|' ) !== false ) {
                list( $original, $new ) = array_map( 'trim', explode( '|', $line, 2 ) );

                if ( '' !== $original ) {
                    $rules[ $original ] = $new;
                }
            }
        }
    }

    if ( isset( $rules[ $translated_text ] ) ) {
        return $rules[ $translated_text ];
    }

    return $translated_text;
}

/**
 * Register itinerary map metabox.
 */
function igs_register_mappa_meta_box(): void {
    add_meta_box(
        'mappa_tappe_meta',
        'Mappa del Viaggio',
        'igs_render_mappa_tappe_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'igs_register_mappa_meta_box' );

/**
 * Render itinerary map metabox fields.
 *
 * @param WP_Post $post Current post.
 */
function igs_render_mappa_tappe_meta_box( WP_Post $post ): void {
    $paese = get_post_meta( $post->ID, '_mappa_paese', true );
    $tappe = get_post_meta( $post->ID, '_mappa_tappe', true );

    if ( ! is_array( $tappe ) ) {
        $tappe = [];
    }

    wp_nonce_field( 'salva_mappa_tappe', 'mappa_tappe_nonce' );

    echo '<p><label>Paese: <input type="text" name="mappa_paese" value="' . esc_attr( $paese ) . '" style="width: 100%;"></label></p>';
    echo '<div id="tappe-container">';

    foreach ( $tappe as $index => $tappa ) {
        $nome = esc_attr( $tappa['nome'] ?? '' );
        $lat  = esc_attr( $tappa['lat'] ?? '' );
        $lon  = esc_attr( $tappa['lon'] ?? '' );
        $desc = esc_textarea( $tappa['descrizione'] ?? '' );

        echo '<div class="tappa-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px; position:relative;">';
        echo '<div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:6px;">';
        echo '<label style="flex:1;">Nome località: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][nome]" value="' . $nome . '" style="width:80%;"></label>';
        echo '<div style="display:flex; gap:6px; white-space:nowrap;">';
        echo '<button type="button" class="button trova-coord">Trova coordinate</button>';
        echo '<button type="button" class="button button-link-delete rimuovi-tappa" style="color:#b32d2e;">Rimuovi tappa</button>';
        echo '</div>';
        echo '</div>';
        echo '<div style="display:flex; gap:10px; margin-bottom:8px;">';
        echo '<label style="flex:1;">Latitudine: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lat]" value="' . $lat . '" style="width:100%;"></label>';
        echo '<label style="flex:1;">Longitudine: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lon]" value="' . $lon . '" style="width:100%;"></label>';
        echo '</div>';
        echo '<label>Descrizione: <textarea name="mappa_tappe[' . esc_attr( $index ) . '][descrizione]" rows="2" style="width:100%;">' . $desc . '</textarea></label>';
        echo '</div>';
    }

    echo '</div>';
    echo '<button type="button" id="aggiungi-tappa" class="button">+ Aggiungi Tappa</button>';
    echo '<p style="margin-top:15px;font-style:italic;color:#555;">Shortcode per questa mappa: <code>[mappa_viaggio id="' . intval( $post->ID ) . '"]</code></p>';
    ?>
    <script>
    jQuery(document).ready(function($){
        var $container = $('#tappe-container');

        function reindexTappe(){
            $container.find('.tappa-item').each(function(idx){
                $(this).find('input, textarea').each(function(){
                    var name = $(this).attr('name');
                    if(!name) return;
                    name = name.replace(/mappa_tappe\[\d+\]/, 'mappa_tappe['+idx+']');
                    $(this).attr('name', name);
                });
            });
        }

        $('#aggiungi-tappa').on('click', function(){
            var idx = $container.find('.tappa-item').length;
            var html = ''
            + '<div class="tappa-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px; position:relative;">'
            + '  <div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:6px;">'
            + '    <label style="flex:1;">Nome località: <input type="text" name="mappa_tappe['+idx+'][nome]" style="width:80%;"></label>'
            + '    <div style="display:flex; gap:6px; white-space:nowrap;">'
            + '      <button type="button" class="button trova-coord">Trova coordinate</button>'
            + '      <button type="button" class="button button-link-delete rimuovi-tappa" style="color:#b32d2e;">Rimuovi tappa</button>'
            + '    </div>'
            + '  </div>'
            + '  <div style="display:flex; gap:10px; margin-bottom:8px;">'
            + '    <label style="flex:1;">Latitudine: <input type="text" name="mappa_tappe['+idx+'][lat]" style="width:100%;"></label>'
            + '    <label style="flex:1;">Longitudine: <input type="text" name="mappa_tappe['+idx+'][lon]" style="width:100%;"></label>'
            + '  </div>'
            + '  <label>Descrizione: <textarea name="mappa_tappe['+idx+'][descrizione]" rows="2" style="width:100%;"></textarea></label>'
            + '</div>';
            $container.append(html);
            reindexTappe();
        });

        $(document).on('click', '.rimuovi-tappa', function(e){
            e.preventDefault();
            if(!confirm('Eliminare questa tappa?')) return;
            $(this).closest('.tappa-item').remove();
            reindexTappe();
        });

        $(document).on('click', '.trova-coord', function(e){
            e.preventDefault();
            var wrapper = $(this).closest('.tappa-item');
            var $nome = wrapper.find('input[name*="[nome]"]');
            var $lat  = wrapper.find('input[name*="[lat]"]');
            var $lon  = wrapper.find('input[name*="[lon]"]');
            var nomeLocalita = ($nome.val() || '').trim();
            if(!nomeLocalita){ alert('Inserisci il nome della località'); return; }

            var url = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(nomeLocalita);
            fetch(url, {
                headers: {'Accept':'application/json'}
            })
            .then(response => response.json())
            .then(data => {
                if(Array.isArray(data) && data.length > 0){
                    $lat.val(data[0].lat);
                    $lon.val(data[0].lon);
                } else {
                    alert('Località non trovata');
                }
            })
            .catch(() => alert('Errore nella ricerca coordinate'));
        });
    });
    </script>
    <?php
}

/**
 * Save itinerary map data.
 *
 * @param int $post_id Product ID.
 */
function igs_save_mappa_tappe( int $post_id ): void {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['mappa_tappe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mappa_tappe_nonce'] ) ), 'salva_mappa_tappe' ) ) {
        return;
    }

    $paese = isset( $_POST['mappa_paese'] ) ? sanitize_text_field( wp_unslash( $_POST['mappa_paese'] ) ) : '';
    update_post_meta( $post_id, '_mappa_paese', $paese );

    if ( isset( $_POST['mappa_tappe'] ) && is_array( $_POST['mappa_tappe'] ) ) {
        $raw = wp_unslash( $_POST['mappa_tappe'] );
        $clean = [];

        foreach ( $raw as $tappa ) {
            $nome = isset( $tappa['nome'] ) ? sanitize_text_field( $tappa['nome'] ) : '';
            $lat  = isset( $tappa['lat'] ) ? sanitize_text_field( $tappa['lat'] ) : '';
            $lon  = isset( $tappa['lon'] ) ? sanitize_text_field( $tappa['lon'] ) : '';
            $desc = isset( $tappa['descrizione'] ) ? sanitize_textarea_field( $tappa['descrizione'] ) : '';

            if ( '' !== $nome ) {
                $clean[] = [
                    'nome'        => $nome,
                    'lat'         => $lat,
                    'lon'         => $lon,
                    'descrizione' => $desc,
                ];
            }
        }

        if ( ! empty( $clean ) ) {
            $clean = array_values( $clean );
            update_post_meta( $post_id, '_mappa_tappe', $clean );
        } else {
            delete_post_meta( $post_id, '_mappa_tappe' );
        }
    } else {
        delete_post_meta( $post_id, '_mappa_tappe' );
    }
}
add_action( 'save_post_product', 'igs_save_mappa_tappe' );

/**
 * Shortcode renderer for the itinerary map.
 *
 * @param array<string,string> $atts Shortcode attributes.
 *
 * @return string
 */
function igs_shortcode_mappa_viaggio( array $atts ): string {
    $atts = shortcode_atts( [ 'id' => '' ], $atts );
    $post_id = intval( $atts['id'] );

    if ( ! $post_id ) {
        return '';
    }

    $tappe = get_post_meta( $post_id, '_mappa_tappe', true );

    if ( ! is_array( $tappe ) || empty( $tappe ) ) {
        return '';
    }

    wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true );
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' );

    ob_start();
    ?>
    <style>
    #mappa-viaggio-<?php echo esc_attr( $post_id ); ?> {
        width: 100%;
        height: 500px;
        max-width: 100%;
    }
    @media (max-width: 768px) {
        #mappa-viaggio-<?php echo esc_attr( $post_id ); ?> { height: 350px; }
    }
    .custom-pin{
        display:flex;justify-content:center;align-items:center;
        font-weight:bold;background-color:#0c5764;color:#fff;border-radius:50%;
        width:36px;height:36px;text-align:center;line-height:36px;font-size:14px;
    }
    </style>

    <div id="mappa-viaggio-<?php echo esc_attr( $post_id ); ?>"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var map = L.map('mappa-viaggio-<?php echo esc_attr( $post_id ); ?>', {
            scrollWheelZoom: false, tap: true
        });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
        }).addTo(map);

        var tappe = <?php echo wp_json_encode( $tappe ); ?>;
        var punti = [];

        tappe.forEach(function(tappa, i){
            if(!tappa.lat || !tappa.lon) return;
            var marker = L.marker([tappa.lat, tappa.lon], {
                icon: L.divIcon({
                    className: 'custom-pin',
                    html: '<div>'+(i+1)+'</div>',
                    iconSize: [36, 36],
                    popupAnchor: [0, -18]
                })
            }).addTo(map).bindPopup('<b>'+ (tappa.nome || '') +'</b><br>'+ (tappa.descrizione || ''), { maxWidth: 250 });
            punti.push([tappa.lat, tappa.lon]);
        });

        if (punti.length) {
            L.polyline(punti, {color: '#0c5764', dashArray: '5, 10'}).addTo(map);
            map.fitBounds(punti);
        }

        map.on('popupopen', function(e){
            var closeBtn = e.popup._closeButton;
            if (closeBtn) {
                closeBtn.addEventListener('click', function(evt){
                    evt.preventDefault(); evt.stopPropagation();
                });
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mappa_viaggio', 'igs_shortcode_mappa_viaggio' );
