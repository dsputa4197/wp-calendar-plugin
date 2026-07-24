<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCAL_Admin {

	const OPTION_GROUP = 'wcal_settings';
	const PAGE_SLUG    = 'wcal-mass-schedule';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_wcal_refresh_now', array( __CLASS__, 'handle_refresh_now' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_color_picker' ) );
	}

	public static function add_menu() {
		add_options_page(
			__( 'Mass Schedule', 'wp-calendar-plugin' ),
			__( 'Mass Schedule', 'wp-calendar-plugin' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_color_picker( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".wcal-color-field").wpColorPicker();});' );
	}

	public static function register_settings() {
		register_setting( self::OPTION_GROUP, 'wcal_ics_url', array(
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_heading', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Upcoming Events',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_event_label_singular', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Mass',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_event_label_plural', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Masses',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_cache_hours', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
			'default'           => 3,
		) );
		register_setting( self::OPTION_GROUP, 'wcal_months_ahead', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
			'default'           => 6,
		) );
		register_setting( self::OPTION_GROUP, 'wcal_max_events', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
			'default'           => 40,
		) );
		register_setting( self::OPTION_GROUP, 'wcal_initial_months', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_positive_int' ),
			'default'           => 1,
		) );
		register_setting( self::OPTION_GROUP, 'wcal_month_language', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_month_language' ),
			'default'           => 'none',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_accent_color', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_color_or_empty' ),
			'default'           => '#9b0177',
		) );
		register_setting( self::OPTION_GROUP, 'wcal_accent_color_2', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_color_or_empty' ),
			'default'           => '#ba3f9d',
		) );
	}

	public static function sanitize_positive_int( $value ) {
		$value = (int) $value;
		return max( 1, $value );
	}

	public static function sanitize_month_language( $value ) {
		$allowed = array( 'none', 'cs', 'es', 'pl', 'vi' );
		return in_array( $value, $allowed, true ) ? $value : 'none';
	}

	public static function sanitize_color_or_empty( $value ) {
		$value = sanitize_hex_color( $value );
		return $value ? $value : '';
	}

	public static function handle_refresh_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-calendar-plugin' ) );
		}
		check_admin_referer( 'wcal_refresh_now' );

		WCAL_Feed::refresh();

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'wcal_refreshed' => '1' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$month_language = get_option( 'wcal_month_language', 'none' );
		$language_labels = array(
			'none' => __( 'None — English only', 'wp-calendar-plugin' ),
			'cs'   => __( 'Czech', 'wp-calendar-plugin' ),
			'es'   => __( 'Spanish', 'wp-calendar-plugin' ),
			'pl'   => __( 'Polish', 'wp-calendar-plugin' ),
			'vi'   => __( 'Vietnamese', 'wp-calendar-plugin' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mass Schedule', 'wp-calendar-plugin' ); ?></h1>

			<?php if ( isset( $_GET['wcal_refreshed'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Calendar feed refreshed.', 'wp-calendar-plugin' ); ?></p>
				</div>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'Use the shortcode below wherever you want the schedule to appear:', 'wp-calendar-plugin' ); ?>
				<code>[mass_schedule]</code>
			</p>
			<p class="description">
				<?php esc_html_e( 'To show a second calendar somewhere else on the site, add an ics_url attribute, e.g.', 'wp-calendar-plugin' ); ?>
				<code>[mass_schedule ics_url="https://calendar.google.com/.../basic.ics"]</code>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="wcal_ics_url"><?php esc_html_e( 'Google Calendar ICS URL', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="url" id="wcal_ics_url" name="wcal_ics_url" class="regular-text" value="<?php echo esc_attr( get_option( 'wcal_ics_url', '' ) ); ?>" />
							<p class="description">
								<?php esc_html_e( 'From Google Calendar: Settings and sharing → Integrate calendar → "Public address in iCal format".', 'wp-calendar-plugin' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_heading"><?php esc_html_e( 'Heading text', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="text" id="wcal_heading" name="wcal_heading" class="regular-text" value="<?php echo esc_attr( get_option( 'wcal_heading', 'Upcoming Events' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Leave blank to render the schedule with no heading at all.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_event_label_singular"><?php esc_html_e( 'Event noun (singular)', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="text" id="wcal_event_label_singular" name="wcal_event_label_singular" class="regular-text" value="<?php echo esc_attr( get_option( 'wcal_event_label_singular', 'Mass' ) ); ?>" placeholder="Mass" />
							<p class="description"><?php esc_html_e( 'Used for the "Next ..." flag on the soonest date, e.g. "Next Mass" or "Next Class". Leave blank to hide that flag.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_event_label_plural"><?php esc_html_e( 'Event noun (plural)', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="text" id="wcal_event_label_plural" name="wcal_event_label_plural" class="regular-text" value="<?php echo esc_attr( get_option( 'wcal_event_label_plural', 'Masses' ) ); ?>" placeholder="Masses" />
							<p class="description"><?php esc_html_e( 'Used in "Show 3 more ..." and the empty-schedule message, e.g. "Masses" or "Classes".', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_month_language"><?php esc_html_e( 'Second language for month names', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<select id="wcal_month_language" name="wcal_month_language">
								<?php foreach ( $language_labels as $code => $label ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $month_language, $code ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Shows month headers like "Září · September" instead of just "September".', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_accent_color"><?php esc_html_e( 'Accent color', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="text" id="wcal_accent_color" name="wcal_accent_color" class="wcal-color-field" value="<?php echo esc_attr( get_option( 'wcal_accent_color', '#9b0177' ) ); ?>" data-default-color="#9b0177" />
							<p class="description"><?php esc_html_e( 'Used for the date badges, time pill, and the subscribe button.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_accent_color_2"><?php esc_html_e( 'Heading color', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="text" id="wcal_accent_color_2" name="wcal_accent_color_2" class="wcal-color-field" value="<?php echo esc_attr( get_option( 'wcal_accent_color_2', '#ba3f9d' ) ); ?>" data-default-color="#ba3f9d" />
							<p class="description"><?php esc_html_e( 'Used for the widget title.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_initial_months"><?php esc_html_e( 'Months shown before "Show more"', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="number" min="1" id="wcal_initial_months" name="wcal_initial_months" class="small-text" value="<?php echo esc_attr( get_option( 'wcal_initial_months', 1 ) ); ?>" />
							<p class="description"><?php esc_html_e( 'The rest of the fetched months are tucked behind a "Show more" toggle so the widget starts compact.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_months_ahead"><?php esc_html_e( 'Months to fetch ahead', 'wp-calendar-plugin' ); ?></label></th>
						<td><input type="number" min="1" id="wcal_months_ahead" name="wcal_months_ahead" class="small-text" value="<?php echo esc_attr( get_option( 'wcal_months_ahead', 6 ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_max_events"><?php esc_html_e( 'Max dates to show', 'wp-calendar-plugin' ); ?></label></th>
						<td><input type="number" min="1" id="wcal_max_events" name="wcal_max_events" class="small-text" value="<?php echo esc_attr( get_option( 'wcal_max_events', 40 ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wcal_cache_hours"><?php esc_html_e( 'Cache duration (hours)', 'wp-calendar-plugin' ); ?></label></th>
						<td>
							<input type="number" min="1" id="wcal_cache_hours" name="wcal_cache_hours" class="small-text" value="<?php echo esc_attr( get_option( 'wcal_cache_hours', 3 ) ); ?>" />
							<p class="description"><?php esc_html_e( 'How long to remember the fetched schedule before checking Google Calendar again.', 'wp-calendar-plugin' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wcal_refresh_now" />
				<?php wp_nonce_field( 'wcal_refresh_now' ); ?>
				<?php submit_button( __( 'Refresh now', 'wp-calendar-plugin' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}
}
