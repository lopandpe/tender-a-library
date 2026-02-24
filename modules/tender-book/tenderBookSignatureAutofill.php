<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_enqueue_scripts', 'tal_enqueue_tender_book_signature_autofill_script');
function tal_enqueue_tender_book_signature_autofill_script($hook)
{
	if (!in_array($hook, array('post-new.php', 'post.php'), true)) {
		return;
	}

	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'tender_book') {
		return;
	}

	$script_path = plugin_dir_path(__FILE__) . '../../assets/js/admin/tender-book-signature-autofill.js';
	$script_url = plugin_dir_url(__FILE__) . '../../assets/js/admin/tender-book-signature-autofill.js';
	$version = file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0';

	wp_enqueue_script(
		'tal-tender-book-signature-autofill',
		$script_url,
		array(),
		$version,
		true
	);
}
