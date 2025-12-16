<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
add_action('init', 'add_tender_book_caps_to_existing_roles', 0);
function add_tender_book_caps_to_existing_roles()
{
	// Lista de roles a los que darás permisos completos sobre tu CPT
	$roles = array('administrator', 'editor', 'author', 'contributor','librarian', 'opener');

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
}
