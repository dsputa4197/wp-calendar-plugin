<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal RFC 5545 ICS reader — just enough to read a Google Calendar
 * export of one-off VEVENTs (no RRULE/timezone-component support needed,
 * since Google publishes each occurrence as its own event in UTC).
 */
class WCAL_ICS_Parser {

	/**
	 * @param string $raw Raw .ics file contents.
	 * @return array[] List of ['uid','summary','location','dtstart_ts','dtend_ts']
	 */
	public static function parse( $raw ) {
		$lines  = self::unfold( $raw );
		$events = array();
		$current = null;

		foreach ( $lines as $line ) {
			if ( 'BEGIN:VEVENT' === $line ) {
				$current = array();
				continue;
			}

			if ( 'END:VEVENT' === $line ) {
				if ( null !== $current && isset( $current['dtstart_ts'] ) ) {
					$events[] = $current;
				}
				$current = null;
				continue;
			}

			if ( null === $current ) {
				continue;
			}

			$colon = strpos( $line, ':' );
			if ( false === $colon ) {
				continue;
			}

			$prop  = substr( $line, 0, $colon );
			$value = substr( $line, $colon + 1 );
			$semi  = strpos( $prop, ';' );
			$name  = false !== $semi ? substr( $prop, 0, $semi ) : $prop;

			switch ( $name ) {
				case 'UID':
					$current['uid'] = $value;
					break;
				case 'SUMMARY':
					$current['summary'] = self::unescape_text( $value );
					break;
				case 'LOCATION':
					$current['location'] = self::unescape_text( $value );
					break;
				case 'DTSTART':
					$ts = self::parse_datetime( $value );
					if ( null !== $ts ) {
						$current['dtstart_ts'] = $ts;
					}
					break;
				case 'DTEND':
					$ts = self::parse_datetime( $value );
					if ( null !== $ts ) {
						$current['dtend_ts'] = $ts;
					}
					break;
			}
		}

		return $events;
	}

	/**
	 * Joins RFC 5545 folded continuation lines (any line starting with a
	 * space or tab is a continuation of the previous logical line).
	 *
	 * @return string[]
	 */
	private static function unfold( $raw ) {
		$raw   = str_replace( "\r\n", "\n", $raw );
		$lines = explode( "\n", $raw );
		$out   = array();

		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				continue;
			}
			if ( ( ' ' === $line[0] || "\t" === $line[0] ) && ! empty( $out ) ) {
				$out[ count( $out ) - 1 ] .= substr( $line, 1 );
			} else {
				$out[] = $line;
			}
		}

		return $out;
	}

	private static function unescape_text( $value ) {
		return preg_replace_callback(
			'/\\\\(.)/s',
			static function ( $m ) {
				switch ( $m[1] ) {
					case 'n':
					case 'N':
						return "\n";
					case ',':
						return ',';
					case ';':
						return ';';
					case '\\':
						return '\\';
					default:
						return $m[1];
				}
			},
			$value
		);
	}

	/**
	 * @return int|null Unix timestamp (UTC), or null if unparsable.
	 */
	private static function parse_datetime( $value ) {
		$value = trim( $value );

		if ( preg_match( '/^\d{8}$/', $value ) ) {
			$dt = DateTime::createFromFormat( 'Ymd', $value, new DateTimeZone( 'UTC' ) );
		} elseif ( preg_match( '/^\d{8}T\d{6}Z$/', $value ) ) {
			$dt = DateTime::createFromFormat( 'Ymd\THis\Z', $value, new DateTimeZone( 'UTC' ) );
		} elseif ( preg_match( '/^\d{8}T\d{6}$/', $value ) ) {
			// Floating local time with no explicit zone; assume the site's own timezone.
			$dt = DateTime::createFromFormat( 'Ymd\THis', $value, wp_timezone() );
		} else {
			return null;
		}

		return $dt ? $dt->getTimestamp() : null;
	}
}
