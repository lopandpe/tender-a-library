<?php

function tender_event_parse_start_ts( $post_id ): int {
	$raw = carbon_get_post_meta( $post_id, 'tender_event_startdate' );
	if ( ! $raw ) return 0;
	return is_numeric( $raw ) ? (int) $raw : ( ( $ts = strtotime( (string) $raw ) ) ? (int) $ts : 0 );
}

function tender_event_parse_until_ts( $post_id, int $fallback_range_end = 0 ): int {
	$raw_end = carbon_get_post_meta( $post_id, 'tender_event_enddate' ); // Y-m-d
	if ( $raw_end && ( $until = strtotime( $raw_end . ' 23:59:59' ) ) ) {
		return (int) $until;
	}
	return $fallback_range_end > 0 ? $fallback_range_end : PHP_INT_MAX;
}

function tender_event_next_occurrence_ts( int $start_ts, int $from_ts, int $until_ts ): int {
	if ( ! $start_ts ) return 0;
	if ( $from_ts <= $start_ts ) return ( $start_ts <= $until_ts ) ? $start_ts : 0;

	$week = WEEK_IN_SECONDS;
	$k    = (int) floor( ( $from_ts - $start_ts ) / $week );
	$occ  = $start_ts + ( $k * $week );
	if ( $occ < $from_ts ) $occ += $week;

	return ( $occ <= $until_ts ) ? $occ : 0;
}

function tender_event_recurrence_label_from_start_ts( int $start_ts ): string {
	$weekday = date_i18n( 'l', $start_ts );
	return sprintf(
		/* translators: %s: weekday (e.g., martes) */
		__( 'Recurring event, every %s', 'tender-a-library' ),
		mb_strtolower( $weekday )
	);
}

function tender_event_date_render_callback( $block_attributes, $block_content ) {

	$block_classes = tender_get_block_classes(
		$block_attributes,
		'wp-block-tender-a-library-event-date'
	);

	$post_id      = get_the_ID();
	$is_recurrent = (bool) carbon_get_post_meta( $post_id, 'tender_event_recurrent' );

	$start_ts = tender_event_parse_start_ts( $post_id );
	if ( ! $start_ts ) return '';

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	$forced_occ_ts = isset( $_GET['occ_ts'] ) ? (int) $_GET['occ_ts'] : 0;

	// Defaults (evento no recurrente)
	$occ_ts    = $start_ts;
	$rec_label = '';
	$end_date  = '';

	if ( $is_recurrent ) {
		$raw_end  = carbon_get_post_meta( $post_id, 'tender_event_enddate' ); // Y-m-d (se lee una vez)
		$until_ts = $raw_end ? (int) strtotime( $raw_end . ' 23:59:59' ) : PHP_INT_MAX;

		$occ_ts = $forced_occ_ts ?: tender_event_next_occurrence_ts( $start_ts, current_time( 'timestamp' ), $until_ts );
		$occ_ts = $occ_ts ?: $start_ts;

		$rec_label = tender_event_recurrence_label_from_start_ts( $start_ts );

		if ( $raw_end && ( $end_day_ts = strtotime( $raw_end . ' 00:00:00' ) ) ) {
			$end_date = sprintf(
				/* translators: %s: end date */
				__( 'until %s', 'tender-a-library' ),
				date_i18n( $date_format, $end_day_ts )
			);
		}
	}

	ob_start();
	?>
	<section class="<?php echo esc_attr( $block_classes ); ?>">
		<div class="block-container">

			<p class="tender-event-date">
				<span class="tender-event-date__dt">
					<?php echo esc_html( date_i18n( $date_format . ' ' . $time_format, $occ_ts ) ); ?>
				</span>
			</p>

			<p class="tender-event-date__recurrent">
				<?php if ( $rec_label ) : ?>
					<span class="tender-event-date__rec"><?php echo esc_html( $rec_label ); ?></span>
				<?php endif; ?>
			</p>

			<?php if ( $is_recurrent ) : ?>
				<p class="tender-event-date__start">
					<?php
					// Mismo resultado visual: "Since {date} {until ...}"
					echo esc_html( sprintf(
						__( 'Since %1$s %2$s', 'tender-a-library' ),
						date_i18n( $date_format, $start_ts ),
						$end_date
					) );
					?>
				</p>
			<?php endif; ?>

			<?php if ( function_exists( 'tal_render_event_feed_links' ) ) : ?>
				<?php echo tal_render_event_feed_links( $post_id ); ?>
			<?php endif; ?>

		</div>
	</section>
	<?php
	return ob_get_clean();
}
