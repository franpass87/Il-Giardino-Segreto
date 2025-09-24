<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://www.italiangardentour.com/
 * Description:       Personalizzazioni su misura per tour, portfolio e flussi WooCommerce de Il Giardino Segreto.
 * Version:           1.1.0
 * Author:            Il Giardino Segreto
 * Author URI:        https://www.italiangardentour.com/
 * Text Domain:       igs-ecommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IGS_ECOMMERCE_VERSION', '1.1.0' );
define( 'IGS_ECOMMERCE_PLUGIN_FILE', __FILE__ );
define( 'IGS_ECOMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once IGS_ECOMMERCE_PLUGIN_DIR . 'includes/class-igs-plugin.php';

add_action( 'plugins_loaded', static function () {
    \IGS_Ecommerce_Customizations\Plugin::instance()->init();
} );
