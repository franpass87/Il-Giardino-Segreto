<?php
/**
 * Main plugin bootstrap.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin orchestrator.
 */
final class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Instantiate the plugin and register hooks.
     */
    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        $this->load_dependencies();
    }

    /**
     * Retrieve the singleton instance.
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( 'igs-ecommerce', false, dirname( plugin_basename( IGS_ECOMMERCE_FILE ) ) . '/languages' );
    }

    /**
     * Include and bootstrap the various modules.
     */
    private function load_dependencies(): void {
        require_once IGS_ECOMMERCE_PATH . 'includes/class-translations.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/helpers.php';

        Translations::init();
        Helpers\register_tour_product_cache_invalidation();

        // Admin modules.
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-product-meta.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-garden-meta-box.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-map-meta-box.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-portfolio-meta.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-global-strings.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Admin/class-admin-columns.php';

        Admin\Product_Meta::init();
        Admin\Garden_Meta_Box::init();
        Admin\Map_Meta_Box::init();
        Admin\Portfolio_Meta::init();
        Admin\Global_Strings::init();
        Admin\Admin_Columns::init();

        // Frontend modules.
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-product-display.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-loop-display.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-booking-modal.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-shop-page.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-shortcodes.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-ajax-handlers.php';
        require_once IGS_ECOMMERCE_PATH . 'includes/Frontend/class-portfolio-display.php';

        Frontend\Product_Display::init();
        Frontend\Loop_Display::init();
        Frontend\Booking_Modal::init();
        Frontend\Shop_Page::init();
        Frontend\Shortcodes::init();
        Frontend\Ajax_Handlers::init();
        Frontend\Portfolio_Display::init();
    }
}
