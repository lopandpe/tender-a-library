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
		'name'                  => _x('Events', 'Post Type General Name', 'tender-a-library'),
		'singular_name'         => _x('Event', 'Post Type Singular Name', 'tender-a-library'),
		'menu_name'             => __('Events', 'tender-a-library'),
		'all_items'             => __('All events', 'tender-a-library'),
		'add_new_item'          => __('Add new event', 'tender-a-library'),
		'edit_item'             => __('Edit event', 'tender-a-library'),
		'view_item'             => __('View event', 'tender-a-library'),
	);


	$args = array(
		'label'                 => __('Event', 'tender-a-library'),
		'description'           => __('Event in the library', 'tender-a-library'),
		'labels'                => $labels,
		'supports'              => array('title', 'thumbnail', 'editor', 'excerpt', 'revisions', 'custom-fields'),
		'taxonomies'            => array('tender_section'),
		'public'                => true,
		'has_archive'           => false,
		'rewrite'               => array('slug' => $event_slug),
		'menu_icon'             => 'dashicons-calendar-alt',
		'capability_type'       => array('tender_event', 'tender_events'),
		'map_meta_cap'          => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
	);
	register_post_type('tender_event', $args);

	
}
add_action('init', 'tender_event', 0);
