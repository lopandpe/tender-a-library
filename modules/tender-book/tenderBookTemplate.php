<?php
if (!defined('ABSPATH')) {
	exit;
}

// 1. TEMAS CLÁSICOS (No FSE): Usar plantilla PHP
function tender_book_classic_template($template)
{
	if (wp_is_block_theme()) {
		return $template;
	}

	if (is_singular('tender_book')) {
		$plugin_template = plugin_dir_path(__DIR__) . '../templates/single-tender-book.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}

	return $template;
}
add_filter('template_include', 'tender_book_classic_template');


// 2. TEMAS FSE (desde WP 6.7): Registrar plantilla desde plugin
add_action('init', 'tender_book_register_fse_template');
function tender_book_register_fse_template()
{
	if (!wp_is_block_theme()) {
		return;
	}

	$slug = 'single-tender_book';
	$plugin_template_path = plugin_dir_path(__DIR__) . '../block-templates/' . $slug . '.html';

	if (!file_exists($plugin_template_path)) {
		return;
	}

	$content = file_get_contents($plugin_template_path);

	register_block_template(
		'tender-a-library//' . $slug,
		[
			'title'       => __('Libro individual', 'tender-a-library'),
			'description' => __('Plantilla personalizada para libros (CPT).', 'tender-a-library'),
			'content'     => $content,
			'post_types'  => ['tender_book'],
		]
	);
}
