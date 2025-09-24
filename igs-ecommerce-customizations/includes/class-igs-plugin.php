<?php
/**
 * Core plugin bootstrap.
 *
 * @package IGS_Ecommerce_Customizations
 */

namespace IGS_Ecommerce_Customizations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-igs-settings.php';
require_once __DIR__ . '/class-igs-free-shipping-progress.php';
require_once __DIR__ . '/class-igs-sale-badge.php';
require_once __DIR__ . '/class-igs-new-badge.php';
require_once __DIR__ . '/class-igs-checkout-fields.php';

/**
 * Main Plugin class.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Settings handler.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Whether WooCommerce is loaded.
     *
     * @var bool
     */
    private $woocommerce_active = false;

    /**
     * Plugin features instances.
     *
     * @var array<string, object>
     */
    private $features = [];

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
     * Initialize plugin.
     */
    public function init(): void {
        $this->woocommerce_active = class_exists( 'WooCommerce' );

        if ( ! $this->woocommerce_active ) {
            add_action( 'admin_notices', [ $this, 'render_missing_wc_notice' ] );
            return;
        }

        $this->settings = new Settings();
        $this->settings->register();

        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        $this->boot_features();
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( 'igs-ecommerce', false, dirname( plugin_basename( IGS_ECOMMERCE_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Register frontend assets.
     */
    public function enqueue_assets(): void {
        wp_register_style( 'igs-ecommerce-frontend', IGS_ECOMMERCE_PLUGIN_URL . 'assets/css/frontend.css', [], IGS_ECOMMERCE_VERSION );

        $should_enqueue = false;

        if ( $this->settings->is_enabled( 'free_shipping_progress' ) && ( is_cart() || is_checkout() ) ) {
            $should_enqueue = true;
        }

        if ( $this->settings->is_enabled( 'discount_badge' ) || $this->settings->is_enabled( 'new_badge' ) ) {
            $should_enqueue = true;
        }

        if ( $should_enqueue ) {
            wp_enqueue_style( 'igs-ecommerce-frontend' );
        }
    }

    /**
     * Instantiate feature classes.
     */
    private function boot_features(): void {
        if ( $this->settings->is_enabled( 'free_shipping_progress' ) ) {
            $this->features['free_shipping_progress'] = new Free_Shipping_Progress( $this->settings );
            $this->features['free_shipping_progress']->init();
        }

        if ( $this->settings->is_enabled( 'discount_badge' ) ) {
            $this->features['sale_badge'] = new Sale_Badge( $this->settings );
            $this->features['sale_badge']->init();
        }

        if ( $this->settings->is_enabled( 'new_badge' ) ) {
            $this->features['new_badge'] = new New_Badge( $this->settings );
            $this->features['new_badge']->init();
        }

        if ( $this->settings->is_enabled( 'checkout_fields' ) ) {
            $this->features['checkout_fields'] = new Checkout_Fields( $this->settings );
            $this->features['checkout_fields']->init();
        }
    }

    /**
     * Display admin notice when WooCommerce is missing.
     */
    public function render_missing_wc_notice(): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'IGS Ecommerce Customizations richiede WooCommerce attivo per funzionare correttamente.', 'igs-ecommerce' )
        );
    }
}
