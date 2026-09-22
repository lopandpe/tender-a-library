<?php

if (!defined('ABSPATH')) {
	exit;
}

function tal_settings_user_can_access()
{
	if (is_multisite() && is_super_admin()) {
		return true;
	}

	$user = wp_get_current_user();
	return current_user_can('manage_options') && in_array('administrator', (array) $user->roles, true);
}

function tal_settings_page_url($tab = 'migration', $args = [])
{
	return add_query_arg(
		array_merge(['page' => 'tal-settings', 'tab' => $tab], $args),
		admin_url('admin.php')
	);
}

function tal_settings_register_menu()
{
	add_submenu_page(
		'tender-library',
		__('Settings', 'tender-library'),
		__('Settings', 'tender-library'),
		'manage_options',
		'tal-settings',
		'tal_settings_render_page'
	);
}
add_action('admin_menu', 'tal_settings_register_menu');

function tal_settings_render_page()
{
	if (!tal_settings_user_can_access()) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	$tabs = [
		'migration' => __('CSV Migration', 'tender-library'),
		'email-queue' => __('Email Queue', 'tender-library'),
		'password-setup' => __('Password Setup Emails', 'tender-library'),
	];
	$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'migration';
	if (!isset($tabs[$tab])) {
		$tab = 'migration';
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__('Tender Library Settings', 'tender-library') . '</h1>';
	echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__('Settings sections', 'tender-library') . '">';
	foreach ($tabs as $tab_id => $label) {
		$class = $tab === $tab_id ? ' nav-tab-active' : '';
		echo '<a class="nav-tab' . esc_attr($class) . '" href="' . esc_url(tal_settings_page_url($tab_id)) . '">' . esc_html($label) . '</a>';
	}
	echo '</nav>';

	switch ($tab) {
		case 'email-queue':
			tal_email_queue_render_content();
			break;
		case 'password-setup':
			tal_password_setup_render_content();
			break;
		default:
			tal_migration_render_content();
			break;
	}

	echo '</div>';
}

function tal_settings_redirect_legacy_pages()
{
	if (!is_admin() || !tal_settings_user_can_access() || empty($_GET['page'])) {
		return;
	}

	$legacy_tabs = [
		'tal-csv-migration' => 'migration',
		'tal-email-queue' => 'email-queue',
		'tal-password-setup-emails' => 'password-setup',
	];
	$page = sanitize_key(wp_unslash($_GET['page']));
	if (!isset($legacy_tabs[$page])) {
		return;
	}

	$args = [];
	if ($page === 'tal-csv-migration' && isset($_GET['job_id'])) {
		$args['job_id'] = absint($_GET['job_id']);
	}

	wp_safe_redirect(tal_settings_page_url($legacy_tabs[$page], $args));
	exit;
}
add_action('admin_init', 'tal_settings_redirect_legacy_pages');
