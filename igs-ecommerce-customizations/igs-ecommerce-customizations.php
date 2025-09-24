<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://example.com/
 * Description:       Raccolta di personalizzazioni per Il Giardino Segreto su WooCommerce.
 * Version:           1.3.0
 * Author:            Il Giardino Segreto
 * Author URI:        https://example.com/
 * Text Domain:       igs-ecommerce
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IGS_ECOMMERCE_VERSION', '1.3.0' );
define( 'IGS_ECOMMERCE_FILE', __FILE__ );
define( 'IGS_ECOMMERCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'IGS_ECOMMERCE_URL', plugin_dir_url( __FILE__ ) );

require_once IGS_ECOMMERCE_PATH . 'includes/class-dependencies.php';

register_activation_hook( IGS_ECOMMERCE_FILE, [ 'IGS\\Ecommerce\\Dependencies', 'on_activation' ] );

if ( ! IGS\Ecommerce\Dependencies::bootstrap() ) {
    return;
}

require_once IGS_ECOMMERCE_PATH . 'includes/class-igs-ecommerce-customizations.php';

IGS\Ecommerce\Plugin::instance();
