<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_current_page_is_plugin_page()
{
	$page_options = array(
		'tal_profile_page',
		'tal_edit_profile_page',
		'tal_library_search_page',
		'tal_users_list_page',
	);

	foreach ($page_options as $option_name) {
		$page_id = absint(get_option($option_name));
		if ($page_id && is_page($page_id)) {
			return true;
		}
	}

	return false;
}

function tender_current_content_has_tender_block($block_names = array())
{
	$post = get_post();
	if (!$post instanceof WP_Post) {
		return false;
	}

	if (empty($block_names)) {
		$block_names = array(
			'tender-a-library/book-summary',
			'tender-a-library/book-cover',
			'tender-a-library/book-data',
			'tender-a-library/mini-book',
			'tender-a-library/book-search',
			'tender-a-library/upcoming-events',
			'tender-a-library/profile-links',
			'tender-a-library/event-date',
			'tender-a-library/latest-books',
		);
	}

	foreach ($block_names as $block_name) {
		if (has_block($block_name, $post)) {
			return true;
		}
	}

	return false;
}

function tender_current_content_has_tender_shortcode()
{
	$post = get_post();
	if (!$post instanceof WP_Post) {
		return false;
	}

	return has_shortcode($post->post_content, 'tender_user_reservations');
}

function tender_should_load_public_assets()
{
	if (is_admin()) {
		return true;
	}

	if (tender_current_page_is_plugin_page()) {
		return true;
	}

	if (is_singular(array('tender_book', 'tender_event'))) {
		return true;
	}

	if (tender_current_content_has_tender_block() || tender_current_content_has_tender_shortcode()) {
		return true;
	}

	return false;
}

function tender_should_load_search_assets()
{
	$search_page_id = absint(get_option('tal_library_search_page'));
	if ($search_page_id && is_page($search_page_id)) {
		return true;
	}

	return tender_current_content_has_tender_block(array('tender-a-library/book-search'));
}

function tender_load_styles()
{
	if (!tender_should_load_public_assets()) {
		return;
	}

	$styles = [
		'public' => [
			'tender-public-style' => 'dist/css/tender-styles.css',
		],
	];

	foreach ($styles as $context => $style_set) {
		foreach ($style_set as $handle => $path) {
			wp_enqueue_style(
				$handle,
				plugin_dir_url(__FILE__) . '../' . $path,
				[],
				defined('TENDER_LIBRARY_VERSION') ? TENDER_LIBRARY_VERSION : '1.0.0',
				'all'
			);
		}
	}
}

function tender_load_admin_styles()
{
	$styles = [
		'admin' => [
			'tender-admin-style' => 'dist/css/tender-styles.css',
		],
	];

	foreach ($styles as $context => $style_set) {
		foreach ($style_set as $handle => $path) {
			wp_enqueue_style(
				$handle,
				plugin_dir_url(__FILE__) . '../' . $path,
				[],
				defined('TENDER_LIBRARY_VERSION') ? TENDER_LIBRARY_VERSION : '1.0.0',
				'all'
			);
		}
	}
}

add_action('admin_enqueue_scripts', 'tender_load_admin_styles', 1);

add_action('wp_enqueue_scripts', 'tender_load_styles', 1);

function tender_load_scripts()
{
	if (!tender_should_load_public_assets()) {
		return;
	}

	wp_register_script(
		'tender-script',
		plugin_dir_url(__FILE__) . '../dist/js/tender-scripts.js',
		['jquery'],
		defined('TENDER_LIBRARY_VERSION') ? TENDER_LIBRARY_VERSION : '1.0.0',
		true
	);
	wp_enqueue_script('tender-script');

	wp_localize_script(
		'tender-script',
		'tender',
		array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'lending_action_nonce' => wp_create_nonce('tal_lending_action'),
		)
	);
}

add_action('wp_enqueue_scripts', 'tender_load_scripts', 1);
