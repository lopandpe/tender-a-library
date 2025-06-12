<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_library_menu()
{
	if (!tender_user_can_access_library()) {
		return;
	}

	add_menu_page(
		'Biblioteca',
		'Biblioteca',
		'read',
		'tender-library',
		'tender_library_dashboard',
		'dashicons-book',
		25
	);

	$submenus = [
		['Préstamos Activos', 'Préstamos', 'tender-lendings', 'tender_lendings_page'],
		['Préstamos Pasados', 'Préstamos terminados', 'tender-old-lendings', 'tender_old_lendings_page'],
		['Nuevo Préstamo', 'Nuevo Préstamo', 'tender-new-lending', 'tender_new_lending_page'],
	];

	foreach ($submenus as $submenu) {
		add_submenu_page(
			'tender-library',
			$submenu[0],
			$submenu[1],
			'read',
			$submenu[2],
			$submenu[3]
		);
	}
}
add_action('admin_menu', 'tender_library_menu');

function tender_user_can_access_library()
{
	$user = wp_get_current_user();
	$roles = ['administrator', 'editor', 'opener'];

	return array_intersect($roles, $user->roles);
}
