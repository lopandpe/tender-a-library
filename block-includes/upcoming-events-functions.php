<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_upcoming_events_render_callback( $attributes, $content = '', $block = null ) {
    $mode  = isset( $attributes['mode'] ) ? $attributes['mode'] : 'calendar';
    $limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 4;

    if ( $mode === 'list' ) {
        return tender_render_events_list( $limit );
    }

    return tender_render_events_calendar();
}

function tender_render_events_list( $limit = 5 ) {
    $now          = current_datetime();
    // "Upcoming" includes the whole current local day, regardless of event time.
    $range_start  = $now->setTime( 0, 0, 0 )->getTimestamp();
    // For example, next 6 months
    $range_end    = $now->modify( '+6 months' )->getTimestamp();

    $occurrences = tender_get_event_occurrences_in_range( $range_start, $range_end, $limit );

    ob_start();

    echo '<div class="wp-block-tender-events wp-block-tender-events--list">';
    if ( empty( $occurrences ) ) {
        echo '<p>' . esc_html__( 'There are no upcoming events.', 'tender-library' ) . '</p>';
    } else {
        echo '<ul class="tender-events-list">';
        foreach ( $occurrences as $occ ) {
            /** @var WP_Post $event_post */
            $event_post = $occ['post'];
            $ts         = $occ['ts'];
            $post_id    = $event_post->ID;
            $thumb_html = get_the_post_thumbnail(
                $post_id,
                'medium',
                array(
                    'class'   => 'tender-events-list__thumb-img',
                    'loading' => 'lazy',
                    'alt'     => the_title_attribute( array( 'echo' => false, 'post' => $post_id ) ),
                )
            );

            $is_recurrent = tender_upcoming_events_is_recurrent( $post_id );

            echo '<li class="tender-events-list__item">';
                echo '<div class="tender-events-list__thumb">';
                    if ( $thumb_html ) {
                        echo '<a href="' . esc_url( get_permalink( $post_id ) ) . '" class="tender-events-list__link">';
                            echo $thumb_html; // get_the_post_thumbnail ya devuelve HTML seguro
                        echo '</a>';
                    } else {
                        echo '<span class="tender-events-list__thumb-placeholder" aria-hidden="true"></span>';
                    }
                echo '</div>';
                echo '<div class="tender-events-list__content">';

                    echo '<span class="tender-events-list__time">';
                        echo '<strong>' . esc_html( wp_date( 'd M.', $ts ) ) . '</strong> - ' . esc_html( wp_date( 'H:i', $ts ) );
                    echo '</span>';
                    echo '<a href="' . esc_url( get_permalink( $post_id ) ) . '" class="tender-events-list__link">';
                        echo esc_html( get_the_title( $post_id ) );
                    echo '</a>';

                    if ( $is_recurrent ) {
                        echo '<span class="tender-events-list__badge">' . esc_html__( 'Recurring event', 'tender-library' ) . '</span>';
                    }
                echo '</div>';

            echo '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';

    return ob_get_clean();
}

function tender_render_events_calendar() {
    $now   = current_datetime();
    $year  = isset( $_GET['te_year'] ) ? absint( $_GET['te_year'] ) : (int) $now->format( 'Y' );
    $month = isset( $_GET['te_month'] ) ? absint( $_GET['te_month'] ) : (int) $now->format( 'n' );
    $month = min( 12, max( 1, $month ) );

    $first_of_month = $now->setDate( $year, $month, 1 )->setTime( 0, 0, 0 );
    $last_of_month  = $first_of_month->modify( 'last day of this month' )->setTime( 23, 59, 59 );
    $days_in_month  = (int) $last_of_month->format( 'j' );
    $start_week_day = (int) $first_of_month->format( 'N' ); // 1–7 (Mon–Sun)

    $month_start_ts = $first_of_month->getTimestamp();
    $month_end_ts   = $last_of_month->getTimestamp();

    $occurrences = tender_get_event_occurrences_in_range( $month_start_ts, $month_end_ts );

    // Group by day
    $events_by_day = array();
    foreach ( $occurrences as $occ ) {
        $day = (int) wp_date( 'j', $occ['ts'] );
        if ( ! isset( $events_by_day[ $day ] ) ) {
            $events_by_day[ $day ] = array();
        }
        $events_by_day[ $day ][] = $occ;
    }

    // Previous / next month
    $prev_month = $month - 1;
    $prev_year  = $year;
    if ( $prev_month < 1 ) {
        $prev_month = 12;
        $prev_year--;
    }

    $next_month = $month + 1;
    $next_year  = $year;
    if ( $next_month > 12 ) {
        $next_month = 1;
        $next_year++;
    }

    $base_url = rtrim( get_permalink(), '/' );

    ob_start();
    ?>

    <div class="wp-block-tender-events wp-block-tender-events--calendar tender-calendar">

        <div class="tender-calendar__header">

            <h2 class="tender-calendar__title">
                <?php echo esc_html( wp_date( 'F Y', $month_start_ts ) ); ?>
            </h2>
            <?php if ( function_exists( 'tal_render_calendar_feed_links' ) ) : ?>
                <?php echo tal_render_calendar_feed_links(); ?>
            <?php endif; ?>
            <div class="tender-calendar__navs">
                <a class="tender-calendar__nav tender-calendar__nav--prev"
                href="<?php echo esc_url( add_query_arg( array( 'te_year' => $prev_year, 'te_month' => $prev_month ), $base_url ) ); ?>">
                    &laquo; <?php esc_html_e( 'Previous month', 'tender-library' ); ?>
                </a>
                <a class="tender-calendar__nav tender-calendar__nav--next"
                href="<?php echo esc_url( add_query_arg( array( 'te_year' => $next_year, 'te_month' => $next_month ), $base_url ) ); ?>">
                    <?php esc_html_e( 'Next month', 'tender-library' ); ?> &raquo;
                </a>
            </div>
        </div>

        <div class="tender-calendar__weekdays">
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Mon', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Tue', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Wed', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Thu', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Fri', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Sat', 'tender-library' ); ?></div>
            <div class="tender-calendar__weekday"><?php esc_html_e( 'Sun', 'tender-library' ); ?></div>
        </div>

        <div class="tender-calendar__grid">
            <?php
            for ( $i = 1; $i < $start_week_day; $i++ ) {
                echo '<div class="tender-calendar__day tender-calendar__day--empty"></div>';
            }

            for ( $day = 1; $day <= $days_in_month; $day++ ) : ?>
                <div class="tender-calendar__day <?php if ( ! empty( $events_by_day[ $day ] ) ) echo ' tender-calendar__day--has-events'; ?>" data-day="<?php echo esc_attr( $day ); ?>">
                    <div class="tender-calendar__day-number">
                        <?php echo esc_html( $day ); ?>
                    </div>

                    <?php if ( ! empty( $events_by_day[ $day ] ) ) : ?>
                        <ul class="tender-calendar__events">
                            <?php foreach ( $events_by_day[ $day ] as $occ ) :
                                /** @var WP_Post $event_post */
                                $event_post = $occ['post'];
                                $ts         = $occ['ts'];
                                $post_id    = $event_post->ID;
                                $is_recurrent = tender_upcoming_events_is_recurrent( $post_id );
                                ?>
                                <li class="tender-calendar__event">
                                    <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                                       class="tender-calendar__event-link"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>

                                    <span class="tender-calendar__event-time">
                                        &nbsp;(<?php echo esc_html( wp_date( 'H:i', $ts ) ); ?>)
                                    </span>

                                    <?php if ( $is_recurrent ) : ?>
                                        <span class="tender-calendar__event-badge">
                                            <?php echo esc_html__( 'R', 'tender-library' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

/**
 * Returns event occurrences between two timestamps.
 *
 * @param int $range_start_ts Start timestamp (inclusive).
 * @param int $range_end_ts   End timestamp (inclusive).
 * @param int $limit          Occurrences limit (0 = no limit).
 *
 * @return array Each item: [ 'post' => WP_Post, 'ts' => int ]
 */
function tender_get_event_occurrences_in_range( $range_start_ts, $range_end_ts, $limit = 0 ) {
    $occurrences = array();
    $timezone    = wp_timezone();
    $range_start = ( new DateTimeImmutable( '@' . (int) $range_start_ts ) )->setTimezone( $timezone );
    $range_end   = ( new DateTimeImmutable( '@' . (int) $range_end_ts ) )->setTimezone( $timezone );

    // Query: todos los eventos ordenados por fecha de inicio
    $events_q = new WP_Query( array(
        'post_type'      => 'tender_event',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'suppress_filters' => true,
    ) );

    if ( $events_q->have_posts() ) {
        while ( $events_q->have_posts() ) {
            $events_q->the_post();
            $post_id   = get_the_ID();
            $event_obj = get_post();

            // Fecha/hora de inicio (date_time)
            $raw_start = carbon_get_post_meta( $post_id, 'tender_event_startdate' ); // ← CAMBIO

            if ( ! $raw_start ) {
                continue;
            }

            $start = tender_upcoming_events_parse_datetime( $raw_start, $timezone );
            if ( ! $start ) {
                continue;
            }

            
            // Recurrente semanal o no
            $is_recurrent = tender_upcoming_events_is_recurrent( $post_id );

            // Fecha fin de evento recurrente (date, Y-m-d)
            $raw_end = carbon_get_post_meta( $post_id, 'tender_event_enddate' ); // ← CAMBIO

            if ( ! $is_recurrent ) {
                // Evento “simple”: solo cuenta si cae dentro del rango
                if ( $start >= $range_start && $start <= $range_end ) {
                    $occurrences[] = array(
                        'post' => $event_obj,
                        'ts'   => $start->getTimestamp(),
                    );
                }
                continue;
            }

            // Evento recurrente semanal
            // Si no hay fecha de fin, limitamos al final del rango para no generar infinitos
            if ( $raw_end ) {
                // tender_event_enddate se guarda como Y-m-d → añadimos fin de día
                $until = tender_upcoming_events_parse_datetime( $raw_end . ' 23:59:59', $timezone );
                if ( ! $until ) {
                    continue;
                }
            } else {
                $until = $range_end;
            }

            // Si termina antes de que empiece el rango, nada que hacer
            if ( $until < $range_start ) {
                continue;
            }

            $occurrence = $start;

            // Avanzamos hasta llegar al inicio del rango
            while ( $occurrence < $range_start && $occurrence <= $until ) {
                // DateTime keeps the event's local wall-clock time over DST changes.
                $occurrence = $occurrence->modify( '+1 week' );
            }

            // Generamos ocurrencias dentro del rango
            while ( $occurrence >= $range_start && $occurrence <= $range_end && $occurrence <= $until ) {
                $occurrences[] = array(
                    'post' => $event_obj,
                    'ts'   => $occurrence->getTimestamp(),
                );
                $occurrence = $occurrence->modify( '+1 week' );
            }
        }
        wp_reset_postdata();
    }

    // Ordenamos por fecha por si acaso
    usort( $occurrences, function ( $a, $b ) {
        return $a['ts'] <=> $b['ts'];
    } );

    if ( $limit && count( $occurrences ) > $limit ) {
        $occurrences = array_slice( $occurrences, 0, $limit );
    }

    return $occurrences;
}

/**
 * Parses stored Carbon Fields event values in the site's configured timezone.
 */
function tender_upcoming_events_parse_datetime( $value, DateTimeZone $timezone ) {
    foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d' ) as $format ) {
        $date = DateTimeImmutable::createFromFormat( '!' . $format, (string) $value, $timezone );
        $errors = DateTimeImmutable::getLastErrors();
        if ( $date instanceof DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
            return $date;
        }
    }

    return false;
}

function tender_upcoming_events_is_recurrent( $post_id ) {
    $value = carbon_get_post_meta( $post_id, 'tender_event_recurrent' );

    return in_array( $value, array( 'yes', '1', 1, true ), true );
}
