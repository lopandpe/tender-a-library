<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
// Tender (A) Library
include_once __DIR__ . '/../block-includes/book-summary-functions.php';
include_once __DIR__ . '/../block-includes/book-cover-functions.php';
include_once __DIR__ . '/../block-includes/book-data-functions.php';
include_once __DIR__ . '/../block-includes/mini-book-functions.php';
include_once __DIR__ . '/../block-includes/book-search-functions.php';
include_once __DIR__ . '/../block-includes/upcoming-events-functions.php';
include_once __DIR__ . '/../block-includes/profile-links-functions.php';
include_once __DIR__ . '/../block-includes/event-date-functions.php';
include_once __DIR__ . '/../block-includes/latest-books-functions.php';

function tender_plugin_block_categories($categories)
{
	return array_merge(
		$categories,
		[
			[
				'slug'  => 'tender-blocks',
				'title' => __('Tender Blocks', 'tender-a-library'),
			],
		]
	);
}
add_action('block_categories_all', 'tender_plugin_block_categories', 10, 2);

function tender_tender_block_init()
{

	register_block_type(__DIR__ . '/../build/book-summary', array(
		'render_callback' => 'tender_book_summary_render_callback',
	));
	register_block_type(__DIR__ . '/../build/book-cover', array(
		'render_callback' => 'tender_book_cover_render_callback',
	));
	register_block_type(__DIR__ . '/../build/book-data', array(
		'render_callback' => 'tender_book_data_render_callback',
	));
	register_block_type(__DIR__ . '/../build/mini-book', array(
		'render_callback' => 'tender_mini_book_render_callback',
	));
	register_block_type(__DIR__ . '/../build/book-search', array(
		'render_callback' => 'tender_book_search_render_callback',
	));
	register_block_type(__DIR__ . '/../build/upcoming-events', array(
		'render_callback' => 'tender_upcoming_events_render_callback',
	));
	register_block_type(__DIR__ . '/../build/profile-links', array(
		'render_callback' => 'tender_profile_links_render_callback',
	));
	register_block_type(__DIR__ . '/../build/event-date', array(
		'render_callback' => 'tender_event_date_render_callback',
	));
	register_block_type(__DIR__ . '/../build/latest-books', array(
		'render_callback' => 'tender_latest_books_render_callback',
	));
}
add_action('init', 'tender_tender_block_init');


// Helper function to build block classes from attributes
function tender_get_block_classes($block_attributes, $base_class = '') {
	$classes = $base_class;

	// Add block alignment class if set
	if (isset($block_attributes['align']) && !empty($block_attributes['align'])) {
		$classes .= ' align' . esc_attr($block_attributes['align']);
	}

	// Add custom className if set (from Gutenberg "Additional CSS class(es)")
	if (isset($block_attributes['className']) && !empty($block_attributes['className'])) {
		$classes .= ' ' . esc_attr($block_attributes['className']);
	}

	// Add background color class if set (from Gutenberg color support)
	if (isset($block_attributes['backgroundColor']) && !empty($block_attributes['backgroundColor'])) {
		$classes .= ' has-' . esc_attr($block_attributes['backgroundColor']) . '-background-color';
	}

	return trim($classes);
}
