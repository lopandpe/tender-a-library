<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}
function tal_localization()
{
	load_plugin_textdomain('tender-a-library', false, 'tender-a-library/languages');
}
add_action('init', 'tal_localization');
