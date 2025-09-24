<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://example.com/
 * Description:       Raccolta di personalizzazioni per Il Giardino Segreto su WooCommerce.
 * Version:           1.0.0
 * Author:            Il Giardino Segreto
 * Author URI:        https://example.com/
 * Text Domain:       igs-ecommerce
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IGS_ECOMMERCE_VERSION', '1.0.0' );
define( 'IGS_ECOMMERCE_PLUGIN_FILE', __FILE__ );
define( 'IGS_ECOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IGS_ECOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once IGS_ECOMMERCE_PLUGIN_DIR . 'includes/class-igs-plugin.php';

add_action( 'plugins_loaded', static function () {
    \IGS_Ecommerce_Customizations\Plugin::instance()->init();
} );
