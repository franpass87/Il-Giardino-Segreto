<?php
/**
 * Custom WooCommerce toolkit for Il Giardino Segreto.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

use DateTime;
use WP_Post;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Bootstrapper class.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Cached translation replacements.
     *
     * @var array<string, string>|null
     */
    private $translation_rules = null;

    /**
     * Retrieve singleton instance.
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'init', [ $this, 'on_init' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_product_admin_assets' ], 10, 1 );
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'render_product_options' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_meta' ] );
        add_filter( 'woocommerce_get_price_html', [ $this, 'filter_price_html' ], 100, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_product_styles' ] );
        add_action( 'woocommerce_before_single_product_summary', [ $this, 'render_custom_product_layout' ], 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tour_services_styles' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );
        add_shortcode( 'protagonista_tour', [ $this, 'shortcode_protagonista_tour' ] );
        add_shortcode( 'livello_culturale', [ $this, 'shortcode_livello_culturale' ] );
        add_shortcode( 'livello_passeggiata', [ $this, 'shortcode_livello_passeggiata' ] );
        add_shortcode( 'livello_piuma', [ $this, 'shortcode_livello_piuma' ] );
        add_shortcode( 'livello_esclusivita', [ $this, 'shortcode_livello_esclusivita' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_loop_styles' ] );
        add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'output_loop_meta' ], 15 );
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_full_card_link' ], 20 );
        add_action( 'wp_footer', [ $this, 'render_booking_modal' ] );
        add_action( 'wp_ajax_gs_tour_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_gs_tour_add_to_cart', [ $this, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_gs_handle_tour_info_request', [ $this, 'ajax_handle_info_request' ] );
        add_action( 'wp_ajax_nopriv_gs_handle_tour_info_request', [ $this, 'ajax_handle_info_request' ] );
        add_filter( 'the_title', [ $this, 'filter_portfolio_title' ], 10, 2 );
        add_action( 'template_redirect', [ $this, 'customize_shop_template' ] );
        add_filter( 'woocommerce_page_title', [ $this, 'filter_shop_title' ] );
        add_action( 'wp_head', [ $this, 'print_shop_styles' ] );
        add_filter( 'manage_edit-product_columns', [ $this, 'add_product_date_column' ] );
        add_action( 'manage_product_posts_custom_column', [ $this, 'render_product_date_column' ], 10, 2 );
        add_action( 'admin_head', [ $this, 'print_admin_product_column_styles' ] );
        add_filter( 'woocommerce_return_to_shop_redirect', [ $this, 'filter_return_to_shop_url' ] );
        add_filter( 'woocommerce_return_to_shop_text', [ $this, 'filter_return_to_shop_text' ] );
        add_action( 'admin_menu', [ $this, 'register_text_manager_page' ] );
        add_action( 'admin_init', [ $this, 'register_text_manager_settings' ] );
        add_filter( 'gettext', [ $this, 'apply_text_replacements' ], 20, 3 );
        add_filter( 'ngettext', [ $this, 'apply_text_replacements' ], 20, 3 );
        add_shortcode( 'mappa_viaggio', [ $this, 'shortcode_mappa_viaggio' ] );
    }

    /**
     * Remove default WooCommerce components.
     */
    public function on_init(): void {
        remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
        remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
        add_filter( 'woocommerce_loop_add_to_cart_link', '__return_empty_string', 10 );
    }

    /**
     * Load admin scripts for date picker repeater.
     *
     * @param string $hook Current admin hook.
     */
    public function enqueue_product_admin_assets( $hook ): void {
        if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
        wp_add_inline_script( 'jquery-ui-datepicker', <<<'JS'
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

    /**
     * Render custom product fields for tours.
     */
    public function render_product_options(): void {
        echo '<div id="date_ranges_wrapper" class="options_group">';
        echo '<p class="form-field"><label>Date del tour</label> <button type="button" class="button add-date-range">Aggiungi</button></p>';
        echo '<div id="date_ranges_list">';

        $saved = get_post_meta( get_the_ID(), '_date_ranges', true );
        if ( is_array( $saved ) ) {
            foreach ( $saved as $range ) {
                printf(
                    '<div class="date-range-row" style="margin-bottom:10px;">'
                    . '<input type="text" name="date_ranges[start][]" class="date-field" value="%s" placeholder="Partenza" style="width:120px;margin-right:5px;">'
                    . '<input type="text" name="date_ranges[end][]" class="date-field" value="%s" placeholder="Ritorno" style="width:120px;margin-right:5px;">'
                    . '<button type="button" class="button remove-date-range">Rimuovi</button>'
                    . '</div>',
                    esc_attr( $range['start'] ?? '' ),
                    esc_attr( $range['end'] ?? '' )
                );
            }
        }

        echo '</div>';
        echo '</div>';

        woocommerce_wp_text_input(
            [
                'id'          => '_paese_tour',
                'label'       => 'Paese del tour',
                'placeholder' => 'Es. Italia',
                'desc_tip'    => true,
                'description' => 'Inserisci il paese del tour',
            ]
        );
    }

    /**
     * Persist custom tour meta.
     *
     * @param int $post_id Product ID.
     */
    public function save_product_meta( int $post_id ): void {
        if ( ! empty( $_POST['date_ranges']['start'] ) && is_array( $_POST['date_ranges']['start'] ) ) {
            $ranges = [];
            $starts = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['date_ranges']['start'] ) );
            $ends   = isset( $_POST['date_ranges']['end'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['date_ranges']['end'] ) ) : [];

            foreach ( $starts as $index => $start ) {
                $end = $ends[ $index ] ?? '';
                if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $start ) && preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $end ) ) {
                    $ranges[] = [
                        'start' => $start,
                        'end'   => $end,
                    ];
                }
            }

            update_post_meta( $post_id, '_date_ranges', $ranges );
        } else {
            delete_post_meta( $post_id, '_date_ranges' );
        }

        if ( isset( $_POST['_paese_tour'] ) ) {
            update_post_meta( $post_id, '_paese_tour', sanitize_text_field( wp_unslash( $_POST['_paese_tour'] ) ) );
        }
    }

    /**
     * Format product prices.
     *
     * @param string     $price   Current price HTML.
     * @param WC_Product $product Product instance.
     *
     * @return string
     */
    public function filter_price_html( $price, $product ) {
        if ( ! $product instanceof WC_Product ) {
            return $price;
        }

        if ( $product->is_type( 'variable' ) ) {
            $min_price = $product->get_variation_price( 'min', true );
            if ( is_numeric( $min_price ) && $min_price > 0 ) {
                return 'da € ' . number_format( (float) $min_price, 0, ',', '.' );
            }

            return '<span class="no-price"></span>';
        }

        $raw = $product->get_price();
        if ( is_numeric( $raw ) && $raw > 0 ) {
            return '€ ' . number_format( (float) $raw, 0, ',', '.' );
        }

        return '<span class="no-price">info in arrivo</span>';
    }

    /**
     * Inject hero and layout styles.
     */
    public function enqueue_product_styles(): void {
        if ( ! is_product() ) {
            return;
        }

        wp_add_inline_style(
            'woocommerce-general',
            '  .custom-hero {'
            . '    position: relative;'
            . '    left: 50%; right: 50%;'
            . '    width: 100vw;'
            . '    margin-left: -50vw; margin-right: -50vw;'
            . '    height: 50vh;'
            . '    background-size: cover;'
            . '    background-position: center;'
            . '    display: flex; align-items: center; justify-content: center;'
            . '  }'
            . '  .custom-hero::before {'
            . '    content: "";'
            . '    position: absolute; top:0; left:0;'
            . '    width:100%; height:100%;'
            . '    background: rgba(0,0,0,0.3);'
            . '  }'
            . '  .custom-hero-content {'
            . '    position: relative; z-index:1;'
            . '    text-align:center; color:#fff; padding:0 20px;'
            . '  }'
            . '  .custom-hero-content h1 { font-size:3em; margin-bottom:.3em; }'
            . '  .custom-hero-content .country,'
            . '  .custom-hero-content .dates { font-size:1.2em; margin-bottom:.2em; }'
            . '  .custom-tour-wrapper { max-width:1200px; margin:40px auto; padding:0 20px; }'
            . '  .custom-tour-columns { display:flex; flex-wrap:nowrap; gap:40px; }'
            . '  .custom-tour-desc { flex:2; min-width:0; font-size:1.1em; line-height:1.6; }'
            . '  .custom-tour-sidebar {'
            . '    flex:1; min-width:0;'
            . '    background:#fff; border-radius:12px;'
            . '    box-shadow:0 4px 12px rgba(0,0,0,0.1);'
            . '    padding:20px; display:flex; flex-direction:column; align-items:center;'
            . '  }'
            . '  .custom-tour-sidebar .price { font-size:2em; font-weight:bold; margin-bottom:10px; }'
            . '  .custom-tour-sidebar .installment { font-size:.95em; color:#777; margin-bottom:20px; }'
            . '  .custom-tour-sidebar .duration { font-size:1.1em; margin-bottom:20px; }'
            . '  .custom-tour-sidebar .country-band {'
            . '    background:#f0f0f0; padding:10px;'
            . '    border-radius:0 0 12px 12px;'
            . '    margin:-20px -20px 0; font-weight:bold;'
            . '  }'
            . '  @media (min-width:769px) { .custom-hero { height:70vh; } }'
            . '  @media (max-width:768px) { .custom-tour-columns { flex-direction:column; } }'
        );
    }

    /**
     * Print hero layout markup.
     */
    public function render_custom_product_layout(): void {
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

        echo '<div class="custom-hero" style="background-image:url(' . esc_url( $image_url ) . ')">';
        echo '<div class="custom-hero-content">';
        echo '<h1>' . esc_html( get_the_title( $product_id ) ) . '</h1>';
        echo '<div class="country">' . ( $country ? esc_html( $country ) : 'Paese non specificato' ) . '</div>';

        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $first = $ranges[0];
            echo '<div class="dates">' . esc_html( $first['start'] ?? '' ) . ' → ' . esc_html( $first['end'] ?? '' ) . '</div>';
        } else {
            echo '<div class="dates">Date non disponibili</div>';
        }

        echo '</div>';
        echo '</div>';

        echo '<div class="custom-tour-wrapper">';
        echo '<div class="custom-tour-columns">';
        echo '<div class="custom-tour-desc">' . wp_kses_post( $excerpt ) . '</div>';
        echo '<div class="custom-tour-sidebar">';
        echo '<div class="price">' . $this->filter_price_html( '', $product ) . '</div>';
        echo '<div class="installment">' . esc_html__( 'Pagamento a rate disponibile', 'igs-ecommerce' ) . '</div>';

        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $start = DateTime::createFromFormat( 'd/m/Y', $ranges[0]['start'] ?? '' );
            $end   = DateTime::createFromFormat( 'd/m/Y', $ranges[0]['end'] ?? '' );

            if ( $start instanceof DateTime && $end instanceof DateTime && $end >= $start ) {
                $days = $start->diff( $end )->days + 1;
                echo '<div class="duration"><strong>' . esc_html( $days ) . ' giorni</strong></div>';
            }
        }

        echo '<div class="tour-services">';
        echo '<span>🪷 Ingressi ai siti e giardini</span>';
        echo '<span>🏨 Pernottamento incluso</span>';
        echo '<span>🚌 Trasferimenti in loco</span>';
        echo '<span>🍽️ Pasti da itinerario</span>';
        echo '<span>🗺️ Guida locale</span>';
        echo '</div>';

        echo '<div class="country-band">' . ( $country ? esc_html( $country ) : esc_html__( 'Paese non specificato', 'igs-ecommerce' ) ) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Inject styles for tour services list.
     */
    public function enqueue_tour_services_styles(): void {
        if ( ! is_product() ) {
            return;
        }

        wp_add_inline_style(
            'woocommerce-general',
            '  .tour-services {'
            . '    display:flex;'
            . '    flex-direction:column;'
            . '    gap:0.2em;'
            . '    margin:0.2em 0;'
            . '  }'
            . '  .tour-services span {'
            . '    font-size:0.95em;'
            . '    color:#555;'
            . '    font-weight:bold;'
            . '    display:flex; align-items:center; gap:0.4em;'
            . '  }'
        );
    }

    /**
     * Register meta boxes for products and portfolio entries.
     */
    public function register_meta_boxes(): void {
        add_meta_box(
            'garden_details_meta',
            'Dettagli Garden Tour',
            [ $this, 'render_garden_meta_box' ],
            'product',
            'normal',
            'high'
        );

        add_meta_box(
            'mappa_tappe_meta',
            'Mappa del Viaggio',
            [ $this, 'render_mappa_tappe_meta_box' ],
            'product',
            'normal',
            'default'
        );

        add_meta_box(
            'date_tour_meta',
            'Date del Tour',
            [ $this, 'render_portfolio_date_meta_box' ],
            'portfolio',
            'side',
            'default'
        );
    }

    /**
     * Render Garden Tour meta box.
     */
    public function render_garden_meta_box( WP_Post $post ): void {
        foreach ( $this->get_garden_fields() as $key => $field ) {
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
     * Return Garden Tour field definitions.
     *
     * @return array<string, array{label:string,type:string}>
     */
    private function get_garden_fields(): array {
        return [
            'protagonista_tour'   => [ 'label' => 'Pianta', 'type' => 'text' ],
            'livello_culturale'   => [ 'label' => 'Cultura (1–5)', 'type' => 'number' ],
            'livello_passeggiata' => [ 'label' => 'Passeggiata (1–5)', 'type' => 'number' ],
            'livello_piuma'       => [ 'label' => 'Comfort (1–5)', 'type' => 'number' ],
            'livello_esclusivita' => [ 'label' => 'Esclusività (1–5)', 'type' => 'number' ],
        ];
    }

    /**
     * Persist meta boxes values.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta_boxes( int $post_id, WP_Post $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( 'product' === $post->post_type ) {
            $this->save_garden_meta_fields( $post_id );
            $this->save_mappa_tappe_meta( $post_id );
        }

        if ( 'portfolio' === $post->post_type ) {
            $this->save_portfolio_dates( $post_id );
        }
    }

    /**
     * Save Garden Tour metadata.
     */
    private function save_garden_meta_fields( int $post_id ): void {
        foreach ( $this->get_garden_fields() as $key => $field ) { // phpcs:ignore Generic.CodeAnalysis.UnusedVariable
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }

    /**
     * Render protagonista shortcode.
     */
    public function shortcode_protagonista_tour(): string {
        if ( ! is_singular( 'product' ) ) {
            return '';
        }

        $text = get_post_meta( get_the_ID(), '_protagonista_tour', true );
        if ( ! $text ) {
            return '';
        }

        return '<div class="garden-feature" style="margin-bottom:12px;">'
            . '<div style="font-weight:bold; font-family:\'the-seasons-regular\'; margin-bottom:8px;">Pianta</div>'
            . '<div style="min-height:32px; display:flex; align-items:center; justify-content:center;">'
            . esc_html( $text )
            . '</div>'
            . '</div>';
    }

    /**
     * Render Cultura shortcode.
     */
    public function shortcode_livello_culturale(): string {
        return $this->render_bar_feature( 'livello_culturale', 'Cultura' );
    }

    /**
     * Render Passeggiata shortcode.
     */
    public function shortcode_livello_passeggiata(): string {
        return $this->render_bar_feature( 'livello_passeggiata', 'Passeggiata' );
    }

    /**
     * Render Comfort shortcode.
     */
    public function shortcode_livello_piuma(): string {
        return $this->render_bar_feature( 'livello_piuma', 'Comfort' );
    }

    /**
     * Render Esclusività shortcode.
     */
    public function shortcode_livello_esclusivita(): string {
        return $this->render_bar_feature( 'livello_esclusivita', 'Esclusività' );
    }

    /**
     * Output bar features for tour attributes.
     */
    private function render_bar_feature( string $meta_key, string $label ): string {
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
     * Add styles for loop cards and metadata.
     */
    public function enqueue_loop_styles(): void {
        wp_add_inline_style(
            'woocommerce-general',
            '  .loop-tour-dates,'
            . '  .loop-tour-duration,'
            . '  .loop-tour-country { font-size:0.9em; color:#555; margin-top:0.3em; }'
            . '  .woocommerce ul.products li.product .woocommerce-loop-product__title {'
            . '    line-height:1.4em;'
            . '    min-height:calc(1.4em * 3);'
            . '    margin-bottom:0.5em;'
            . '    overflow:visible;'
            . '  }'
            . '  .woocommerce ul.products li.product {'
            . '    border-radius:10px;'
            . '    overflow:hidden;'
            . '    position:relative;'
            . '  }'
            . '  .woocommerce ul.products li.product a {'
            . '    display:block;'
            . '    border-radius:10px;'
            . '  }'
            . '  .woocommerce ul.products li.product .full-card-link {'
            . '    position:absolute;'
            . '    top:0; left:0;'
            . '    width:100%; height:100%;'
            . '    z-index:10;'
            . '    text-indent:-9999px;'
            . '  }'
        );
    }

    /**
     * Display tour metadata inside product loop.
     */
    public function output_loop_meta(): void {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $ranges  = get_post_meta( $product->get_id(), '_date_ranges', true );
        $country = get_post_meta( $product->get_id(), '_paese_tour', true );

        $valid = false;
        if ( is_array( $ranges ) && ! empty( $ranges ) ) {
            $range = $ranges[0];
            $start = DateTime::createFromFormat( 'd/m/Y', $range['start'] ?? '' );
            $end   = DateTime::createFromFormat( 'd/m/Y', $range['end'] ?? '' );

            if ( $start instanceof DateTime && $end instanceof DateTime && $end >= $start ) {
                echo '<div class="loop-tour-dates">' . esc_html( $range['start'] ?? '' ) . ' → ' . esc_html( $range['end'] ?? '' ) . '</div>';
                echo '<div class="loop-tour-duration">' . esc_html( $start->diff( $end )->days + 1 ) . ' giorni</div>';
                $valid = true;
            }
        }

        if ( ! $valid ) {
            echo '<div class="loop-tour-dates">Date non disponibili</div>';
            echo '<div class="loop-tour-duration">Durata non disponibile</div>';
        }

        if ( $country ) {
            echo '<div class="loop-tour-country">' . esc_html( $country ) . '</div>';
        }
    }

    /**
     * Overlay link to make loop cards clickable.
     */
    public function render_full_card_link(): void {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $url   = get_permalink( $product->get_id() );
        $label = get_the_title( $product->get_id() );
        echo '<a href="' . esc_url( $url ) . '" class="full-card-link" aria-label="' . esc_attr( $label ) . '">Vai al tour</a>';
    }
    /**
     * Print booking modal markup and scripts.
     */
    public function render_booking_modal(): void {
        if ( ! is_product() ) {
            return;
        }

        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        if ( '' === $product->get_price() ) {
            return;
        }

        $product_id    = $product->get_id();
        $product_title = $product->get_title();
        $ajax_url      = admin_url( 'admin-ajax.php' );
        $checkout_url  = wc_get_checkout_url();
        $cart_nonce    = wp_create_nonce( 'add_to_cart_nonce' );
        $info_nonce    = wp_create_nonce( 'tour_info_nonce' );

        ob_start();
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
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(0, 0, 0, 0.6);
                z-index: 10000;
                justify-content: center;
                align-items: center;
                padding: 15px;
                font-family: var(--font-main);
            }
            #gs-tour-modal.is-visible .gs-modal-content {
                transform: scale(1);
                opacity: 1;
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
                .gs-modal-header, .gs-modal-body, .gs-modal-footer { padding-left: 15px; padding-right: 15px; }
            }
        </style>
        <div id="gs-fixed-cta">
            <button id="gs-open-modal">Scopri e Prenota</button>
        </div>
        <div id="gs-tour-modal">
            <div class="gs-modal-content">
                <div class="gs-modal-header">
                    <h3><?php echo esc_html( $product_title ); ?></h3>
                    <button class="gs-close-modal" aria-label="Chiudi finestra">×</button>
                </div>
                <div class="booking-view">
                    <div class="gs-modal-body">
                        <form id="tour-booking-form" onsubmit="return false;">
                            <div class="gs-form-group">
                                <label>Scegli la tua opzione:</label>
                                <?php if ( $product->is_type( 'variable' ) ) : ?>
                                    <?php foreach ( $product->get_available_variations() as $variation ) : ?>
                                        <?php if ( $variation['is_in_stock'] && $variation['display_price'] > 0 ) : ?>
                                            <label class="variation-label">
                                                <input type="radio" name="variation_id" value="<?php echo esc_attr( $variation['variation_id'] ); ?>" data-price="<?php echo esc_attr( $variation['display_price'] ); ?>">
                                                <?php echo esc_html( implode( ' / ', $variation['attributes'] ) ); ?>
                                                <span class="variation-price">(€<?php echo number_format( (float) $variation['display_price'], 2, ',', '.' ); ?>)</span>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php elseif ( $product->is_type( 'simple' ) ) : ?>
                                    <label class="variation-label">
                                        <input type="radio" name="variation_id" value="0" data-price="<?php echo esc_attr( $product->get_price() ); ?>" checked style="display:none;">
                                        <span>Opzione unica</span>
                                        <span class="variation-price">(€<?php echo number_format( (float) $product->get_price(), 2, ',', '.' ); ?>)</span>
                                    </label>
                                <?php else : ?>
                                    <p>Non ci sono opzioni di acquisto disponibili per questo prodotto.</p>
                                <?php endif; ?>
                            </div>
                            <div class="gs-form-group">
                                <label for="tour-quantity">Numero persone:</label>
                                <div class="qty-control">
                                    <button type="button" class="qty-minus" aria-label="Diminuisci quantità">−</button>
                                    <input type="text" id="tour-quantity" name="quantity" value="1" readonly>
                                    <button type="button" class="qty-plus" aria-label="Aumenta quantità">+</button>
                                </div>
                            </div>
                            <div id="tour-price-total">€0,00</div>
                        </form>
                    </div>
                    <div class="gs-modal-footer">
                        <button type="button" id="submit-booking" class="gs-btn gs-btn-primary">Procedi al Checkout</button>
                        <button type="button" id="go-to-info" class="gs-btn gs-btn-secondary">Richiedi Informazioni</button>
                    </div>
                </div>
                <div class="info-view">
                    <div class="gs-modal-body">
                        <div id="info-success-message">
                            <strong>Grazie!</strong><br>La tua richiesta è stata inviata. Ti risponderemo al più presto.
                        </div>
                        <form id="info-form" onsubmit="return false;">
                            <input type="hidden" name="tour_id" value="<?php echo esc_attr( $product_id ); ?>">
                            <div class="gs-form-group">
                                <label for="info_name">Nome</label>
                                <input type="text" id="info_name" name="info_name" required>
                            </div>
                            <div class="gs-form-group">
                                <label for="info_email">Email</label>
                                <input type="email" id="info_email" name="info_email" required>
                            </div>
                            <div class="gs-form-group">
                                <label for="info_comment">La tua richiesta (opzionale)</label>
                                <textarea id="info_comment" name="info_comment"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="gs-modal-footer">
                        <button type="button" id="submit-info" class="gs-btn gs-btn-primary">Invia Richiesta</button>
                        <button type="button" id="back-to-booking" class="gs-btn gs-btn-secondary">Torna alla Prenotazione</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        jQuery(function($) {
            const $modal = $('#gs-tour-modal');
            const $bookingForm = $('#tour-booking-form');
            const $infoForm = $('#info-form');
            const ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
            const checkoutUrl = <?php echo wp_json_encode( $checkout_url ); ?>;

            function updatePrice() {
                const selectedOption = $bookingForm.find('input[name="variation_id"]:checked');
                if (!selectedOption.length) {
                    return;
                }
                const unitPrice = parseFloat(selectedOption.data('price')) || 0;
                const quantity = parseInt($bookingForm.find('input[name="quantity"]').val(), 10) || 1;
                $('#tour-price-total').text('€' + (unitPrice * quantity).toFixed(2).replace('.', ','));
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
                setTimeout(function(){ $modal.hide(); }, 300);
            }

            $('#gs-open-modal').on('click', openModal);

            $modal.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('gs-close-modal')) {
                    closeModal();
                }
            });

            $('#go-to-info').on('click', function(){ $modal.addClass('info-view-active'); });
            $('#back-to-booking').on('click', function(){ $modal.removeClass('info-view-active'); });
            $bookingForm.on('change', 'input[name="variation_id"]', updatePrice);

            $('.qty-plus').on('click', function(){
                const $input = $bookingForm.find('input[name="quantity"]');
                $input.val(parseInt($input.val(), 10) + 1);
                updatePrice();
            });

            $('.qty-minus').on('click', function(){
                const $input = $bookingForm.find('input[name="quantity"]');
                const val = parseInt($input.val(), 10);
                if (val > 1) {
                    $input.val(val - 1);
                    updatePrice();
                }
            });

            $('#submit-booking').on('click', function(){
                const $button = $(this);
                const variationId = $bookingForm.find('input[name="variation_id"]:checked').val();

                if (typeof variationId === 'undefined') {
                    alert('Per favore, seleziona un\'opzione prima di procedere.');
                    return;
                }

                $button.prop('disabled', true).text('Attendi...');

                $.post(ajaxUrl, {
                    action: 'gs_tour_add_to_cart',
                    nonce: <?php echo wp_json_encode( $cart_nonce ); ?>,
                    tour_id: <?php echo (int) $product_id; ?>,
                    variation_id: variationId,
                    quantity: $bookingForm.find('input[name="quantity"]').val()
                }).done(function(response){
                    if (response.success) {
                        window.location.href = checkoutUrl;
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Si è verificato un errore. Riprova.');
                        $button.prop('disabled', false).text('Procedi al Checkout');
                    }
                }).fail(function(){
                    alert('Errore di comunicazione. Riprova.');
                    $button.prop('disabled', false).text('Procedi al Checkout');
                });
            });

            $('#submit-info').on('click', function(){
                const $button = $(this);
                if ($('#info_name').val() === '' || $('#info_email').val() === '') {
                    alert('Per favore, compila nome ed email.');
                    return;
                }

                $button.prop('disabled', true).text('Invio in corso...');

                $.post(ajaxUrl, {
                    action: 'gs_handle_tour_info_request',
                    nonce: <?php echo wp_json_encode( $info_nonce ); ?>,
                    tour_id: <?php echo (int) $product_id; ?>,
                    info_name: $('#info_name').val(),
                    info_email: $('#info_email').val(),
                    info_comment: $('#info_comment').val()
                }).done(function(response){
                    if (response.success) {
                        $infoForm.hide();
                        $('.info-view .gs-modal-footer').hide();
                        $('#info-success-message').fadeIn();
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Errore. Assicurati di aver compilato tutti i campi.');
                        $button.prop('disabled', false).text('Invia Richiesta');
                    }
                }).fail(function(){
                    alert('Si è verificato un errore di comunicazione con il server.');
                    $button.prop('disabled', false).text('Invia Richiesta');
                });
            });
        });
        </script>
        <?php
        echo ob_get_clean();
    }

    /**
     * AJAX handler to add tours to cart.
     */
    public function ajax_add_to_cart(): void {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'add_to_cart_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Verifica di sicurezza fallita.' ] );
        }

        if ( ! isset( $_POST['tour_id'], $_POST['quantity'] ) ) {
            wp_send_json_error( [ 'message' => 'Dati mancanti.' ] );
        }

        $product_id   = absint( wp_unslash( $_POST['tour_id'] ) );
        $quantity     = absint( wp_unslash( $_POST['quantity'] ) );
        $variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( [ 'message' => 'WooCommerce non è attivo.' ] );
        }

        WC()->cart->empty_cart();

        $product_added = false;
        if ( $variation_id > 0 ) {
            $product_added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
        } else {
            $product_added = WC()->cart->add_to_cart( $product_id, $quantity );
        }

        if ( $product_added ) {
            wp_send_json_success( [ 'message' => 'Prodotto aggiunto al carrello.' ] );
        }

        wp_send_json_error( [ 'message' => 'Impossibile aggiungere il prodotto al carrello. Potrebbe non essere disponibile.' ] );
    }

    /**
     * Handle info request submission via AJAX.
     */
    public function ajax_handle_info_request(): void {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'tour_info_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Verifica di sicurezza fallita.' ] );
        }

        $tour_id = isset( $_POST['tour_id'] ) ? absint( wp_unslash( $_POST['tour_id'] ) ) : 0;
        $name    = isset( $_POST['info_name'] ) ? sanitize_text_field( wp_unslash( $_POST['info_name'] ) ) : '';
        $email   = isset( $_POST['info_email'] ) ? sanitize_email( wp_unslash( $_POST['info_email'] ) ) : '';
        $comment = isset( $_POST['info_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['info_comment'] ) ) : '';

        if ( empty( $name ) || empty( $tour_id ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Per favore, compila correttamente nome ed email.' ] );
        }

        $admin_email = get_option( 'admin_email' );
        $product     = wc_get_product( $tour_id );
        $tour_title  = $product ? $product->get_title() : 'Tour non specificato';

        $subject = sprintf( 'Richiesta informazioni per il tour: %s', $tour_title );

        $body  = "<html><body style='font-family: sans-serif;'>";
        $body .= '<h2>Nuova Richiesta Informazioni</h2>';
        $body .= '<p>Hai ricevuto una nuova richiesta per il tour: <strong>' . esc_html( $tour_title ) . '</strong></p>';
        $body .= '<ul>';
        $body .= '<li><strong>Nome:</strong> ' . esc_html( $name ) . '</li>';
        $body .= '<li><strong>Email:</strong> ' . esc_html( $email ) . '</li>';
        $body .= '</ul>';
        if ( ! empty( $comment ) ) {
            $body .= "<h4>Messaggio:</h4>";
            $body .= "<p style='border-left: 3px solid #eee; padding-left: 15px; font-style: italic;'>" . nl2br( esc_html( $comment ) ) . '</p>';
        }
        $body .= '</body></html>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . $admin_email . '>',
            'Reply-To: ' . esc_attr( $name ) . ' <' . esc_attr( $email ) . '>',
        ];

        if ( wp_mail( $admin_email, $subject, $body, $headers ) ) {
            wp_send_json_success( [ 'message' => 'Email inviata con successo.' ] );
        }

        wp_send_json_error( [ 'message' => "Impossibile inviare l'email. Riprova più tardi." ] );
    }
    /**
     * Render portfolio date meta box.
     */
    public function render_portfolio_date_meta_box( WP_Post $post ): void {
        $start = get_post_meta( $post->ID, '_data_partenza', true );
        $end   = get_post_meta( $post->ID, '_data_arrivo', true );

        echo '<label for="data_partenza">Data Partenza:</label><br>';
        echo '<input type="date" name="data_partenza" value="' . esc_attr( $start ) . '"><br><br>';
        echo '<label for="data_arrivo">Data Arrivo:</label><br>';
        echo '<input type="date" name="data_arrivo" value="' . esc_attr( $end ) . '">';
    }

    /**
     * Render itinerary meta box.
     */
    public function render_mappa_tappe_meta_box( WP_Post $post ): void {
        $country = get_post_meta( $post->ID, '_mappa_paese', true );
        $stages  = get_post_meta( $post->ID, '_mappa_tappe', true );
        if ( ! is_array( $stages ) ) {
            $stages = [];
        }

        wp_nonce_field( 'salva_mappa_tappe', 'mappa_tappe_nonce' );

        echo '<p><label>Paese: <input type="text" name="mappa_paese" value="' . esc_attr( $country ) . '" style="width: 100%;"></label></p>';
        echo '<div id="tappe-container">';

        foreach ( $stages as $index => $stage ) {
            echo '<div class="tappa-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px; position:relative;">';
            echo '<div style="display:flex; gap:8px; align-items:center; justify-content:space-between; margin-bottom:6px;">';
            echo '<label style="flex:1;">Nome località: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][nome]" value="' . esc_attr( $stage['nome'] ?? '' ) . '" style="width:80%;"></label>';
            echo '<div style="display:flex; gap:6px; white-space:nowrap;">';
            echo '<button type="button" class="button trova-coord">Trova coordinate</button>';
            echo '<button type="button" class="button button-link-delete rimuovi-tappa" style="color:#b32d2e;">Rimuovi tappa</button>';
            echo '</div>';
            echo '</div>';
            echo '<div style="display:flex; gap:10px; margin-bottom:8px;">';
            echo '<label style="flex:1;">Latitudine: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lat]" value="' . esc_attr( $stage['lat'] ?? '' ) . '" style="width:100%;"></label>';
            echo '<label style="flex:1;">Longitudine: <input type="text" name="mappa_tappe[' . esc_attr( $index ) . '][lon]" value="' . esc_attr( $stage['lon'] ?? '' ) . '" style="width:100%;"></label>';
            echo '</div>';
            echo '<label>Descrizione: <textarea name="mappa_tappe[' . esc_attr( $index ) . '][descrizione]" rows="2" style="width:100%;">' . esc_textarea( $stage['descrizione'] ?? '' ) . '</textarea></label>';
            echo '</div>';
        }

        echo '</div>';
        echo '<button type="button" id="aggiungi-tappa" class="button">+ Aggiungi Tappa</button>';
        echo '<p style="margin-top:15px;font-style:italic;color:#555;">Shortcode per questa mappa: <code>[mappa_viaggio id="' . (int) $post->ID . '"]</code></p>';
        ?>
        <script>
        jQuery(document).ready(function($){
            var $container = $('#tappe-container');
            function reindexTappe(){
                $container.find('.tappa-item').each(function(idx){
                    $(this).find('input, textarea').each(function(){
                        var name = $(this).attr('name');
                        if(!name){ return; }
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
                if(!confirm('Eliminare questa tappa?')){ return; }
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
                var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(nomeLocalita);
                fetch(url, { headers: { 'Accept': 'application/json' } })
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
     * Save itinerary meta box data.
     */
    private function save_mappa_tappe_meta( int $post_id ): void {
        if ( ! isset( $_POST['mappa_tappe_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['mappa_tappe_nonce'] ), 'salva_mappa_tappe' ) ) {
            return;
        }

        $country = isset( $_POST['mappa_paese'] ) ? sanitize_text_field( wp_unslash( $_POST['mappa_paese'] ) ) : '';
        update_post_meta( $post_id, '_mappa_paese', $country );

        if ( isset( $_POST['mappa_tappe'] ) && is_array( $_POST['mappa_tappe'] ) ) {
            $raw   = wp_unslash( $_POST['mappa_tappe'] );
            $clean = [];
            foreach ( $raw as $stage ) {
                $name = isset( $stage['nome'] ) ? sanitize_text_field( $stage['nome'] ) : '';
                if ( '' === $name ) {
                    continue;
                }
                $clean[] = [
                    'nome'        => $name,
                    'lat'         => isset( $stage['lat'] ) ? sanitize_text_field( $stage['lat'] ) : '',
                    'lon'         => isset( $stage['lon'] ) ? sanitize_text_field( $stage['lon'] ) : '',
                    'descrizione' => isset( $stage['descrizione'] ) ? sanitize_textarea_field( $stage['descrizione'] ) : '',
                ];
            }

            if ( ! empty( $clean ) ) {
                update_post_meta( $post_id, '_mappa_tappe', array_values( $clean ) );
            } else {
                delete_post_meta( $post_id, '_mappa_tappe' );
            }
        } else {
            delete_post_meta( $post_id, '_mappa_tappe' );
        }
    }

    /**
     * Save portfolio tour dates.
     */
    private function save_portfolio_dates( int $post_id ): void {
        if ( isset( $_POST['data_partenza'] ) ) {
            update_post_meta( $post_id, '_data_partenza', sanitize_text_field( wp_unslash( $_POST['data_partenza'] ) ) );
        }
        if ( isset( $_POST['data_arrivo'] ) ) {
            update_post_meta( $post_id, '_data_arrivo', sanitize_text_field( wp_unslash( $_POST['data_arrivo'] ) ) );
        }
    }

    /**
     * Customize portfolio titles with dates and partner logo.
     *
     * @param string $title   Original title.
     * @param int    $post_id Post ID.
     */
    public function filter_portfolio_title( string $title, int $post_id ): string {
        if ( is_admin() || 'portfolio' !== get_post_type( $post_id ) ) {
            return $title;
        }

        $start = get_post_meta( $post_id, '_data_partenza', true );
        $end   = get_post_meta( $post_id, '_data_arrivo', true );

        if ( ! $start || ! $end ) {
            return $title;
        }

        setlocale( LC_TIME, 'it_IT.UTF-8' );
        $start_fmt = ucfirst( strftime( '%d %b %Y', strtotime( $start ) ) );
        $end_fmt   = ucfirst( strftime( '%d %b %Y', strtotime( $end ) ) );

        $css_class = is_singular( 'portfolio' ) ? 'tour-date-single' : 'tour-date-loop';
        $date_html = '<div class="' . esc_attr( $css_class ) . '">' . esc_html( $start_fmt ) . ' → ' . esc_html( $end_fmt ) . '</div>';

        $logo_html = '';
        if ( ! is_singular( 'portfolio' ) && has_term( 'tour-in-partnership', 'portfolio_category', $post_id ) ) {
            $logo_html = '<div class="partner-logo-loop"><img src="https://www.italiangardentour.com/wp-content/uploads/2025/07/mob_LOGO_grandigiardiniitaliani_1c.png" width="100" height="100" alt="Partner: Grandi Giardini Italiani"></div>';
        }

        return $title . $date_html . $logo_html;
    }

    /**
     * Remove breadcrumb on shop template.
     */
    public function customize_shop_template(): void {
        if ( is_shop() ) {
            remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
        }
    }

    /**
     * Override shop page title.
     */
    public function filter_shop_title( string $title ): string {
        if ( is_shop() ) {
            return 'Destinazioni fuori dai sentieri battuti';
        }

        return $title;
    }

    /**
     * Print shop-specific styles.
     */
    public function print_shop_styles(): void {
        if ( ! is_shop() ) {
            return;
        }

        echo '<style>'
            . '.woocommerce-products-header h1.page-title,'
            . '.woocommerce-page .page-title,'
            . '.woocommerce .page-title { text-align: center !important; margin-top: 35px; }'
            . 'nav.woocommerce-breadcrumb { display: none !important; }'
            . '</style>';
    }

    /**
     * Add custom column with tour dates in admin product list.
     */
    public function add_product_date_column( array $columns ): array {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'name' === $key ) {
                $new['tour_dates'] = 'Date tour';
            }
        }

        return $new;
    }

    /**
     * Populate custom product column.
     */
    public function render_product_date_column( string $column, int $post_id ): void {
        if ( 'tour_dates' !== $column ) {
            return;
        }

        $ranges = get_post_meta( $post_id, '_date_ranges', true );
        if ( is_array( $ranges ) && ! empty( $ranges[0]['start'] ) ) {
            echo '<span class="tour-date-cell">' . esc_html( $ranges[0]['start'] ) . ' → ' . esc_html( $ranges[0]['end'] ?? '' ) . '</span>';
        } else {
            echo '—';
        }
    }

    /**
     * Inject admin column styles.
     */
    public function print_admin_product_column_styles(): void {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'edit-product' !== $screen->id ) {
            return;
        }

        echo '<style>'
            . '.widefat .column-tour_dates { white-space: nowrap; width: 140px; }'
            . '.widefat .column-tour_dates .tour-date-cell { display: inline-block; min-width: 120px; overflow: hidden; text-overflow: ellipsis; }'
            . '</style>';
    }

    /**
     * Customize return to shop button URL.
     */
    public function filter_return_to_shop_url( string $url ): string {
        return home_url();
    }

    /**
     * Customize return to shop button text.
     */
    public function filter_return_to_shop_text( string $text ): string {
        return 'Ritorna al sito web';
    }

    /**
     * Register text replacement settings page.
     */
    public function register_text_manager_page(): void {
        add_options_page(
            'Gestione Testo Globale',
            'Gestione Testo',
            'manage_options',
            'gw-global-strings',
            [ $this, 'render_text_manager_page' ]
        );
    }

    /**
     * Render admin page for text replacements.
     */
    public function render_text_manager_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'gw_global_strings_group' );
                do_settings_sections( 'gw-global-strings' );
                submit_button( 'Salva Modifiche' );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings for text replacements.
     */
    public function register_text_manager_settings(): void {
        register_setting( 'gw_global_strings_group', 'gw_string_replacements_global', [
            'sanitize_callback' => 'wp_kses_post',
        ] );

        add_settings_section(
            'gw_global_strings_section',
            'Regole di Sostituzione Testo',
            function () {
                echo '<p>Inserisci una regola per riga. Separa il testo originale dal nuovo testo usando il simbolo della pipe "|".</p>';
                echo '<strong>Esempio:</strong> <code>Prodotti correlati | Ti potrebbe interessare anche</code>';
            },
            'gw-global-strings'
        );

        add_settings_field(
            'gw_string_replacements_global_field',
            'Inserisci le tue regole',
            [ $this, 'render_text_manager_textarea' ],
            'gw-global-strings',
            'gw_global_strings_section'
        );
    }

    /**
     * Render textarea for text replacements.
     */
    public function render_text_manager_textarea(): void {
        $options = get_option( 'gw_string_replacements_global' );
        echo '<textarea name="gw_string_replacements_global" rows="10" cols="80" style="width: 100%;">' . esc_textarea( $options ) . '</textarea>';
    }

    /**
     * Apply text replacements site wide.
     *
     * @param string $translated_text Current translation.
     * @param string $text            Original text.
     * @param string $domain          Text domain.
     */
    public function apply_text_replacements( string $translated_text, string $text, string $domain ): string { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        $rules = $this->get_translation_rules();
        if ( empty( $rules ) ) {
            return $translated_text;
        }

        return $rules[ $translated_text ] ?? $translated_text;
    }

    /**
     * Retrieve translation replacement rules.
     *
     * @return array<string, string>
     */
    private function get_translation_rules(): array {
        if ( null !== $this->translation_rules ) {
            return $this->translation_rules;
        }

        $raw = get_option( 'gw_string_replacements_global' );
        if ( empty( $raw ) ) {
            $this->translation_rules = [];
            return $this->translation_rules;
        }

        $rules = [];
        $lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
        foreach ( $lines as $line ) {
            if ( false === strpos( $line, '|' ) ) {
                continue;
            }

            list( $original, $replacement ) = array_map( 'trim', explode( '|', $line, 2 ) );
            if ( '' !== $original ) {
                $rules[ $original ] = $replacement;
            }
        }

        $this->translation_rules = $rules;
        return $this->translation_rules;
    }

    /**
     * Shortcode renderer for Leaflet-based itinerary map.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     */
    public function shortcode_mappa_viaggio( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => '' ], $atts );
        $post_id = (int) $atts['id'];
        if ( ! $post_id ) {
            return '';
        }

        $stages = get_post_meta( $post_id, '_mappa_tappe', true );
        if ( ! is_array( $stages ) || empty( $stages ) ) {
            return '';
        }

        wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true );
        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], null );

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
            var map = L.map('mappa-viaggio-<?php echo esc_attr( $post_id ); ?>', { scrollWheelZoom: false, tap: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
            }).addTo(map);
            var tappe = <?php echo wp_json_encode( $stages ); ?>;
            var punti = [];
            tappe.forEach(function(tappa, i){
                if(!tappa.lat || !tappa.lon){ return; }
                var marker = L.marker([tappa.lat, tappa.lon], {
                    icon: L.divIcon({
                        className: 'custom-pin',
                        html: '<div>'+(i+1)+'</div>',
                        iconSize: [36, 36],
                        popupAnchor: [0, -18]
                    })
                }).addTo(map).bindPopup('<b>'+(tappa.nome || '')+'</b><br>'+(tappa.descrizione || ''), { maxWidth: 250 });
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
}
