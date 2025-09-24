<?php
/**
 * Plugin uninstall routine.
 *
 * @package IGS_Ecommerce_Customizations
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin specific options.
delete_option( 'gw_string_replacements_global' );
delete_site_option( 'gw_string_replacements_global' );

// Clear transients used by the geocoding helper.
delete_transient( 'igs_geocode_rate_limit' );
delete_site_transient( 'igs_geocode_rate_limit' );

global $wpdb;

if ( isset( $wpdb->options ) ) {
    $like_base    = $wpdb->esc_like( '_transient_igs_geocode_' ) . '%';
    $like_timeout = $wpdb->esc_like( '_transient_timeout_igs_geocode_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_timeout ) );

    // Remove stored booking/info rate limit windows.
    $info_like_base    = $wpdb->esc_like( '_transient_igs_info_rl_' ) . '%';
    $info_like_timeout = $wpdb->esc_like( '_transient_timeout_igs_info_rl_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $info_like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $info_like_timeout ) );

    // Remove cached translation catalogues.
    $translations_like_base    = $wpdb->esc_like( '_transient_igs_translation_' ) . '%';
    $translations_like_timeout = $wpdb->esc_like( '_transient_timeout_igs_translation_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $translations_like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $translations_like_timeout ) );
}

if ( is_multisite() && isset( $wpdb->sitemeta ) ) {
    $like_base    = $wpdb->esc_like( '_site_transient_igs_geocode_' ) . '%';
    $like_timeout = $wpdb->esc_like( '_site_transient_timeout_igs_geocode_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like_timeout ) );

    // Remove stored booking/info rate limit windows on the network table too.
    $info_like_base    = $wpdb->esc_like( '_site_transient_igs_info_rl_' ) . '%';
    $info_like_timeout = $wpdb->esc_like( '_site_transient_timeout_igs_info_rl_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $info_like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $info_like_timeout ) );

    // Remove cached translation catalogues from the network table as well.
    $translations_like_base    = $wpdb->esc_like( '_site_transient_igs_translation_' ) . '%';
    $translations_like_timeout = $wpdb->esc_like( '_site_transient_timeout_igs_translation_' ) . '%';

    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $translations_like_base ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $translations_like_timeout ) );
}
