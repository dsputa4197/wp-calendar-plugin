<?php
/**
 * @var array  $months               Month buckets from WCAL_Shortcode::grouped_by_month().
 * @var string $title                Heading text. Empty string skips the heading entirely.
 * @var string $subscribe_url        webcal:// link to the source calendar, or ''.
 * @var int    $initial_months       Month buckets to render expanded before the rest fold behind "Show more".
 * @var int    $hidden_event_count   Events inside the folded months.
 * @var string $next_label           e.g. "Next Mass" — shown on the soonest event.
 * @var string $empty_label          e.g. "Masses" — used in the empty/not-scheduled state.
 * @var string $show_more_label_tpl  sprintf template with one %d, e.g. "Show %d more Masses".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders one month's divider + timeline of rows. Kept as a closure so the
 * same markup can run both above and inside the <details> fold below.
 */
$render_month = static function ( array $month, &$flagged_next ) use ( $next_label ) {
	?>
	<div class="wcal-month">
		<span class="wcal-rule"></span>
		<span class="wcal-month-label"><?php echo esc_html( $month['label'] ); ?></span>
	</div>
	<div class="wcal-timeline">
		<?php foreach ( $month['events'] as $event ) : ?>
			<div class="wcal-row">
				<div class="wcal-stub">
					<span class="wcal-dow"><?php echo esc_html( $event['dow'] ); ?></span>
					<span class="wcal-day"><?php echo esc_html( $event['day'] ); ?></span>
					<span class="wcal-mark">&#10010;</span>
				</div>
				<div class="wcal-body">
					<?php if ( ! $flagged_next && '' !== $next_label ) : ?>
						<span class="wcal-next"><?php echo esc_html( $next_label ); ?></span>
						<?php $flagged_next = true; ?>
					<?php endif; ?>
					<div class="wcal-top">
						<div class="wcal-city"><?php echo esc_html( $event['summary'] ); ?></div>
						<div class="wcal-time"><?php echo esc_html( $event['time'] ); ?></div>
					</div>
					<?php if ( '' !== $event['location'] ) : ?>
						<div class="wcal-address"><?php echo esc_html( $event['location'] ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
};
?>
<div class="wcal-schedule">
	<?php if ( '' !== trim( $title ) ) : ?>
		<div class="wcal-heading">
			<span class="wcal-rule"></span>
			<span class="wcal-heading-title"><?php echo esc_html( $title ); ?></span>
			<span class="wcal-rule"></span>
		</div>
	<?php endif; ?>

	<?php if ( empty( $months ) ) : ?>

		<p class="wcal-empty">
			<?php
			printf(
				/* translators: %s: plural event noun, e.g. "Masses" */
				esc_html__( 'No upcoming %s are scheduled right now — please check back soon.', 'wp-calendar-plugin' ),
				esc_html( $empty_label )
			);
			?>
		</p>

	<?php else : ?>

		<?php
		$flagged_next = false;
		$index        = 0;
		foreach ( $months as $month ) :
			if ( $index === $initial_months && $hidden_event_count > 0 ) :
				?>
				<details class="wcal-more">
					<summary class="wcal-more-toggle">
						<?php echo esc_html( sprintf( $show_more_label_tpl, $hidden_event_count ) ); ?>
					</summary>
				<?php
			endif;
			$render_month( $month, $flagged_next );
			++$index;
		endforeach;
		if ( $index > $initial_months && $hidden_event_count > 0 ) :
			?>
			</details>
			<?php
		endif;
		?>

	<?php endif; ?>

	<?php if ( '' !== $subscribe_url ) : ?>
		<div class="wcal-foot">
			<a class="wcal-subscribe" href="<?php echo esc_url( $subscribe_url ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="1.5"></rect><path d="M3 9h18M8 2v4M16 2v4"></path></svg>
				<?php esc_html_e( 'Add to your calendar', 'wp-calendar-plugin' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
