<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Register Custom Post Type
function tender_book()
{
	$labels = array(
		'name'                  => _x('Libros', 'Post Type General Name', 'tender-a-library'),
		'singular_name'         => _x('Libro', 'Post Type Singular Name', 'tender-a-library'),
		'menu_name'             => __('Libros', 'tender-a-library'),
		'all_items'             => __('Todos los libros', 'tender-a-library'),
		'add_new_item'          => __('Añadir nuevo libro', 'tender-a-library'),
		'edit_item'             => __('Editar libro', 'tender-a-library'),
		'view_item'             => __('Ver libro', 'tender-a-library'),
		// ...el resto de labels...
	);

	// Define aquí capacidades personalizadas para tu CPT
	$capabilities = array(
		// Estas meta capabilities se "mapearán" a las que asignes a los roles
		'edit_post'              => 'edit_tender_book',
		'read_post'              => 'read_tender_book',
		'delete_post'            => 'delete_tender_book',
		'edit_posts'             => 'edit_tender_books',
		'edit_others_posts'      => 'edit_others_tender_books',
		'publish_posts'          => 'publish_tender_books',
		'read_private_posts'     => 'read_private_tender_books',

		// Capacidades adicionales para mayor control
		'delete_posts'           => 'delete_tender_books',
		'delete_private_posts'   => 'delete_private_tender_books',
		'delete_published_posts' => 'delete_published_tender_books',
		'delete_others_posts'    => 'delete_others_tender_books',
		'edit_private_posts'     => 'edit_private_tender_books',
		'edit_published_posts'   => 'edit_published_tender_books',
		'create_posts'           => 'create_tender_books', // WP >= 5.0
	);

	$args = array(
		'label'                 => __('Libro', 'tender-a-library'),
		'description'           => __('Libro en la biblioteca', 'tender-a-library'),
		'labels'                => $labels,
		'supports'              => array('title', 'thumbnail'),
		'taxonomies'            => array('tender-section'),
		'public'                => true,
		'has_archive'           => true,
		'menu_icon'             => 'dashicons-book-alt',
		'capability_type'       => array('tender_book', 'tender_books'),
		'map_meta_cap'          => true, // Importante
		'capabilities'          => $capabilities, // Aquí asignamos el set personalizado
		'show_in_rest'          => true,
	);
	register_post_type('tender_book', $args);
}
add_action('init', 'tender_book', 0);
