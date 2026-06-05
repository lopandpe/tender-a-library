<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Register Custom Post Type
function tender_book()
{
	$book_slug = get_option('tender_book_slug', 'libro'); // Valor por defecto

	$labels = array(
		'name'                  => _x('Books', 'Post Type General Name', 'tender-library'),
		'singular_name'         => _x('Book', 'Post Type Singular Name', 'tender-library'),
		'menu_name'             => __('Books', 'tender-library'),
		'all_items'             => __('All books', 'tender-library'),
		'add_new_item'          => __('Add new book', 'tender-library'),
		'edit_item'             => __('Edit book', 'tender-library'),
		'view_item'             => __('View book', 'tender-library'),
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
		'label'                 => __('Book', 'tender-library'),
		'description'           => __('Library book', 'tender-library'),
		'labels'                => $labels,
		'supports'              => array('title', 'thumbnail', 'custom-fields'),
		'taxonomies'            => array('tender_section'),
		'public'                => true,
		'has_archive'           => false,
		'rewrite'               => array('slug' => $book_slug),
		'menu_icon'             => 'dashicons-book-alt',
		'capability_type'       => array('tender_book', 'tender_books'),
		'map_meta_cap'          => true,
		'capabilities'          => $capabilities,
		'show_in_rest'          => true,
	);
	register_post_type('tender_book', $args);

	$tender_section_labels = array(
		'name'                       => _x('Library sections', 'Taxonomy General Name', 'tender-library'),
		'singular_name'              => _x('Library section', 'Taxonomy Singular Name', 'tender-library'),
		'menu_name'                  => __('Library sections', 'tender-library'),
		'all_items'                  => __('All sections', 'tender-library'),
		'parent_item'                => __('Parent section', 'tender-library'),
		'parent_item_colon'          => __('Parent Item:', 'tender-library'),
		'new_item_name'              => __('New section', 'tender-library'),
		'add_new_item'               => __('Add new section', 'tender-library'),
	);
	$tender_section_args = array(
		'labels'                     => $tender_section_labels,
		'hierarchical'               => true,
		'show_in_rest'              => true,
		'rewrite'                   => false, 
		'publicly_queryable' 		=> false, 
		'capabilities' => [
			'manage_terms' => 'manage_tender_sections',
			'edit_terms'   => 'edit_tender_sections',
			'delete_terms' => 'delete_tender_sections',
			'assign_terms' => 'assign_tender_sections',
		],
	);
	register_taxonomy('tender_section', array('tender_book'), $tender_section_args);

	$tender_language_labels = array(
		'name'                       => _x('Languages', 'Taxonomy General Name', 'tender-library'),
		'singular_name'              => _x('Language', 'Taxonomy Singular Name', 'tender-library'),
		'menu_name'                  => __('Languages', 'tender-library'),
		'all_items'                  => __('All languages', 'tender-library'),
		'new_item_name'              => __('New language', 'tender-library'),
		'add_new_item'               => __('Add new language', 'tender-library'),
	);
	$tender_language_args = array(
		'labels'                     => $tender_language_labels,
		'hierarchical'               => false,
		'show_in_rest'              => true,
		'rewrite'                   => false, 
		'publicly_queryable' 		=> false, 
		'capabilities' => [
			'manage_terms' => 'manage_tender_languages',
			'edit_terms'   => 'edit_tender_languages',
			'delete_terms' => 'delete_tender_languages',
			'assign_terms' => 'assign_tender_languages',
		],
	);
	register_taxonomy('tender_language', array('tender_book'), $tender_language_args);
}
add_action('init', 'tender_book', 0);


add_action('admin_init', function() {
    remove_meta_box('tagsdiv-tender_section', 'tender_book', 'side');
    remove_meta_box('tagsdiv-tender_language', 'tender_book', 'side');
    
    // Si son taxonomías jerárquicas (como categorías)
    remove_meta_box('tender_sectiondiv', 'tender_book', 'side');
    remove_meta_box('tender_languagediv', 'tender_book', 'side');
});