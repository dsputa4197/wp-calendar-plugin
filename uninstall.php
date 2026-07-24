<?php
/**
 * Removes this plugin's data when deleted from wp-admin (not on mere
 * deactivation — WordPress only loads this file for an actual delete).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Static option names, plus the dynamic per-calendar fallback/cache options
// created by WCAL_Feed for any `ics_url`-overridden shortcode (their names
// are suffixed with md5(url), so they can't be listed individually here).
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wcal\\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_wcal\\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_timeout\\_wcal\\_%'" );
