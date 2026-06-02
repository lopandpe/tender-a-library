<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
add_action('init', 'add_tender_book_caps_to_existing_roles', 0);
function add_tender_book_caps_to_existing_roles()
{
	// Roles with full management access to the library catalog.
	$roles = array('administrator', 'librarian');

	// Las mismas capacidades que definiste arriba en $capabilities
	$caps = array(
		'edit_tender_book',
		'read_tender_book',
		'delete_tender_book',
		'edit_tender_books',
		'edit_others_tender_books',
		'publish_tender_books',
		'read_private_tender_books',
		'delete_tender_books',
		'delete_private_tender_books',
		'delete_published_tender_books',
		'delete_others_tender_books',
		'edit_private_tender_books',
		'edit_published_tender_books',
		'create_tender_books',
	);

	foreach ($roles as $role_name) {
		$role = get_role($role_name);
		if (!$role) continue;

		foreach ($caps as $cap) {
			$role->add_cap($cap);
		}
	}

	// Earlier versions granted full catalog permissions to roles that should not
	// manage books. Remove persisted write caps while preserving public read.
	$restricted_roles = array('author', 'contributor', 'editor', 'opener');
	$write_caps = array_diff($caps, array('read_tender_book'));
	foreach ($restricted_roles as $role_name) {
		$role = get_role($role_name);
		if (!$role) continue;

		foreach ($write_caps as $cap) {
			$role->remove_cap($cap);
		}
	}
}
