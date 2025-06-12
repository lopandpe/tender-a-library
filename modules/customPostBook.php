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
		'name'                  => _x('Books', 'Post Type General Name', 'tender-a-library'),
		'singular_name'         => _x('Book', 'Post Type Singular Name', 'tender-a-library'),
		'menu_name'             => __('Books', 'tender-a-library'),
		'all_items'             => __('All books', 'tender-a-library'),
		'add_new_item'          => __('Add new book', 'tender-a-library'),
		'edit_item'             => __('Edit book', 'tender-a-library'),
		'view_item'             => __('View book', 'tender-a-library'),
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
		'label'                 => __('Book', 'tender-a-library'),
		'description'           => __('Library book', 'tender-a-library'),
		'labels'                => $labels,
		'supports'              => array('title', 'thumbnail'),
		'taxonomies'            => array('tender_section'),
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
		'name'                       => _x('Library sections', 'Taxonomy General Name', 'tender-a-library'),
		'singular_name'              => _x('Library section', 'Taxonomy Singular Name', 'tender-a-library'),
		'menu_name'                  => __('Library sections', 'tender-a-library'),
		'all_items'                  => __('All sections', 'tender-a-library'),
		'parent_item'                => __('Parent section', 'tender-a-library'),
		'parent_item_colon'          => __('Parent Item:', 'tender-a-library'),
		'new_item_name'              => __('New section', 'tender-a-library'),
		'add_new_item'               => __('Add new section', 'tender-a-library'),
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
		'name'                       => _x('Languages', 'Taxonomy General Name', 'tender-a-library'),
		'singular_name'              => _x('Language', 'Taxonomy Singular Name', 'tender-a-library'),
		'menu_name'                  => __('Languages', 'tender-a-library'),
		'all_items'                  => __('All languages', 'tender-a-library'),
		'new_item_name'              => __('New language', 'tender-a-library'),
		'add_new_item'               => __('Add new language', 'tender-a-library'),
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
