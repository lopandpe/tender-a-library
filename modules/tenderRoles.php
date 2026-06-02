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
			

			// Reader/user management and lending desk access.
			'create_users' => true,
			'edit_users'   => true,
			'list_users'   => true,
			'create_lendings' => true,
			// No delete/promote caps: openers can manage reader accounts only.
		)
	);
}
add_action('init', 'tal_sync_librarian_role_label', 1);
function tal_sync_librarian_role_label()
{
	global $wp_roles;

	if (!isset($wp_roles) || !isset($wp_roles->roles['librarian'])) {
		return;
	}

	$label = __('Bibliotecarix', 'tender-a-library');

	if (isset($wp_roles->roles['librarian']['name']) && $wp_roles->roles['librarian']['name'] === $label) {
		return;
	}

	$wp_roles->roles['librarian']['name'] = $label;
	$wp_roles->role_names['librarian'] = $label;
	update_option($wp_roles->role_key, $wp_roles->roles);
}

add_action('init', 'create_librarian_role', 0);
function create_librarian_role()
{
	add_role(
		'librarian',
		__('Bibliotecarix', 'tender-a-library'),  // Nombre que aparecerá en WP
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
			

			// Same reader/user management and lending desk access as opener.
			'create_users' => true,
			'edit_users'   => true,
			'list_users'   => true,
			'create_lendings' => true,
			// No delete/promote caps: librarians can manage reader accounts only.
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
    $roles = ['administrator', 'opener', 'librarian'];

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->add_cap('create_lendings');
        }
    }

    foreach (['author', 'contributor', 'editor'] as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('create_lendings');
        }
    }
}

function tal_sync_reader_manager_caps() {
    foreach (['opener', 'librarian'] as $role_name) {
        $role = get_role($role_name);
        if (!$role) {
            continue;
        }

        $role->add_cap('create_users');
        $role->add_cap('edit_users');
        $role->add_cap('list_users');
        $role->remove_cap('delete_users');
        $role->remove_cap('promote_users');
        $role->remove_cap('remove_users');
        $role->remove_cap('add_users');
    }
}
add_action('init', 'tal_sync_reader_manager_caps');

function tal_add_librarian_caps() {
    $roles = ['administrator', 'librarian'];
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if (!$role) {
            continue;
        }
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
