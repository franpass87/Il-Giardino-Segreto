<?php
/**
 * Plugin uninstall routine.
 *
 * @package IGS_Ecommerce_Customizations
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('gw_string_replacements_global');
delete_site_option('gw_string_replacements_global');
