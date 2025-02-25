<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/**
 * Añadir el menú de la Biblioteca en el Dashboard con permisos específicos
 */
function tender_library_menu()
{
	// Verificar si el usuario tiene permisos
	if (!tender_user_can_access_library()) {
		return; // Si no tiene permisos, no se añade el menú
	}

	add_menu_page(
		'Biblioteca',
		'Biblioteca',
		'read', // Se usa 'read' porque lo personalizamos en la validación
		'tender-library',
		'tender_library_dashboard',
		'dashicons-book',
		25
	);

	add_submenu_page(
		'tender-library',
		'Préstamos Activos',
		'Préstamos',
		'read',
		'tender-lendings',
		'tender_lendings_page'
	);

	add_submenu_page(
		'tender-library',
		'Nuevo Préstamo',
		'Nuevo Préstamo',
		'read',
		'tender-new-lending',
		'tender_new_lending_page'
	);
}
add_action('admin_menu', 'tender_library_menu');

/**
 * Verificar si el usuario actual puede acceder a la biblioteca
 */
function tender_user_can_access_library()
{
	$user = wp_get_current_user();

	if (in_array('administrator', $user->roles) || in_array('editor', $user->roles) || in_array('opener', $user->roles)) {
		return true;
	}

	return false;
}
