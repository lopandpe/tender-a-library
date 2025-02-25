<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Register Custom Post Type
function tender_book()
{
	$book_slug = get_option('tender_book_slug', 'libro'); // Valor por defecto
	$section_slug = get_option('tender_section_slug', 'seccion-biblioteca'); // Valor por defecto
	$language_slug = get_option('tender_language_slug', 'idioma-biblioteca'); // Valor por defecto

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

	$capabilities = array(
		'edit_post'              => 'edit_tender_book',
		'read_post'              => 'read_tender_book',
		'delete_post'            => 'delete_tender_book',
		'edit_posts'             => 'edit_tender_books',
		'edit_others_posts'      => 'edit_others_tender_books',
		'publish_posts'          => 'publish_tender_books',
		'read_private_posts'     => 'read_private_tender_books',
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
		'rewrite'               => array('slug' => $book_slug),
		'menu_icon'             => 'dashicons-book-alt',
		'capability_type'       => array('tender_book', 'tender_books'),
		'map_meta_cap'          => true,
		'capabilities'          => $capabilities,
		'show_in_rest'          => true,
	);
	register_post_type('tender_book', $args);

	$tender_section_labels = array(
		'name'                       => _x('Secciones de la biblioteca', 'Taxonomy General Name', 'tender-a-library'),
		'singular_name'              => _x('Sección de la biblioteca', 'Taxonomy Singular Name', 'tender-a-library'),
		'menu_name'                  => __('Secciones de la biblioteca', 'tender-a-library'),
		'all_items'                  => __('Todas las secciones', 'tender-a-library'),
		'parent_item'                => __('Sección madre', 'tender-a-library'),
		'parent_item_colon'          => __('Parent Item:', 'tender-a-library'),
		'new_item_name'              => __('Nueva sección', 'tender-a-library'),
		'add_new_item'               => __('Añadir nueva sección', 'tender-a-library'),
	);
	$tender_section_args = array(
		'labels'                     => $tender_section_labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'meta_box_cb'                => false,
		'rewrite'               => array('slug' => $section_slug),
	);
	register_taxonomy('tender_section', array('tender_book'), $tender_section_args);

	$tender_language_labels = array(
		'name'                       => _x('Idiomas', 'Taxonomy General Name', 'tender-a-library'),
		'singular_name'              => _x('Idioma', 'Taxonomy Singular Name', 'tender-a-library'),
		'menu_name'                  => __('Idiomas', 'tender-a-library'),
		'all_items'                  => __('Todos los idiomas', 'tender-a-library'),
		'new_item_name'              => __('Nuevo idioma', 'tender-a-library'),
		'add_new_item'               => __('Añadir nuevo idioma', 'tender-a-library'),
	);
	$tender_language_args = array(
		'labels'                     => $tender_language_labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'rewrite'               => array('slug' => $language_slug),
	);
	register_taxonomy('tender_language', array('tender_book'), $tender_language_args);
}
add_action('init', 'tender_book', 0);
