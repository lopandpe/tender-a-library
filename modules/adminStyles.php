<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_load_admin_styles()
{
	wp_enqueue_style(
		'tender-admin-style', // Unique identifier
		plugin_dir_url(__FILE__) . '../css/admin-style.css', // Path to the CSS file
		array(), // Dependencies (if any)
		'1.0', // Version
		'all' // Media type
	);
}
add_action('admin_enqueue_scripts', 'tender_load_admin_styles');
