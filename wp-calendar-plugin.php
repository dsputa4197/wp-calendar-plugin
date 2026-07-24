<?php
/**
 * Plugin Name: Google Calendar Schedule
 * Description: Renders an upcoming-events schedule from a public Google Calendar feed via the [calendar_schedule] shortcode.
 * Version: 1.1.1
 * Author: Daniel Sputa
 * License: GPL-2.0-or-later
 * Text Domain: wp-calendar-plugin
 * GitHub Plugin URI: dsputa4197/wp-calendar-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCAL_VERSION', '1.1.1' );
define( 'WCAL_PLUGIN_FILE', __FILE__ );
define( 'WCAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCAL_TRANSIENT_KEY', 'wcal_events' );
define( 'WCAL_FALLBACK_OPTION', 'wcal_events_fallback' );

require_once WCAL_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
require_once WCAL_PLUGIN_DIR . 'includes/class-wcal-ics-parser.php';
require_once WCAL_PLUGIN_DIR . 'includes/class-wcal-feed.php';
require_once WCAL_PLUGIN_DIR . 'includes/class-wcal-shortcode.php';
require_once WCAL_PLUGIN_DIR . 'includes/class-wcal-admin.php';

/**
 * Checks GitHub Releases for this repo and shows the normal wp-admin
 * "Update available" notice/one-click-update UI when a new one is tagged.
 * See DEVELOPMENT.md for how to cut a release.
 */
function wcal_init_update_checker() {
	$update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/dsputa4197/wp-calendar-plugin/',
		WCAL_PLUGIN_FILE,
		'wp-calendar-plugin'
	);
	$update_checker->getVcsApi()->enableReleaseAssets();
}
add_action( 'plugins_loaded', 'wcal_init_update_checker' );

/**
 * No site-specific defaults here on purpose — this plugin is public, so a
 * stranger's first activation should start empty rather than silently
 * pulling someone else's calendar. wcal_maybe_upgrade() below is what keeps
 * an already-configured site's settings intact across updates.
 */
function wcal_activate() {
	add_option( 'wcal_ics_url', '' );
	add_option( 'wcal_heading', 'Upcoming Events' );
	add_option( 'wcal_cache_hours', 3 );
	add_option( 'wcal_months_ahead', 6 );
	add_option( 'wcal_max_events', 40 );
	add_option( 'wcal_initial_months', 1 );
	update_option( 'wcal_schema_version', WCAL_VERSION );
}
register_activation_hook( WCAL_PLUGIN_FILE, 'wcal_activate' );

function wcal_deactivate() {
	delete_transient( WCAL_TRANSIENT_KEY );
}
register_deactivation_hook( WCAL_PLUGIN_FILE, 'wcal_deactivate' );

/**
 * Runs once per version bump (not just on activate, since WordPress never
 * re-fires register_activation_hook for an update). Its only job is to add
 * options introduced *after* a site's first activation, with a value that
 * preserves what that site was already doing — new installs never hit the
 * "existing install" branch, so they just get the plain new-plugin defaults.
 */
function wcal_maybe_upgrade() {
	if ( get_option( 'wcal_schema_version' ) === WCAL_VERSION ) {
		return;
	}

	$is_existing_install = false !== get_option( 'wcal_ics_url', false );

	if ( $is_existing_install ) {
		// Sites that were already active before the second-language month
		// setting existed defaulted to a bilingual Czech/English label; keep
		// that behavior rather than silently switching them to English-only.
		add_option( 'wcal_month_language', 'cs' );
	}

	update_option( 'wcal_schema_version', WCAL_VERSION );
}
add_action( 'plugins_loaded', 'wcal_maybe_upgrade' );

/**
 * Enqueued unconditionally (rather than gated behind has_shortcode()) since
 * the shortcode can end up inside a text widget or a Fusion Builder element
 * whose content wp_enqueue_scripts can't reliably inspect. The stylesheet is
 * a single small file, so the always-on cost is negligible.
 */
function wcal_register_assets() {
	wp_enqueue_style( 'wcal-schedule', WCAL_PLUGIN_URL . 'assets/css/schedule.css', array(), WCAL_VERSION );

	$accent   = sanitize_hex_color( get_option( 'wcal_accent_color', '#9b0177' ) );
	$accent_2 = sanitize_hex_color( get_option( 'wcal_accent_color_2', '#ba3f9d' ) );

	if ( $accent || $accent_2 ) {
		$css = '.wcal-schedule{';
		if ( $accent ) {
			$css .= '--wcal-accent:' . $accent . ';--wcal-accent-wash:' . wcal_tint_hex_color( $accent, 0.92 ) . ';';
		}
		if ( $accent_2 ) {
			$css .= '--wcal-accent-2:' . $accent_2 . ';';
		}
		$css .= '}';
		wp_add_inline_style( 'wcal-schedule', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'wcal_register_assets' );

/**
 * Lightens a hex color toward white by $amount (0 = unchanged, 1 = white),
 * used to derive the pale accent-wash background from just one admin-picked
 * accent color instead of asking for a second color on a settings screen.
 */
function wcal_tint_hex_color( $hex, $amount ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		return '#f8ecf4';
	}
	list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
	$r                 = (int) round( $r + ( 255 - $r ) * $amount );
	$g                 = (int) round( $g + ( 255 - $g ) * $amount );
	$b                 = (int) round( $b + ( 255 - $b ) * $amount );
	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

WCAL_Shortcode::init();
WCAL_Admin::init();
