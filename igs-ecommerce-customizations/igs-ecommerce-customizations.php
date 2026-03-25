<?php
/**
 * Plugin Name:       IGS Ecommerce Customizations
 * Plugin URI:        https://github.com/franpass87/Il-Giardino-Segreto
 * Description:       Personalizzazioni WooCommerce per Il Giardino Segreto: tour, date, prenotazioni, mappa.
 * Version:           2.3.2
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

define('IGS_VERSION', '2.3.2');
define('IGS_FILE', __FILE__);
define('IGS_DIR', plugin_dir_path(__FILE__));
define('IGS_URL', plugin_dir_url(__FILE__));

/**
 * Carica subito le traduzioni WooCommerce da wp-content/languages se presenti, così il primo __(..., 'woocommerce')
 * non attiva il caricamento JIT prima di after_setup_theme (WordPress 6.7+).
 */
function igs_preload_woocommerce_textdomain_early(): void {
    if (!function_exists('determine_locale') || !function_exists('load_textdomain')) {
        return;
    }
    $wc_main = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
    if (!is_readable($wc_main)) {
        return;
    }
    $locale = determine_locale();
    $candidates = [
        WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo',
        WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo',
    ];
    foreach ($candidates as $mofile) {
        if (is_readable($mofile)) {
            load_textdomain('woocommerce', $mofile, $locale);
            return;
        }
    }
}

igs_preload_woocommerce_textdomain_early();

/**
 * WooCommerce chiama __() sui widget durante includes() nel costruttore, prima di after_setup_theme: con WP 6.7+
 * _load_textdomain_just_in_time emette _doing_it_wrong anche se non c’è un .mo (es. locale en_US).
 * Sopprime solo questo caso per il dominio woocommerce; il resto degli avvisi resta attivo.
 */
add_filter(
    'doing_it_wrong_trigger_error',
    static function ($trigger, $function_name, $message, $version) {
        if ('_load_textdomain_just_in_time' !== $function_name) {
            return $trigger;
        }
        if (!is_string($message) || !str_contains($message, 'woocommerce')) {
            return $trigger;
        }
        return false;
    },
    10,
    4
);

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
