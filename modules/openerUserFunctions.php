<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function limit_opener_roles($roles)
{
	// Si el usuario actual es "Opener" y NO es administrador:
	if (current_user_can('opener') && !current_user_can('administrator')) {
		// Deja únicamente "reader" en la lista de roles
		return array(
			'reader' => $roles['reader'],
		);
	}
	return $roles;
}
add_filter('editable_roles', 'limit_opener_roles');

function force_reader_role_for_opener($user_id)
{
	// Si el usuario que está creando es "Opener"
	if (current_user_can('opener') && !current_user_can('administrator')) {
		// Fuerza el rol del nuevo usuario a "reader"
		$user = new WP_User($user_id);
		$user->set_role('reader');
	}
}
add_action('user_register', 'force_reader_role_for_opener');

/**
 * Muestra solo usuarios con rol "reader" al "Opener" en la tabla de Usuarios.
 */
function filter_opener_user_list($query)
{
	// Verificamos que estemos en el admin "users.php"
	global $pagenow;

	// Verificamos que el usuario actual sea "Opener" (y no sea Administrador)
	if (
		'users.php' === $pagenow
		&& current_user_can('opener')
		&& ! current_user_can('administrator')
	) {
		// Forzamos la búsqueda de usuarios con rol "reader" únicamente
		$query->set('role', 'reader');
	}
}
add_action('pre_get_users', 'filter_opener_user_list');

/**
 * Evita que "Opener" edite usuarios que no sean "reader".
 */
function restrict_opener_edit_non_reader($allcaps, $caps, $args, $user)
{
	// $args[0] suele ser la capacidad solicitada (ej: 'edit_user')
	// $args[1] no siempre, hay que mirar su contenido
	// $args[2] suele ser el ID del usuario que se intenta editar
	// Revisa: https://developer.wordpress.org/reference/hooks/user_has_cap/

	// Asegurarnos de que se está pidiendo editar a un usuario: 'edit_user' o 'edit_users'
	// y que sea un "Opener" (pero no administrador).
	if (
		! empty($args[0])
		&& in_array($args[0], array('edit_user', 'edit_users'), true)
		&& current_user_can('opener')
		&& ! current_user_can('administrator')
	) {
		// $args[2] es el ID del usuario al que se intenta editar
		if (isset($args[2]) && (int) $args[2] > 0) {
			$user_to_edit = get_userdata($args[2]);
			if ($user_to_edit) {
				// Si el usuario a editar NO tiene el rol "reader",
				// le quitamos la capacidad de editarlo.
				if (! in_array('reader', (array) $user_to_edit->roles, true)) {
					// Quitar la capacidad 'edit_users'
					if (isset($allcaps['edit_users'])) {
						unset($allcaps['edit_users']);
					}
					// Quitar también 'edit_user' si está
					if (isset($allcaps['edit_user'])) {
						unset($allcaps['edit_user']);
					}
				}
			}
		}
	}
	return $allcaps;
}
add_filter('user_has_cap', 'restrict_opener_edit_non_reader', 10, 4);

function tal_current_user_opener_or_admin()
{
	// Si el usuario actual es "Opener" o "Administrador"
	if (current_user_can('opener') || current_user_can('administrator')) {
		return true;
	}
	return false;
}


add_action('carbon_fields_register_fields', 'tal_attach_user_fields');

function tal_attach_user_fields()
{
	Container::make('user_meta', __('More info', 'tender-a-library'))
		->add_fields(array(
			Field::make('checkbox', 'newsletter', __('Receive newsletter by e-mail', 'tender-a-library'))
				->set_option_value('1'),
			Field::make('text', 'phone_number', __('Phone number', 'tender-a-library'))
				->set_attribute('placeholder', '+34 XXX XX XX XX')
				->set_required(true)
		));
}
add_action('after_setup_theme', 'crb_load');
