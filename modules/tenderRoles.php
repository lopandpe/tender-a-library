<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

add_action('init', 'create_opener_role', 0);
function create_opener_role()
{
	add_role(
		'opener',
		__('Opener', 'tender-a-library'),  // Nombre que aparecerá en WP
		array(
			// Para acceder al Dashboard, ver su perfil
			'read' => true,

			
			'read_tender_book'               => true,
			'delete_tender_book'             => false,
			'edit_tender_books'              => false,
			'edit_others_tender_books'       => false,
			'publish_tender_books'           => false,
			'read_private_tender_books'      => false,
			'delete_tender_books'            => false,
			'delete_private_tender_books'    => false,
			'delete_published_tender_books'  => false,
			'delete_others_tender_books'     => false,
			'edit_private_tender_books'      => false,
			'edit_published_tender_books'    => false,
			'create_tender_books'            => false,
			

			// Capacidades para crear y editar usuarios:
			'create_users' => false,  // Permite añadir usuarios
			'edit_users'   => false,  // Permite editar usuarios (cambiar datos, resetear contraseña, etc.)
			'list_users'   => false,  // Permite ver el listado de usuarios (importante si quieres que vea la tabla)
			// No agregamos 'delete_users' => true, para que NO pueda eliminarlos
			// No agregamos 'promote_users' => true, si no quieres que cambie roles existentes (ver siguiente sección)
		)
	);
}
add_action('init', 'create_librarian_role', 0);
function create_librarian_role()
{
	add_role(
		'librarian',
		__('Librarian', 'tender-a-library'),  // Nombre que aparecerá en WP
		array(
			// Para acceder al Dashboard, ver su perfil
			'read' => true,

			// Capacidades de tu Custom Post Type (CPT) tender_book (igual que antes):
			'edit_tender_book'               => true,
			'read_tender_book'               => true,
			'delete_tender_book'             => true,
			'edit_tender_books'              => true,
			'edit_others_tender_books'       => true,
			'publish_tender_books'           => true,
			'read_private_tender_books'      => true,
			'delete_tender_books'            => true,
			'delete_private_tender_books'    => true,
			'delete_published_tender_books'  => true,
			'delete_others_tender_books'     => true,
			'edit_private_tender_books'      => true,
			'edit_published_tender_books'    => true,
			'create_tender_books'            => true,

			'manage_tender_sections'         => true,
			'edit_tender_sections'           => true,
			'delete_tender_sections'         => true,
			'assign_tender_sections'         => true,

			'manage_tender_languages'         => true,
			'edit_tender_languages'           => true,
			'delete_tender_languages'         => true,
			'assign_tender_languages'         => true,
			

			// Capacidades para crear y editar usuarios:
			'create_users' => false,  // Permite añadir usuarios
			'edit_users'   => false,  // Permite editar usuarios (cambiar datos, resetear contraseña, etc.)
			'list_users'   => false,  // Permite ver el listado de usuarios (importante si quieres que vea la tabla)
			// No agregamos 'delete_users' => true, para que NO pueda eliminarlos
			// No agregamos 'promote_users' => true, si no quieres que cambie roles existentes (ver siguiente sección)
		)
	);
}

add_action('init', 'create_reader_role', 0);
function create_reader_role()
{
	add_role(
		'reader',        // Identificador interno del rol
		__('Reader', 'tender-a-library'),  // Nombre que aparecerá en WP
		array(
			'read' => true,  // Puede iniciar sesión y ver su propio perfil
			// No añadimos más capacidades, así no podrá editar posts/páginas ni contenidos
		)
	);
}



add_action('init', 'tal_add_library_capability', 0);
function tal_add_library_capability() {
    // Roles a los que se le asignará la capability
    $roles = ['administrator', 'opener', 'librarian', 'author', 'editor'];

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('create_lendings')) {
            $role->add_cap('create_lendings');
        }
    }
}

function tal_cleanup_opener_caps() {
    $role = get_role('opener');
    if ($role) {
        $role->remove_cap('list_users');
        $role->remove_cap('edit_users');
        $role->remove_cap('delete_users');
        $role->remove_cap('create_users');
        $role->remove_cap('promote_users');
        $role->remove_cap('remove_users');
        $role->remove_cap('add_users');
        $role->remove_cap('edit_user');
        $role->remove_cap('edit_others_users');
    }
}
add_action('init', 'tal_cleanup_opener_caps');

function tal_add_librarian_caps() {
    $role = get_role('librarian');
    if ($role) {
        $role->add_cap('manage_tender_sections');
        $role->add_cap('edit_tender_sections');
        $role->add_cap('delete_tender_sections');
        $role->add_cap('assign_tender_sections');
        $role->add_cap('manage_tender_languages');
        $role->add_cap('edit_tender_languages');
        $role->add_cap('delete_tender_languages');
        $role->add_cap('assign_tender_languages');
    }
}
add_action('init', 'tal_add_librarian_caps');
