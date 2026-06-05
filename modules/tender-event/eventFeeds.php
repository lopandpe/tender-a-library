<?php

if (!defined('ABSPATH')) {
	exit;
}

add_action('rest_api_init', 'tal_register_event_feed_routes');
function tal_register_event_feed_routes()
{
	register_rest_route('tender-library/v1', '/events\.(?P<format>ics|ical)', array(
		'methods' => 'GET',
		'callback' => 'tal_render_events_calendar_feed',
		'permission_callback' => '__return_true',
	));

	register_rest_route('tender-library/v1', '/events\.rss', array(
		'methods' => 'GET',
		'callback' => 'tal_render_events_rss_feed',
		'permission_callback' => '__return_true',
	));

	register_rest_route('tender-library/v1', '/events/(?P<id>\d+)\.(?P<format>ics|ical)', array(
		'methods' => 'GET',
		'callback' => 'tal_render_single_event_calendar_feed',
		'permission_callback' => '__return_true',
	));
}

function tal_get_events_ics_url($format = 'ics')
{
	$format = $format === 'ical' ? 'ical' : 'ics';
	return rest_url('tender-library/v1/events.' . $format);
}

function tal_get_events_rss_url()
{
	return rest_url('tender-library/v1/events.rss');
}

function tal_get_single_event_ics_url($post_id, $format = 'ics')
{
	$format = $format === 'ical' ? 'ical' : 'ics';
	return rest_url('tender-library/v1/events/' . absint($post_id) . '.' . $format);
}

function tal_get_events_webcal_url()
{
	$ics_url = tal_get_events_ics_url('ics');
	return preg_replace('/^https?:\/\//', 'webcal://', $ics_url);
}

function tal_render_events_calendar_feed($request)
{
	$format = $request->get_param('format') === 'ical' ? 'ical' : 'ics';
	$events = tal_get_events_for_ical_feed();
	$ics = tal_build_ics_calendar($events, __('Library events', 'tender-library'));

	tal_send_raw_feed_response($ics, 'text/calendar; charset=utf-8', 'tender-events.' . $format);
}

function tal_render_single_event_calendar_feed($request)
{
	$post_id = absint($request->get_param('id'));
	$post = get_post($post_id);

	if (!$post || $post->post_type !== 'tender_event' || $post->post_status !== 'publish') {
		return new WP_Error('not_found', __('Event not found.', 'tender-library'), array('status' => 404));
	}

	$event = tal_get_event_ical_data($post);
	if (!$event) {
		return new WP_Error('missing_date', __('Event date is missing.', 'tender-library'), array('status' => 404));
	}

	$format = $request->get_param('format') === 'ical' ? 'ical' : 'ics';
	$ics = tal_build_ics_calendar(array($event), get_the_title($post_id));

	tal_send_raw_feed_response($ics, 'text/calendar; charset=utf-8', sanitize_title(get_the_title($post_id)) . '.' . $format);
}

function tal_render_events_rss_feed()
{
	$now = current_time('timestamp');
	$range_end = strtotime('+1 year', $now);
	$occurrences = function_exists('tender_get_event_occurrences_in_range')
		? tender_get_event_occurrences_in_range($now, $range_end, 50)
		: array();

	$rss = tal_build_events_rss($occurrences);
	tal_send_raw_feed_response($rss, 'application/rss+xml; charset=utf-8', 'tender-events.rss');
}

function tal_send_raw_feed_response($body, $content_type, $filename)
{
	if (!headers_sent()) {
		header('Content-Type: ' . $content_type);
		header('Content-Disposition: inline; filename="' . sanitize_file_name($filename) . '"');
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
	}

	echo $body;
	exit;
}

function tal_get_events_for_ical_feed()
{
	$query = new WP_Query(array(
		'post_type' => 'tender_event',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'meta_value',
		'meta_key' => 'tender_event_startdate',
		'order' => 'ASC',
	));

	$events = array();
	$now = current_time('timestamp');

	foreach ($query->posts as $post) {
		$event = tal_get_event_ical_data($post);
		if (!$event) {
			continue;
		}

		if (!$event['is_recurrent'] && $event['start_ts'] < $now) {
			continue;
		}

		if ($event['is_recurrent'] && $event['until_ts'] && $event['until_ts'] < $now) {
			continue;
		}

		$events[] = $event;
	}

	return $events;
}

function tal_get_event_ical_data($post)
{
	$post = get_post($post);
	if (!$post || $post->post_type !== 'tender_event') {
		return null;
	}

	$start = tal_get_event_datetime($post->ID, 'tender_event_startdate');
	if (!$start) {
		return null;
	}

	$end = $start->modify('+1 hour');
	$is_recurrent = (bool) carbon_get_post_meta($post->ID, 'tender_event_recurrent');
	$until = null;
	$until_ts = 0;

	if ($is_recurrent) {
		$raw_until = carbon_get_post_meta($post->ID, 'tender_event_enddate');
		if ($raw_until) {
			$timezone = wp_timezone();
			$until = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw_until . ' 23:59:59', $timezone);
			if ($until) {
				$until_ts = $until->getTimestamp();
			}
		}
	}

	return array(
		'post' => $post,
		'start' => $start,
		'end' => $end,
		'start_ts' => $start->getTimestamp(),
		'is_recurrent' => $is_recurrent,
		'until' => $until,
		'until_ts' => $until_ts,
	);
}

function tal_get_event_datetime($post_id, $meta_key)
{
	$raw = carbon_get_post_meta($post_id, $meta_key);
	if (!$raw) {
		return null;
	}

	$timezone = wp_timezone();
	$formats = array('Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d');
	foreach ($formats as $format) {
		$date = DateTimeImmutable::createFromFormat($format, (string) $raw, $timezone);
		if ($date instanceof DateTimeImmutable) {
			return $date;
		}
	}

	try {
		return new DateTimeImmutable((string) $raw, $timezone);
	} catch (Exception $e) {
		return null;
	}
}

function tal_build_ics_calendar($events, $calendar_name)
{
	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Local Anarquista Magdalena//Tender A Library//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . tal_ics_escape($calendar_name),
	);

	foreach ($events as $event) {
		$lines = array_merge($lines, tal_build_ics_event_lines($event));
	}

	$lines[] = 'END:VCALENDAR';

	return implode("\r\n", array_map('tal_ics_fold_line', $lines)) . "\r\n";
}

function tal_build_ics_event_lines($event)
{
	$post = $event['post'];
	$post_id = $post->ID;
	$description = wp_strip_all_tags(get_the_excerpt($post_id) ?: $post->post_content);
	$url = get_permalink($post_id);

	$lines = array(
		'BEGIN:VEVENT',
		'UID:tender-event-' . $post_id . '@' . wp_parse_url(home_url(), PHP_URL_HOST),
		'DTSTAMP:' . tal_ics_datetime(new DateTimeImmutable('now', new DateTimeZone('UTC'))),
		'DTSTART:' . tal_ics_datetime($event['start']),
		'DTEND:' . tal_ics_datetime($event['end']),
		'SUMMARY:' . tal_ics_escape(get_the_title($post_id)),
		'DESCRIPTION:' . tal_ics_escape($description),
		'URL:' . esc_url_raw($url),
	);

	if (!empty($event['is_recurrent'])) {
		$rrule = 'FREQ=WEEKLY';
		if (!empty($event['until']) && $event['until'] instanceof DateTimeImmutable) {
			$rrule .= ';UNTIL=' . tal_ics_datetime($event['until']);
		}
		$lines[] = 'RRULE:' . $rrule;
	}

	$lines[] = 'END:VEVENT';

	return $lines;
}

function tal_ics_datetime(DateTimeImmutable $datetime)
{
	return $datetime->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
}

function tal_ics_escape($value)
{
	$value = wp_strip_all_tags((string) $value);
	$value = str_replace('\\', '\\\\', $value);
	$value = str_replace(';', '\\;', $value);
	$value = str_replace(',', '\\,', $value);
	$value = preg_replace("/\r\n|\r|\n/", '\\n', $value);
	return $value;
}

function tal_ics_fold_line($line)
{
	$line = (string) $line;
	if (strlen($line) <= 75) {
		return $line;
	}

	$folded = '';
	while (strlen($line) > 75) {
		$folded .= substr($line, 0, 75) . "\r\n ";
		$line = substr($line, 75);
	}

	return $folded . $line;
}

function tal_build_events_rss($occurrences)
{
	$site_name = get_bloginfo('name');
	$title = sprintf('%s - %s', $site_name, __('Library events', 'tender-library'));
	$description = __('Upcoming library events', 'tender-library');

	$xml = array(
		'<?xml version="1.0" encoding="UTF-8"?>',
		'<rss version="2.0">',
		'<channel>',
		'<title>' . esc_html($title) . '</title>',
		'<link>' . esc_url(home_url('/')) . '</link>',
		'<description>' . esc_html($description) . '</description>',
		'<lastBuildDate>' . esc_html(mysql2date(DATE_RSS, current_time('mysql'), false)) . '</lastBuildDate>',
	);

	foreach ($occurrences as $occurrence) {
		$post = $occurrence['post'];
		$post_id = $post->ID;
		$ts = (int) $occurrence['ts'];
		$item_title = sprintf('%s - %s', date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $ts), get_the_title($post_id));
		$link = add_query_arg('occ_ts', $ts, get_permalink($post_id));
		$description = wp_strip_all_tags(get_the_excerpt($post_id) ?: $post->post_content);
		$description = str_replace(']]>', ']]]]><![CDATA[>', $description);

		$xml[] = '<item>';
		$xml[] = '<title>' . esc_html($item_title) . '</title>';
		$xml[] = '<link>' . esc_url($link) . '</link>';
		$xml[] = '<guid isPermaLink="false">tender-event-' . (int) $post_id . '-' . (int) $ts . '</guid>';
		$xml[] = '<pubDate>' . esc_html(gmdate(DATE_RSS, $ts)) . '</pubDate>';
		$xml[] = '<description><![CDATA[' . $description . ']]></description>';
		$xml[] = '</item>';
	}

	$xml[] = '</channel>';
	$xml[] = '</rss>';

	return implode("\n", $xml) . "\n";
}

function tal_render_event_feed_links($post_id = 0, $include_calendar_links = true)
{
	$post_id = $post_id ? absint($post_id) : get_the_ID();
	if (!$post_id || get_post_type($post_id) !== 'tender_event') {
		return '';
	}

	$links = array(
		array('url' => tal_get_single_event_ics_url($post_id, 'ics'), 'label' => __('ICS', 'tender-library')),
		array('url' => tal_get_single_event_ics_url($post_id, 'ical'), 'label' => __('iCal', 'tender-library')),
	);

	if ($include_calendar_links) {
		$links[] = array('url' => tal_get_events_webcal_url(), 'label' => __('Subscribe', 'tender-library'));
		$links[] = array('url' => tal_get_events_rss_url(), 'label' => __('RSS', 'tender-library'));
	}

	return tal_render_feed_links_markup($links, __('Add to calendar', 'tender-library'), 'tender-event-feed-links');
}

function tal_render_calendar_feed_links()
{
	$links = array(
		array('url' => tal_get_events_ics_url('ics'), 'label' => __('ICS', 'tender-library')),
		array('url' => tal_get_events_ics_url('ical'), 'label' => __('iCal', 'tender-library')),
		array('url' => tal_get_events_webcal_url(), 'label' => __('Subscribe', 'tender-library')),
		array('url' => tal_get_events_rss_url(), 'label' => __('RSS', 'tender-library')),
	);

	return tal_render_feed_links_markup($links, __('Subscribe to calendar', 'tender-library'), 'tender-calendar-feed-links');
}

function tal_render_feed_links_markup($links, $label, $class_name)
{
	ob_start();
	?>
	<nav class="<?php echo esc_attr($class_name); ?> tender-feed-links" aria-label="<?php echo esc_attr($label); ?>">
		<span class="tender-feed-links__label"><?php echo esc_html($label); ?></span>
		<ul class="tender-feed-links__list">
			<?php foreach ($links as $link) : ?>
				<li class="tender-feed-links__item">
					<a class="tender-feed-links__link" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
	return ob_get_clean();
}
