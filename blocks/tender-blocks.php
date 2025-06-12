<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
// Tender (A) Library
include_once __DIR__ . '/includes/book-summary-functions.php';
include_once __DIR__ . '/includes/book-cover-functions.php';
include_once __DIR__ . '/includes/book-data-functions.php';

function tender_plugin_block_categories($categories)
{
	return array_merge(
		$categories,
		[
			[
				'slug'  => 'tender-blocks',
				'title' => __('MimoticTender Blocks', 'tender-a-library'),
			],
		]
	);
}
add_action('block_categories_all', 'tender_plugin_block_categories', 10, 2);

function tender_tender_block_init()
{

	register_block_type(__DIR__ . '/build/book-summary', array(
		'render_callback' => 'tender_book_summary_render_callback',
	));
	register_block_type(__DIR__ . '/build/book-cover', array(
		'render_callback' => 'tender_book_cover_render_callback',
	));
	register_block_type(__DIR__ . '/build/book-data', array(
		'render_callback' => 'tender_book_data_render_callback',
	));
}
add_action('init', 'tender_tender_block_init');
