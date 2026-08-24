<?php

if (!defined('ABSPATH')) {
	exit;
}

define('TAL_PASSWORD_SETUP_SENT_META_KEY', 'tal_password_setup_email_sent_at');
define('TAL_PASSWORD_SETUP_LAST_ERROR_META_KEY', 'tal_password_setup_email_last_error');
define('TAL_PASSWORD_SETUP_SUBJECT_OPTION', 'tal_password_setup_email_subject');
define('TAL_PASSWORD_SETUP_BODY_OPTION', 'tal_password_setup_email_body');
define('TAL_PASSWORD_SETUP_BATCH_SIZE', 50);

function tal_password_setup_default_subject()
{
	return __('Set your password for the new library website', 'tender-library');
}

function tal_password_setup_default_body()
{
	return __(
		"Hello {display_name},\n\nWe have moved the library to a new website and migrated your existing library account.\n\nFor security reasons, your old password was not migrated. You can create a new password using this secure link:\n\n{reset_password_url}\n\nIf you did not request this or you think this message is a mistake, please contact the library.\n\nThank you,\n{site_name}",
		'tender-library'
	);
}

function tal_password_setup_register_menu()
{
	add_submenu_page(
		'tender-library',
		__('Password Setup Emails', 'tender-library'),
		__('Password Setup Emails', 'tender-library'),
		'manage_options',
		'tal-password-setup-emails',
		'tal_password_setup_render_page'
	);
}
add_action('admin_menu', 'tal_password_setup_register_menu');

function tal_password_setup_get_subject_template()
{
	return get_option(TAL_PASSWORD_SETUP_SUBJECT_OPTION, tal_password_setup_default_subject());
}

function tal_password_setup_get_body_template()
{
	return get_option(TAL_PASSWORD_SETUP_BODY_OPTION, tal_password_setup_default_body());
}

function tal_password_setup_get_imported_user_ids($only_unsent = true)
{
	$args = [
		'fields' => 'ID',
		'orderby' => 'ID',
		'order' => 'ASC',
		'meta_query' => [
			[
				'key' => defined('TAL_MIGRATION_META_KEY') ? TAL_MIGRATION_META_KEY : 'tender_old_id',
				'compare' => 'EXISTS',
			],
		],
	];


	if ($only_unsent) {
		$args['meta_query'][] = [
			'key' => TAL_PASSWORD_SETUP_SENT_META_KEY,
			'compare' => 'NOT EXISTS',
		];
	}

	$user_query = new WP_User_Query($args);
	return array_map('intval', $user_query->get_results());
}

function tal_password_setup_user_can_receive_email($user)
{
	return $user instanceof WP_User
		&& !empty($user->user_email)
		&& is_email($user->user_email)
		&& !str_ends_with($user->user_email, '@example.local');
}

function tal_password_setup_filter_emailable_user_ids($user_ids)
{
	$emailable_ids = [];

	foreach ($user_ids as $user_id) {
		$user = get_userdata($user_id);
		if (tal_password_setup_user_can_receive_email($user)) {
			$emailable_ids[] = (int) $user_id;
		}
	}

	return $emailable_ids;
}

function tal_password_setup_get_emailable_imported_user_ids($only_unsent = true, $limit = 0)
{
	$user_ids = tal_password_setup_filter_emailable_user_ids(tal_password_setup_get_imported_user_ids($only_unsent));

	if ($limit > 0) {
		return array_slice($user_ids, 0, (int) $limit);
	}

	return $user_ids;
}

function tal_password_setup_count_imported_users($only_unsent = true)
{
	return count(tal_password_setup_get_imported_user_ids($only_unsent));
}

function tal_password_setup_count_emailable_imported_users($only_unsent = true)
{
	return count(tal_password_setup_get_emailable_imported_user_ids($only_unsent));
}

function tal_password_setup_get_reset_url($user)
{
	$key = get_password_reset_key($user);
	if (is_wp_error($key)) {
		return $key;
	}

	return network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login),
		'login'
	);
}

function tal_password_setup_replace_placeholders($template, $user, $reset_url, $escape_html = false)
{
	$values = [
		'{display_name}' => $user->display_name ?: $user->user_login,
		'{user_login}' => $user->user_login,
		'{user_email}' => $user->user_email,
		'{site_name}' => get_bloginfo('name'),
		'{reset_password_url}' => $reset_url,
	];

	if ($escape_html) {
		$values['{display_name}'] = esc_html($values['{display_name}']);
		$values['{user_login}'] = esc_html($values['{user_login}']);
		$values['{user_email}'] = esc_html($values['{user_email}']);
		$values['{site_name}'] = esc_html($values['{site_name}']);
		$values['{reset_password_url}'] = esc_url($values['{reset_password_url}']);
	}

	return strtr($template, $values);
}

function tal_password_setup_send_email_to_user($user, $subject_template, $body_template)
{
	if (!$user instanceof WP_User || empty($user->user_email) || !is_email($user->user_email)) {
		return new WP_Error('invalid_user_email', __('The user does not have a valid email address.', 'tender-library'));
	}

	if (str_ends_with($user->user_email, '@example.local')) {
		return new WP_Error('placeholder_email', __('The user has a placeholder migration email address.', 'tender-library'));
	}

	$reset_url = tal_password_setup_get_reset_url($user);
	if (is_wp_error($reset_url)) {
		return $reset_url;
	}

	$subject = tal_password_setup_replace_placeholders($subject_template, $user, $reset_url, false);
	$body = tal_password_setup_replace_placeholders($body_template, $user, $reset_url, true);
	$message = wpautop($body);
	$headers = ['Content-Type: text/html; charset=UTF-8'];

	$sent = wp_mail($user->user_email, $subject, $message, $headers);
	if (!$sent) {
		return new WP_Error('mail_failed', __('WordPress could not send the email.', 'tender-library'));
	}

	update_user_meta($user->ID, TAL_PASSWORD_SETUP_SENT_META_KEY, current_time('mysql'));
	delete_user_meta($user->ID, TAL_PASSWORD_SETUP_LAST_ERROR_META_KEY);

	return true;
}

function tal_password_setup_handle_save()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	check_admin_referer('tal_password_setup_save');

	$subject = isset($_POST['tal_password_setup_subject'])
		? sanitize_text_field(wp_unslash($_POST['tal_password_setup_subject']))
		: tal_password_setup_default_subject();
	$body = isset($_POST['tal_password_setup_body'])
		? wp_kses_post(wp_unslash($_POST['tal_password_setup_body']))
		: tal_password_setup_default_body();

	update_option(TAL_PASSWORD_SETUP_SUBJECT_OPTION, $subject, false);
	update_option(TAL_PASSWORD_SETUP_BODY_OPTION, $body, false);

	wp_safe_redirect(add_query_arg('tal_password_setup_saved', '1', admin_url('admin.php?page=tal-password-setup-emails')));
	exit;
}
add_action('admin_post_tal_password_setup_save', 'tal_password_setup_handle_save');

function tal_password_setup_handle_send()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	check_admin_referer('tal_password_setup_send');

	$limit = isset($_POST['tal_password_setup_limit']) ? (int) $_POST['tal_password_setup_limit'] : TAL_PASSWORD_SETUP_BATCH_SIZE;
	$limit = max(1, min(200, $limit));
	$subject_template = tal_password_setup_get_subject_template();
	$body_template = tal_password_setup_get_body_template();
	$user_ids = tal_password_setup_get_emailable_imported_user_ids(true, $limit);
	$sent = 0;
	$failed = 0;
	$errors = [];

	foreach ($user_ids as $user_id) {
		$user = get_userdata($user_id);
		$result = tal_password_setup_send_email_to_user($user, $subject_template, $body_template);

		if (is_wp_error($result)) {
			$failed++;
			$message = $result->get_error_message();
			update_user_meta($user_id, TAL_PASSWORD_SETUP_LAST_ERROR_META_KEY, $message);
			$errors[] = sprintf('%s: %s', $user ? $user->user_email : '#' . $user_id, $message);
			continue;
		}

		$sent++;
	}

	set_transient('tal_password_setup_last_result', [
		'sent' => $sent,
		'failed' => $failed,
		'errors' => $errors,
		'timestamp' => current_time('mysql'),
	], 10 * MINUTE_IN_SECONDS);

	wp_safe_redirect(admin_url('admin.php?page=tal-password-setup-emails'));
	exit;
}
add_action('admin_post_tal_password_setup_send', 'tal_password_setup_handle_send');

function tal_password_setup_render_page()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	$subject = tal_password_setup_get_subject_template();
	$body = tal_password_setup_get_body_template();
	$total_imported = tal_password_setup_count_imported_users(false);
	$total_emailable = tal_password_setup_count_emailable_imported_users(false);
	$remaining = tal_password_setup_count_emailable_imported_users(true);
	$sent_count = max(0, $total_emailable - $remaining);
	$skipped_count = max(0, $total_imported - $total_emailable);
	$last_result = get_transient('tal_password_setup_last_result');

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__('Password Setup Emails', 'tender-library') . '</h1>';
	echo '<p>' . esc_html__('Send imported users a secure WordPress password reset link so they can create a password for the new website.', 'tender-library') . '</p>';

	if (!empty($_GET['tal_password_setup_saved'])) {
		echo '<div class="notice notice-success"><p>' . esc_html__('Email template saved.', 'tender-library') . '</p></div>';
	}

	if (is_array($last_result)) {
		echo '<div class="notice notice-info"><p>';
		echo esc_html(sprintf(
			__('Last send run: %1$d sent, %2$d failed at %3$s.', 'tender-library'),
			(int) $last_result['sent'],
			(int) $last_result['failed'],
			(string) $last_result['timestamp']
		));
		echo '</p>';
		if (!empty($last_result['errors'])) {
			echo '<ul style="margin-left:18px;">';
			foreach (array_slice($last_result['errors'], 0, 20) as $error) {
				echo '<li>' . esc_html($error) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">';
	echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 14px;min-width:160px;"><strong>' . esc_html((string) $total_imported) . '</strong><br />' . esc_html__('Imported users', 'tender-library') . '</div>';
	echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 14px;min-width:160px;"><strong>' . esc_html((string) $skipped_count) . '</strong><br />' . esc_html__('Skipped placeholder emails', 'tender-library') . '</div>';
	echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 14px;min-width:160px;"><strong>' . esc_html((string) $sent_count) . '</strong><br />' . esc_html__('Already emailed', 'tender-library') . '</div>';
	echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:12px 14px;min-width:160px;"><strong>' . esc_html((string) $remaining) . '</strong><br />' . esc_html__('Waiting to send', 'tender-library') . '</div>';
	echo '</div>';

	echo '<h2>' . esc_html__('Email Template', 'tender-library') . '</h2>';
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
	wp_nonce_field('tal_password_setup_save');
	echo '<input type="hidden" name="action" value="tal_password_setup_save" />';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th scope="row"><label for="tal_password_setup_subject">' . esc_html__('Subject', 'tender-library') . '</label></th>';
	echo '<td><input class="large-text" id="tal_password_setup_subject" name="tal_password_setup_subject" type="text" value="' . esc_attr($subject) . '" /></td></tr>';
	echo '<tr><th scope="row"><label for="tal_password_setup_body">' . esc_html__('Body', 'tender-library') . '</label></th>';
	echo '<td><textarea class="large-text" rows="12" id="tal_password_setup_body" name="tal_password_setup_body">' . esc_textarea($body) . '</textarea>';
	echo '<p class="description">' . esc_html__('Available placeholders: {display_name}, {user_login}, {user_email}, {site_name}, {reset_password_url}', 'tender-library') . '</p></td></tr>';
	echo '</tbody></table>';
	submit_button(__('Save Template', 'tender-library'));
	echo '</form>';

	echo '<h2>' . esc_html__('Send Emails', 'tender-library') . '</h2>';
	echo '<p>' . esc_html__('Emails are sent only to imported users that do not already have the sent flag.', 'tender-library') . '</p>';
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
	wp_nonce_field('tal_password_setup_send');
	echo '<input type="hidden" name="action" value="tal_password_setup_send" />';
	echo '<p><label for="tal_password_setup_limit">' . esc_html__('Maximum emails in this batch', 'tender-library') . '</label> ';
	echo '<input id="tal_password_setup_limit" name="tal_password_setup_limit" type="number" min="1" max="200" value="' . esc_attr((string) TAL_PASSWORD_SETUP_BATCH_SIZE) . '" /></p>';
	submit_button(
		$remaining > 0 ? __('Send Next Batch', 'tender-library') : __('No Emails Left To Send', 'tender-library'),
		'primary',
		'submit',
		true,
		$remaining > 0 ? [] : ['disabled' => 'disabled']
	);
	echo '</form>';

	echo '</div>';
}
