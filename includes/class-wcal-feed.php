<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and caches the parsed event list from an ICS URL. Supports more
 * than one calendar (the configured default, plus any per-shortcode
 * `ics_url` overrides): the default calendar keeps the plain cache keys
 * this plugin has always used, while an override gets its own keys derived
 * from its URL so multiple calendars can cache independently.
 */
class WCAL_Feed {

	/**
	 * @param string $ics_url Feed URL, or '' to use the configured default.
	 * @return array[] Parsed events (see WCAL_ICS_Parser::parse()).
	 */
	public static function get_events( $ics_url = '' ) {
		$cached = get_transient( self::transient_key( $ics_url ) );
		if ( false !== $cached ) {
			return $cached;
		}

		return self::refresh( $ics_url );
	}

	/**
	 * Fetches the feed now, ignoring the cache, and re-primes it.
	 * Falls back to the last known-good copy if the request fails.
	 *
	 * @param string $ics_url Feed URL, or '' to use the configured default.
	 * @return array[]
	 */
	public static function refresh( $ics_url = '' ) {
		$resolved_url  = self::resolve_url( $ics_url );
		$fallback_key  = self::fallback_key( $ics_url );
		$transient_key = self::transient_key( $ics_url );

		if ( '' === $resolved_url ) {
			return get_option( $fallback_key, array() );
		}

		$response = wp_remote_get(
			$resolved_url,
			array(
				'timeout'    => 15,
				'user-agent' => 'WordPress/Mass-Schedule-Plugin',
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return get_option( $fallback_key, array() );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === trim( (string) $body ) ) {
			return get_option( $fallback_key, array() );
		}

		$events = WCAL_ICS_Parser::parse( $body );

		// Only trust and persist a fetch that actually produced events —
		// an empty parse usually means a transient upstream hiccup, not an
		// empty calendar, so keep serving the last good copy instead.
		if ( ! empty( $events ) ) {
			update_option( $fallback_key, $events, false );
		} else {
			$events = get_option( $fallback_key, array() );
		}

		$hours = max( 1, (int) get_option( 'wcal_cache_hours', 3 ) );
		set_transient( $transient_key, $events, $hours * HOUR_IN_SECONDS );

		return $events;
	}

	private static function is_override( $ics_url ) {
		return '' !== trim( (string) $ics_url );
	}

	private static function resolve_url( $ics_url ) {
		return self::is_override( $ics_url )
			? trim( (string) $ics_url )
			: trim( (string) get_option( 'wcal_ics_url', '' ) );
	}

	private static function transient_key( $ics_url ) {
		return self::is_override( $ics_url )
			? WCAL_TRANSIENT_KEY . '_' . md5( trim( (string) $ics_url ) )
			: WCAL_TRANSIENT_KEY;
	}

	private static function fallback_key( $ics_url ) {
		return self::is_override( $ics_url )
			? WCAL_FALLBACK_OPTION . '_' . md5( trim( (string) $ics_url ) )
			: WCAL_FALLBACK_OPTION;
	}
}
