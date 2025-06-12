<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_load_styles()
{
	$styles = [
		'public' => [
			'tender-public-style' => 'dist/css/tender-styles.css', // Nuevo CSS
		],
	];

	foreach ($styles as $context => $style_set) {
		foreach ($style_set as $handle => $path) {
			wp_enqueue_style(
				$handle,
				plugin_dir_url(__FILE__) . '../' . $path,
				[],
				'1.0',
				'all'
			);
		}
	}
}

function tender_load_admin_styles()
{
	$styles = [
		'admin' => [
			'tender-admin-style' => 'dist/css/tender-styles.css', // Nuevo CSS
		],
	];

	foreach ($styles as $context => $style_set) {
		foreach ($style_set as $handle => $path) {
			wp_enqueue_style(
				$handle,
				plugin_dir_url(__FILE__) . '../' . $path,
				[],
				'1.0',
				'all'
			);
		}
	}
}

add_action('admin_enqueue_scripts', 'tender_load_admin_styles', 1);

add_action('wp_enqueue_scripts', 'tender_load_styles', 1);

function tender_load_scripts()
{

	wp_register_script(
		'tender-script',
		plugin_dir_url(__FILE__) . '../dist/js/tender-scripts.js',
		['jquery'],
		'1.0',
		true
	);
	wp_enqueue_script('tender-script');

	wp_localize_script(
		'tender-script',
		'tender',
		array('ajax_url' => admin_url('admin-ajax.php'))
	);
}

add_action('wp_enqueue_scripts', 'tender_load_scripts', 1);
