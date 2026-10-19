<?php

function tender_event_parse_datetime( $value, DateTimeZone $timezone ) {
	foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d' ) as $format ) {
		$date   = DateTimeImmutable::createFromFormat( '!' . $format, (string) $value, $timezone );
		$errors = DateTimeImmutable::getLastErrors();
		if ( $date instanceof DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
			return $date;
		}
	}

	return false;
}

function tender_event_next_occurrence( DateTimeImmutable $start, DateTimeImmutable $from, $until = null ) {
	$occurrence = $start;

	while ( $occurrence < $from && ( ! $until || $occurrence <= $until ) ) {
		// Calendar arithmetic retains the local event time across DST changes.
		$occurrence = $occurrence->modify( '+1 week' );
	}

	return ( ! $until || $occurrence <= $until ) ? $occurrence : false;
}

function tender_event_is_valid_occurrence( DateTimeImmutable $candidate, DateTimeImmutable $start, $until = null ): bool {
	$occurrence = $start;

	while ( $occurrence < $candidate && ( ! $until || $occurrence <= $until ) ) {
		$occurrence = $occurrence->modify( '+1 week' );
	}

	return $occurrence == $candidate && ( ! $until || $candidate <= $until );
}

function tender_event_is_recurrent( $post_id ): bool {
	$value = carbon_get_post_meta( $post_id, 'tender_event_recurrent' );

	return in_array( $value, array( 'yes', '1', 1, true ), true );
}

function tender_event_recurrence_label_from_start_ts( int $start_ts ): string {
	$weekday = wp_date( 'l', $start_ts );
	return sprintf(
		/* translators: %s: weekday (e.g., martes) */
		__( 'Recurring event, every %s', 'tender-library' ),
		mb_strtolower( $weekday )
	);
}

function tender_event_date_render_callback( $block_attributes, $block_content ) {

	$block_classes = tender_get_block_classes(
		$block_attributes,
		'wp-block-tender-a-library-event-date'
	);

	$post_id      = get_the_ID();
	$is_recurrent = tender_event_is_recurrent( $post_id );
	$timezone     = wp_timezone();
	$raw_start    = carbon_get_post_meta( $post_id, 'tender_event_startdate' );
	$start         = tender_event_parse_datetime( $raw_start, $timezone );

	if ( ! $start ) return '';

	$start_ts = $start->getTimestamp();

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	$forced_occ_ts = isset( $_GET['occ_ts'] ) ? absint( $_GET['occ_ts'] ) : 0;

	// Defaults (evento no recurrente)
	$occ_ts    = $start_ts;
	$rec_label = '';
	$end_date  = '';

	if ( $is_recurrent ) {
		$raw_end  = carbon_get_post_meta( $post_id, 'tender_event_enddate' ); // Y-m-d (se lee una vez)
		$until    = $raw_end ? tender_event_parse_datetime( $raw_end . ' 23:59:59', $timezone ) : null;
		$from     = current_datetime();
		$forced   = $forced_occ_ts ? ( new DateTimeImmutable( '@' . $forced_occ_ts ) )->setTimezone( $timezone ) : null;

		if ( $forced && tender_event_is_valid_occurrence( $forced, $start, $until ) ) {
			$occurrence = $forced;
		} else {
			$occurrence = tender_event_next_occurrence( $start, $from, $until );
		}

		$occ_ts = $occurrence ? $occurrence->getTimestamp() : $start_ts;

		$rec_label = tender_event_recurrence_label_from_start_ts( $start_ts );

		if ( $until ) {
			$end_date = sprintf(
				/* translators: %s: end date */
				__( 'until %s', 'tender-library' ),
				wp_date( $date_format, $until->setTime( 0, 0, 0 )->getTimestamp() )
			);
		}
	}

	ob_start();
	?>
	<section class="<?php echo esc_attr( $block_classes ); ?>">
		<div class="block-container">

			<p class="tender-event-date">
				<span class="tender-event-date__dt">
					<?php echo esc_html( wp_date( $date_format . ' ' . $time_format, $occ_ts ) ); ?>
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
						__( 'Since %1$s %2$s', 'tender-library' ),
							wp_date( $date_format, $start_ts ),
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
