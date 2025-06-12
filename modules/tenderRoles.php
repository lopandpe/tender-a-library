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

			// Capacidades para crear y editar usuarios:
			'create_users' => true,  // Permite añadir usuarios
			'edit_users'   => true,  // Permite editar usuarios (cambiar datos, resetear contraseña, etc.)
			'list_users'   => true,  // Permite ver el listado de usuarios (importante si quieres que vea la tabla)
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
