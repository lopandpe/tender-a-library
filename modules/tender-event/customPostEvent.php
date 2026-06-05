<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Register Custom Post Type
function tender_event()
{


	// Obtener el valor del slug desde la opción de WordPress
	$event_slug = get_option('tender_event_slug', 'evento'); // Valor por defecto

	$labels = array(
		'name'                  => _x('Events', 'Post Type General Name', 'tender-library'),
		'singular_name'         => _x('Event', 'Post Type Singular Name', 'tender-library'),
		'menu_name'             => __('Events', 'tender-library'),
		'all_items'             => __('All events', 'tender-library'),
		'add_new_item'          => __('Add new event', 'tender-library'),
		'edit_item'             => __('Edit event', 'tender-library'),
		'view_item'             => __('View event', 'tender-library'),
	);


	$args = array(
		'label'                 => __('Event', 'tender-library'),
		'description'           => __('Event in the library', 'tender-library'),
		'labels'                => $labels,
		'supports'              => array('title', 'thumbnail', 'editor', 'excerpt', 'revisions', 'custom-fields'),
		'taxonomies'            => array('tender_section'),
		'public'                => true,
		'has_archive'           => false,
		'rewrite'               => array('slug' => $event_slug),
		'menu_icon'             => 'dashicons-calendar-alt',
		'capability_type'       => 'post',
		'show_in_rest'          => true,
	);
	register_post_type('tender_event', $args);

	
}
add_action('init', 'tender_event', 0);
