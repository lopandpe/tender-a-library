<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tal_can_see_users_list() {
    $current_user = wp_get_current_user();

    // Admins pueden ver todos los usuarios
    if (user_can($current_user, 'administrator')) return true;

    // Librarian, opener y editor pueden ver la lista de usuarios
    $roles = (array) $current_user->roles;
    if (array_intersect($roles, ['librarian', 'opener', 'editor'])) {
        return true;
    }

    // Reader no puede ver la lista de usuarios
    return false;
}


/**
 * Comprueba si el usuario actual puede VER el perfil del usuario $profile_user_id
 */
function tal_can_view_profile($profile_user_id) {
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();

    // Puede ver siempre su propio perfil
    if ($current_user_id == $profile_user_id) return true;

    // Admins pueden ver todos
    if (user_can($current_user, 'administrator')) return true;

    // Librarian, opener y editor pueden ver todos, pero solo editar algunos (gestión abajo)
    $roles = (array) $current_user->roles;
    if (array_intersect($roles, ['librarian', 'opener', 'editor'])) {
        return true;
    }

    // Los reader solo pueden ver su perfil
    return false;
}

/**
 * Comprueba si el usuario actual puede EDITAR el perfil $profile_user_id
 */
function tal_can_edit_profile($profile_user_id) {
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();

    // Puede editar siempre su propio perfil
    if ($current_user_id == $profile_user_id) return true;

    // Admins pueden editar todos
    if (user_can($current_user, 'administrator')) return true;

    // Librarian, opener, editor pueden editar los reader
    $profile_user = get_userdata($profile_user_id);
    $editable_roles = ['reader'];

    $roles = (array) $current_user->roles;
    if (array_intersect($roles, ['librarian', 'opener', 'editor'])) {
        foreach ($profile_user->roles as $profile_role) {
            if (in_array($profile_role, $editable_roles, true)) {
                return true;
            }
        }
    }

    // Otros casos: no pueden editar
    return false;
}