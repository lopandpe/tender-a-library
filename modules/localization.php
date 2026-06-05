<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}
function tal_localization()
{
	$plugin_dir = defined('TENDER_LIBRARY_PLUGIN_FILE')
		? dirname(plugin_basename(TENDER_LIBRARY_PLUGIN_FILE))
		: basename(dirname(__DIR__));

	load_plugin_textdomain('tender-library', false, $plugin_dir . '/languages');
}
add_action('init', 'tal_localization');
