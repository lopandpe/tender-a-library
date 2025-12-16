<?php
// Restrict access to the WordPress dashboard
function tal_restrict_dashboard_access()
{
	if (is_admin() && (current_user_can('opener') || current_user_can('reader')) && !(defined('DOING_AJAX') && DOING_AJAX)) {
		wp_redirect(tender_profile_url());
		exit;
	}
}
add_action('admin_init', 'tal_restrict_dashboard_access');

// Hide the admin bar for subscribers
function tal_remove_admin_bar()
{
	if (current_user_can('opener') || current_user_can('reader')) {
		show_admin_bar(false);
	}
}
add_action('init', 'tal_remove_admin_bar', 0);
