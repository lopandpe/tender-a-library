<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_load_styles()
{
	$styles = [
		'admin' => [
			'tender-admin-style' => 'css/admin-style.css',
			'tender-admin-tailwind-style' => 'css/tender-admin.css',
		],
		'public' => [
			'tender-public-style-tailwind' => 'css/tender-public.css',
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
add_action('admin_enqueue_scripts', 'tender_load_styles', 1);
add_action('wp_enqueue_scripts', 'tender_load_styles', 1);
