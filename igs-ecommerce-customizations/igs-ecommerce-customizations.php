<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://github.com/franpass87/Il-Giardino-Segreto
 * Description:       Personalizzazioni WooCommerce per Il Giardino Segreto: tour, date, prenotazioni, mappa.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Francesco Passeri
 * Author URI:        https://francescopasseri.com
 * License:           Proprietary
 * Text Domain:       igs-ecommerce
 * GitHub Plugin URI: franpass87/Il-Giardino-Segreto
 * Primary Branch:    main
 */

defined('ABSPATH') || exit;

define('IGS_VERSION', '2.0.0');
define('IGS_FILE', __FILE__);
define('IGS_DIR', plugin_dir_path(__FILE__));
define('IGS_URL', plugin_dir_url(__FILE__));

if (file_exists(IGS_DIR . 'vendor/autoload.php')) {
    require_once IGS_DIR . 'vendor/autoload.php';
}

add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }
    \IGS\Ecommerce\Core\Plugin::instance()->init();
});
