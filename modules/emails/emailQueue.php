<?php

if (!defined('ABSPATH')) {
	exit;
}

define('TAL_EMAIL_RATE_LIMIT_OPTION', 'tal_email_rate_limit');
define('TAL_EMAIL_QUEUE_CRON_HOOK', 'tal_process_email_queue');
define('TAL_EMAIL_QUEUE_MAX_ATTEMPTS', 4);
define('TAL_EMAIL_QUEUE_MAX_RATE', 50);
define('TAL_EMAIL_QUEUE_DEFAULT_RATE', 40);

function tal_email_queue_utc_now()
{
	return current_time('mysql', true);
}

function tal_email_queue_get_rate_limit()
{
	$rate = (int) get_option(TAL_EMAIL_RATE_LIMIT_OPTION, TAL_EMAIL_QUEUE_DEFAULT_RATE);
	return max(1, min(TAL_EMAIL_QUEUE_MAX_RATE, $rate));
}

function tal_email_queue_register_schedule($schedules)
{
	$schedules['tal_every_minute'] = [
		'interval' => MINUTE_IN_SECONDS,
		'display' => __('Every minute', 'tender-library'),
	];

	return $schedules;
}
add_filter('cron_schedules', 'tal_email_queue_register_schedule');

function tal_email_queue_schedule_worker()
{
	if (!wp_next_scheduled(TAL_EMAIL_QUEUE_CRON_HOOK)) {
		wp_schedule_event(time() + MINUTE_IN_SECONDS, 'tal_every_minute', TAL_EMAIL_QUEUE_CRON_HOOK);
	}
}
add_action('init', 'tal_email_queue_schedule_worker');
add_action(TAL_EMAIL_QUEUE_CRON_HOOK, 'tal_email_queue_process');

function tal_email_queue_enqueue($args)
{
	global $wpdb;

	$defaults = [
		'type' => 'generic',
		'recipient' => '',
		'subject' => '',
		'message' => '',
		'headers' => ['Content-Type: text/html; charset=UTF-8'],
		'payload' => [],
		'deduplication_key' => '',
		'priority' => 10,
	];
	$args = wp_parse_args($args, $defaults);

	if (!is_email($args['recipient']) || empty($args['deduplication_key'])) {
		return new WP_Error('invalid_email_queue_item', __('The email queue item is invalid.', 'tender-library'));
	}

	$now = tal_email_queue_utc_now();
	$inserted = $wpdb->insert(
		TENDER_TABLE_EMAIL_QUEUE,
		[
			'type' => sanitize_key($args['type']),
			'recipient' => sanitize_email($args['recipient']),
			'subject' => $args['subject'],
			'message' => $args['message'],
			'headers' => wp_json_encode($args['headers']),
			'payload' => wp_json_encode($args['payload']),
			'deduplication_key' => sanitize_key($args['deduplication_key']),
			'priority' => max(1, min(100, (int) $args['priority'])),
			'status' => 'pending',
			'attempts' => 0,
			'available_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
		],
		['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s']
	);

	if ($inserted) {
		return (int) $wpdb->insert_id;
	}

	if (false !== strpos((string) $wpdb->last_error, 'Duplicate')) {
		return 0;
	}

	return new WP_Error('email_queue_insert_failed', __('Could not add the email to the queue.', 'tender-library'));
}

function tal_email_queue_has_active_key($deduplication_key)
{
	global $wpdb;

	return (bool) $wpdb->get_var($wpdb->prepare(
		"SELECT id FROM " . TENDER_TABLE_EMAIL_QUEUE . " WHERE deduplication_key = %s AND status IN ('pending', 'processing') LIMIT 1",
		$deduplication_key
	));
}

function tal_email_queue_acquire_lock()
{
	$lock_option = 'tal_email_queue_worker_lock';
	$now = time();
	$existing = (int) get_option($lock_option, 0);

	if ($existing && $existing > $now - (2 * MINUTE_IN_SECONDS)) {
		return false;
	}

	if ($existing) {
		delete_option($lock_option);
	}

	return add_option($lock_option, $now, '', false);
}

function tal_email_queue_release_lock()
{
	delete_option('tal_email_queue_worker_lock');
}

function tal_email_queue_process()
{
	global $wpdb;

	if (!tal_email_queue_acquire_lock()) {
		return;
	}

	try {
		$now = tal_email_queue_utc_now();
		$stale_before = gmdate('Y-m-d H:i:s', time() - (10 * MINUTE_IN_SECONDS));
		$wpdb->query($wpdb->prepare(
			"UPDATE " . TENDER_TABLE_EMAIL_QUEUE . " SET status = 'pending', started_at = NULL, updated_at = %s WHERE status = 'processing' AND started_at < %s",
			$now,
			$stale_before
		));

		$minute_ago = gmdate('Y-m-d H:i:s', time() - MINUTE_IN_SECONDS);
		$sent_last_minute = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM " . TENDER_TABLE_EMAIL_QUEUE . " WHERE status = 'sent' AND sent_at >= %s",
			$minute_ago
		));
		$remaining_capacity = tal_email_queue_get_rate_limit() - $sent_last_minute;
		if ($remaining_capacity < 1) {
			return;
		}

		$items = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM " . TENDER_TABLE_EMAIL_QUEUE . " WHERE status = 'pending' AND available_at <= %s ORDER BY priority DESC, id ASC LIMIT %d",
			$now,
			$remaining_capacity
		));

		foreach ($items as $item) {
			$claimed = $wpdb->update(
				TENDER_TABLE_EMAIL_QUEUE,
				['status' => 'processing', 'started_at' => $now, 'updated_at' => $now],
				['id' => $item->id, 'status' => 'pending'],
				['%s', '%s', '%s'],
				['%d', '%s']
			);
			if (!$claimed) {
				continue;
			}

			tal_email_queue_dispatch($item);
		}
	} finally {
		tal_email_queue_release_lock();
	}
}

function tal_email_queue_dispatch($item)
{
	global $wpdb;

	$prepared = apply_filters('tal_email_queue_prepare_message', null, $item);
	if (is_wp_error($prepared)) {
		tal_email_queue_mark_failed($item, $prepared->get_error_message());
		return;
	}

	if (!is_array($prepared)) {
		$prepared = [
			'recipient' => $item->recipient,
			'subject' => $item->subject,
			'message' => $item->message,
			'headers' => json_decode($item->headers, true) ?: [],
		];
	}

	$sent = wp_mail($prepared['recipient'], $prepared['subject'], $prepared['message'], $prepared['headers']);
	$now = tal_email_queue_utc_now();

	if (!$sent) {
		tal_email_queue_mark_failed($item, __('WordPress could not send the email.', 'tender-library'));
		return;
	}

	$wpdb->update(
		TENDER_TABLE_EMAIL_QUEUE,
		['status' => 'sent', 'sent_at' => $now, 'updated_at' => $now, 'last_error' => null],
		['id' => $item->id],
		['%s', '%s', '%s', '%s'],
		['%d']
	);
	do_action('tal_email_queue_sent', $item);
}

function tal_email_queue_mark_failed($item, $error)
{
	global $wpdb;

	$attempts = (int) $item->attempts + 1;
	$now = tal_email_queue_utc_now();
	$status = $attempts >= TAL_EMAIL_QUEUE_MAX_ATTEMPTS ? 'failed' : 'pending';
	$delay = (int) pow(6, max(0, $attempts - 1)) * MINUTE_IN_SECONDS;
	$available_at = gmdate('Y-m-d H:i:s', time() + $delay);

	$wpdb->update(
		TENDER_TABLE_EMAIL_QUEUE,
		[
			'status' => $status,
			'attempts' => $attempts,
			'available_at' => $available_at,
			'started_at' => null,
			'last_error' => sanitize_text_field($error),
			'updated_at' => $now,
		],
		['id' => $item->id],
		['%s', '%d', '%s', '%s', '%s', '%s'],
		['%d']
	);
}

function tal_email_queue_register_menu()
{
	add_submenu_page(
		'tender-library',
		__('Email Queue', 'tender-library'),
		__('Email Queue', 'tender-library'),
		'manage_options',
		'tal-email-queue',
		'tal_email_queue_render_page'
	);
}
add_action('admin_menu', 'tal_email_queue_register_menu');

function tal_email_queue_handle_settings()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}
	check_admin_referer('tal_email_queue_settings');

	$rate = isset($_POST['tal_email_rate_limit']) ? absint($_POST['tal_email_rate_limit']) : TAL_EMAIL_QUEUE_DEFAULT_RATE;
	update_option(TAL_EMAIL_RATE_LIMIT_OPTION, max(1, min(TAL_EMAIL_QUEUE_MAX_RATE, $rate)), false);
	wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=tal-email-queue')));
	exit;
}
add_action('admin_post_tal_email_queue_settings', 'tal_email_queue_handle_settings');

function tal_email_queue_handle_retry_failed()
{
	global $wpdb;

	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}
	check_admin_referer('tal_email_queue_retry_failed');

	$now = tal_email_queue_utc_now();
	$wpdb->update(
		TENDER_TABLE_EMAIL_QUEUE,
		['status' => 'pending', 'attempts' => 0, 'available_at' => $now, 'updated_at' => $now],
		['status' => 'failed'],
		['%s', '%d', '%s', '%s'],
		['%s']
	);
	wp_safe_redirect(add_query_arg('retried', '1', admin_url('admin.php?page=tal-email-queue')));
	exit;
}
add_action('admin_post_tal_email_queue_retry_failed', 'tal_email_queue_handle_retry_failed');

function tal_email_queue_render_page()
{
	global $wpdb;

	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	$counts = ['pending' => 0, 'processing' => 0, 'failed' => 0, 'sent' => 0];
	$rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM " . TENDER_TABLE_EMAIL_QUEUE . " GROUP BY status");
	foreach ($rows as $row) {
		$counts[$row->status] = (int) $row->total;
	}
	$failed = $wpdb->get_results("SELECT recipient, type, attempts, last_error FROM " . TENDER_TABLE_EMAIL_QUEUE . " WHERE status = 'failed' ORDER BY updated_at DESC LIMIT 20");

	echo '<div class="wrap"><h1>' . esc_html__('Email Queue', 'tender-library') . '</h1>';
	if (isset($_GET['updated'])) {
		echo '<div class="notice notice-success"><p>' . esc_html__('Email rate limit saved.', 'tender-library') . '</p></div>';
	}
	if (isset($_GET['retried'])) {
		echo '<div class="notice notice-success"><p>' . esc_html__('Failed emails were queued again.', 'tender-library') . '</p></div>';
	}
	echo '<p>' . esc_html__('All Tender Library transactional emails are dispatched by this queue.', 'tender-library') . '</p>';
	echo '<p><strong>' . esc_html(sprintf(__('Pending: %d | Processing: %d | Failed: %d | Sent: %d', 'tender-library'), $counts['pending'], $counts['processing'], $counts['failed'], $counts['sent'])) . '</strong></p>';
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
	wp_nonce_field('tal_email_queue_settings');
	echo '<input type="hidden" name="action" value="tal_email_queue_settings" />';
	echo '<table class="form-table"><tr><th scope="row"><label for="tal_email_rate_limit">' . esc_html__('Maximum emails per minute', 'tender-library') . '</label></th><td><input id="tal_email_rate_limit" name="tal_email_rate_limit" type="number" min="1" max="50" value="' . esc_attr((string) tal_email_queue_get_rate_limit()) . '" /> <p class="description">' . esc_html__('The server allows 50 emails per minute. The default of 40 reserves capacity for other WordPress emails.', 'tender-library') . '</p></td></tr></table>';
	submit_button(__('Save Changes', 'tender-library'));
	echo '</form>';

	if ($counts['failed']) {
		echo '<h2>' . esc_html__('Failed Emails', 'tender-library') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
		wp_nonce_field('tal_email_queue_retry_failed');
		echo '<input type="hidden" name="action" value="tal_email_queue_retry_failed" />';
		submit_button(__('Retry Failed Emails', 'tender-library'), 'secondary');
		echo '</form><table class="widefat striped"><thead><tr><th>' . esc_html__('Recipient', 'tender-library') . '</th><th>' . esc_html__('Type', 'tender-library') . '</th><th>' . esc_html__('Attempts', 'tender-library') . '</th><th>' . esc_html__('Last error', 'tender-library') . '</th></tr></thead><tbody>';
		foreach ($failed as $item) {
			echo '<tr><td>' . esc_html($item->recipient) . '</td><td>' . esc_html($item->type) . '</td><td>' . esc_html((string) $item->attempts) . '</td><td>' . esc_html($item->last_error) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}
