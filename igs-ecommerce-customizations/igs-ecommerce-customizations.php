<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://github.com/franpass87/Il-Giardino-Segreto
 * Description:       Personalizzazioni WooCommerce per Il Giardino Segreto: tour, date, prenotazioni, mappa.
 * Version:           2.3.1
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

define('IGS_VERSION', '2.3.0');
define('IGS_FILE', __FILE__);
define('IGS_DIR', plugin_dir_path(__FILE__));
define('IGS_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain(
        'igs-ecommerce',
        false,
        dirname(plugin_basename(IGS_FILE)) . '/languages'
    );

    if (!class_exists('WooCommerce')) {
        return;
    }
    if (!file_exists(IGS_DIR . 'vendor/autoload.php')) {
        add_action('admin_notices', static function (): void {
            if (current_user_can('manage_options')) {
                echo '<div class="notice notice-error"><p><strong>IGS Ecommerce:</strong> ' . esc_html__('Esegui composer install nella cartella del plugin.', 'igs-ecommerce') . '</p></div>';
            }
        });
        return;
    }
    require_once IGS_DIR . 'vendor/autoload.php';
    \IGS\Ecommerce\Core\Plugin::instance()->init();
}, 5);
