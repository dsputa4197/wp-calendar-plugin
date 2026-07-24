<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCAL_Shortcode {

	public static function init() {
		add_shortcode( 'mass_schedule', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'months'         => get_option( 'wcal_months_ahead', 6 ),
				'limit'          => get_option( 'wcal_max_events', 40 ),
				'title'          => get_option( 'wcal_heading', 'Mass Schedule' ),
				'initial_months' => get_option( 'wcal_initial_months', 1 ),
				// Lets one page show a second, different calendar alongside the
				// site-wide default configured in Settings, e.g.
				// [mass_schedule ics_url="https://calendar.google.com/.../basic.ics"].
				'ics_url'        => '',
			),
			$atts,
			'mass_schedule'
		);

		$ics_url = '' !== trim( (string) $atts['ics_url'] )
			? esc_url_raw( trim( (string) $atts['ics_url'] ) )
			: trim( (string) get_option( 'wcal_ics_url', '' ) );

		if ( '' === $ics_url ) {
			ob_start();
			self::render_not_configured();
			return ob_get_clean();
		}

		$tz        = wp_timezone();
		$now       = new DateTime( 'now', $tz );
		$day_start = ( clone $now )->setTime( 0, 0, 0 )->getTimestamp();
		$cutoff    = ( clone $now )->modify( '+' . max( 1, (int) $atts['months'] ) . ' months' )->getTimestamp();

		$events = WCAL_Feed::get_events( $ics_url );

		$upcoming = array_values(
			array_filter(
				$events,
				static function ( $event ) use ( $day_start, $cutoff ) {
					return isset( $event['dtstart_ts'] )
						&& $event['dtstart_ts'] >= $day_start
						&& $event['dtstart_ts'] <= $cutoff;
				}
			)
		);

		usort(
			$upcoming,
			static function ( $a, $b ) {
				return $a['dtstart_ts'] <=> $b['dtstart_ts'];
			}
		);

		$upcoming = array_slice( $upcoming, 0, max( 1, (int) $atts['limit'] ) );

		$subscribe_url  = self::to_webcal_url( $ics_url );
		$months         = self::grouped_by_month( $upcoming, $tz );
		$title          = $atts['title'];
		$initial_months = max( 1, (int) $atts['initial_months'] );

		// How many events live in the month buckets beyond the initially
		// expanded ones, so the "Show more" toggle can say how many are hidden.
		$hidden_event_count = 0;
		foreach ( array_slice( $months, $initial_months, null, true ) as $hidden_month ) {
			$hidden_event_count += count( $hidden_month['events'] );
		}

		// Not every calendar this plugin renders is a Mass schedule, so the
		// noun used in "Next Mass" / "Show 3 more Masses" is configurable.
		// Leaving the singular blank hides the "Next ..." flag entirely.
		$singular = trim( (string) get_option( 'wcal_event_label_singular', 'Mass' ) );
		$plural   = trim( (string) get_option( 'wcal_event_label_plural', 'Masses' ) );
		$plural   = '' !== $plural ? $plural : 'events';

		$next_label = '' !== $singular
			/* translators: %s: singular event noun, e.g. "Mass" */
			? sprintf( __( 'Next %s', 'wp-calendar-plugin' ), $singular )
			: '';
		$empty_label = $plural;
		/* translators: %s: plural event noun, e.g. "Masses" — the %%d becomes a literal %d for a later sprintf() with the count */
		$show_more_label_tpl = sprintf( __( 'Show %%d more %s', 'wp-calendar-plugin' ), $plural );

		ob_start();
		include WCAL_PLUGIN_DIR . 'templates/schedule.php';
		return ob_get_clean();
	}

	/**
	 * Shown instead of the schedule when no calendar URL is set at all —
	 * only site admins get an actionable message; other visitors see a
	 * generic placeholder so the widget never looks broken to the public.
	 */
	private static function render_not_configured() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wcal-schedule">
			<p class="wcal-empty">
				<?php
				printf(
					/* translators: %s: link to the plugin's settings page */
					esc_html__( 'No calendar is configured yet. %s', 'wp-calendar-plugin' ),
					'<a href="' . esc_url( admin_url( 'options-general.php?page=wcal-mass-schedule' ) ) . '">' . esc_html__( 'Add your Google Calendar link.', 'wp-calendar-plugin' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Month names in a second language, shown alongside the (always English)
	 * base name — e.g. "Září · September". Add a language here and to the
	 * dropdown in class-wcal-admin.php to support another one.
	 */
	private static function month_translations() {
		return array(
			'cs' => array( 1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben', 5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen', 9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec' ),
			'es' => array( 1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre' ),
			'pl' => array( 1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień', 5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień', 9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień' ),
			'vi' => array( 1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba', 4 => 'Tháng Tư', 5 => 'Tháng Năm', 6 => 'Tháng Sáu', 7 => 'Tháng Bảy', 8 => 'Tháng Tám', 9 => 'Tháng Chín', 10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai' ),
		);
	}

	/**
	 * Groups already-sorted events into ['label' => ..., 'events' => [...]]
	 * buckets keyed by calendar month, in display order.
	 */
	private static function grouped_by_month( array $upcoming, DateTimeZone $tz ) {
		$translations = self::month_translations();
		$language     = get_option( 'wcal_month_language', 'none' );
		$second_lang  = isset( $translations[ $language ] ) ? $translations[ $language ] : null;

		$months        = array();
		$last_location = null;

		foreach ( $upcoming as $event ) {
			$dt  = new DateTime( '@' . $event['dtstart_ts'] );
			$dt->setTimezone( $tz );
			$key = $dt->format( 'Y-m' );

			if ( ! isset( $months[ $key ] ) ) {
				$english_month = $dt->format( 'F' );
				$label         = $second_lang ? $second_lang[ (int) $dt->format( 'n' ) ] . ' · ' . $english_month : $english_month;

				$months[ $key ] = array(
					/**
					 * Filters the rendered month divider label, e.g. "Září · September".
					 *
					 * @param string   $label Default label.
					 * @param DateTime $dt    First day represented by this month bucket.
					 */
					'label'  => apply_filters( 'wcal_month_label', $label, $dt ),
					'events' => array(),
				);
			}

			$location = isset( $event['location'] ) ? $event['location'] : '';

			// Skip repeating the address when it's the same venue as the Mass
			// right before it — the schedule rotates through the same handful
			// of churches, so most weeks are a repeat of a location already shown.
			$display_location = ( '' !== $location && $location === $last_location ) ? '' : $location;
			$last_location     = $location;

			$summary = isset( $event['summary'] ) ? $event['summary'] : '';

			$months[ $key ]['events'][] = array(
				/**
				 * Filters an individual event's display title.
				 *
				 * @param string $summary Raw SUMMARY value from the calendar.
				 * @param array  $event   Parsed event (dtstart_ts, location, uid, ...).
				 */
				'summary'  => apply_filters( 'wcal_event_summary', $summary, $event ),
				'location' => $display_location,
				'dow'      => $dt->format( 'D' ),
				'day'      => $dt->format( 'j' ),
				'time'     => $dt->format( 'g:i A' ),
			);
		}

		return $months;
	}

	/**
	 * Rewrites an https ICS URL to a webcal:// link so clicking it offers
	 * to subscribe in the visitor's own calendar app, rather than downloading it.
	 */
	private static function to_webcal_url( $ics_url ) {
		$ics_url = trim( (string) $ics_url );
		if ( '' === $ics_url ) {
			return '';
		}
		return preg_replace( '#^https?://#i', 'webcal://', $ics_url );
	}
}
