<?php
if (! defined('ABSPATH')) {
	exit;
}

// 1. PLANTILLA PARA TEMAS CLÁSICOS (no-FSE)
function tender_book_classic_template($template)
{
	// Sólo para temas clásicos
	if (wp_is_block_theme()) {
		return $template;
	}

	if (is_singular('tender_book')) {
		$plugin_template = plugin_dir_path(__FILE__) . 'templates/single-tender-book.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}

	return $template;
}
add_filter('template_include', 'tender_book_classic_template');

// 2. REGISTRAR Y CARGAR PLANTILLA PARA TEMAS FSE
function tender_book_handle_fse_templates($template, $context, $type)
{
	// Sólo procesar dentro de un tema FSE, para nuestro CPT y tipo de plantilla correcto
	if (
		! wp_is_block_theme()
		|| 'wp_template' !== $type
		|| ! is_singular('tender_book')
	) {
		return $template;
	}

	$plugin_template_path = plugin_dir_path(__FILE__) . 'block-templates/single-tender-book.html';
	if (! file_exists($plugin_template_path)) {
		return $template;
	}

	$content = file_get_contents($plugin_template_path);

	$block_template = new WP_Block_Template();
	$block_template->id         = 'plugin//single-tender-book';
	$block_template->slug       = 'single-tender-book';
	$block_template->title      = __('Libro individual', 'tender-a-library');
	$block_template->post_types = array('tender_book');
	$block_template->type       = 'wp_template';
	$block_template->source     = 'plugin';
	$block_template->content    = $content;
	$block_template->status     = 'publish';

	return $block_template;
}
add_filter('pre_get_block_template', 'tender_book_handle_fse_templates', 10, 3);

// 3. AÑADIR PLANTILLA AL LISTADO EN EL EDITOR FSE
function tender_book_add_to_template_list($templates, $query, $template_type)
{
	// Sólo temas FSE y plantillas (wp_template)
	if (! wp_is_block_theme() || 'wp_template' !== $template_type) {
		return $templates;
	}

	$plugin_template_path = plugin_dir_path(__FILE__) . 'block-templates/single-tender-book.html';
	if (! file_exists($plugin_template_path)) {
		return $templates;
	}

	$content = file_get_contents($plugin_template_path);

	$block_template = new WP_Block_Template();
	$block_template->id         = 'plugin//single-tender-book';
	$block_template->slug       = 'single-tender-book';
	$block_template->title      = __('Plantilla: Libro individual', 'tender-a-library');
	$block_template->post_types = array('tender_book');
	$block_template->type       = 'wp_template';
	$block_template->source     = 'plugin';
	$block_template->content    = $content;
	$block_template->status     = 'publish';

	// Lo ponemos al principio de la lista
	array_unshift($templates, $block_template);

	return $templates;
}
add_filter('get_block_templates', 'tender_book_add_to_template_list', 10, 3);
