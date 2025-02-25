<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
function tender_add_permalink_settings()
{
	add_settings_section(
		'tender_permalink_section',
		__('Configuración de la biblioteca', 'tender-a-library'),
		function () {
			echo '<p>' . __('Configura los slugs para los libros y sus taxonomías.', 'tender-a-library') . '</p>';
		},
		'permalink'
	);

	add_settings_field(
		'tender_book_slug',
		__('Slug para los libros', 'tender-a-library'),
		function () {
			$value = get_option('tender_book_slug', 'libro');
			echo '<input type="text" name="tender_book_slug" value="' . esc_attr($value) . '" class="regular-text" />';
		},
		'permalink',
		'tender_permalink_section'
	);

	add_settings_field(
		'tender_section_slug',
		__('Slug para las secciones de la biblioteca', 'tender-a-library'),
		function () {
			$value = get_option('tender_section_slug', 'seccion-biblioteca');
			echo '<input type="text" name="tender_section_slug" value="' . esc_attr($value) . '" class="regular-text" />';
		},
		'permalink',
		'tender_permalink_section'
	);

	add_settings_field(
		'tender_language_slug',
		__('Slug para los idiomas', 'tender-a-library'),
		function () {
			$value = get_option('tender_language_slug', 'idioma-biblioteca');
			echo '<input type="text" name="tender_language_slug" value="' . esc_attr($value) . '" class="regular-text" />';
		},
		'permalink',
		'tender_permalink_section'
	);

	add_settings_section(
		'tal_profile_settings',          // ID de la sección
		'Configuración de Perfil',       // Título de la sección
		'__return_false',                // Callback (no necesitamos descripción)
		'permalink'                      // Página donde se muestra
	);

	add_settings_field(
		'tal_profile_page',              // ID del campo
		'Página de Perfil',              // Etiqueta
		'tal_profile_page_callback',     // Función de renderizado
		'permalink',                     // Página donde se muestra
		'tal_profile_settings'           // ID de la sección
	);

	add_settings_field(
		'tal_edit_profile_page',
		'Página de Edición de Perfil',
		'tal_edit_profile_page_callback',
		'permalink',
		'tal_profile_settings'
	);

	register_setting('permalink', 'tender_book_slug', 'sanitize_text_field');
	register_setting('permalink', 'tender_section_slug', 'sanitize_text_field');
	register_setting('permalink', 'tender_language_slug', 'sanitize_text_field');
	register_setting('permalink', 'tal_profile_page');
	register_setting('permalink', 'tal_edit_profile_page');
}
add_action('admin_init', 'tender_add_permalink_settings');

function tender_save_permalink_settings()
{
	$fields = ['permalink_structure', 'tender_book_slug', 'tender_section_slug', 'tender_language_slug', 'tal_profile_page', 'tal_edit_profile_page'];
	$isAnyFieldSet = false;



	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-permalink')) {
				return;
			}
			$isAnyFieldSet = true;
			update_option($field, sanitize_text_field($_POST[$field]));
		}
	}
	if ($isAnyFieldSet) {
		flush_rewrite_rules();
	}
}
add_action('admin_init', 'tender_save_permalink_settings', 20);

// Callback para mostrar el selector de páginas
function tal_profile_page_callback()
{
	$selected = get_option('tal_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_profile_page',
		'selected' => $selected,
		'show_option_none' => '— Seleccionar Página —',
	]);
}

function tal_edit_profile_page_callback()
{
	$selected = get_option('tal_edit_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_edit_profile_page',
		'selected' => $selected,
		'show_option_none' => '— Seleccionar Página —',
	]);
}


function tender_activate()
{
	tender_book(); // Register the custom post type and taxonomies
	tender_create_database_tables();
	flush_rewrite_rules(); // Flush rewrite rules
}
register_activation_hook(__FILE__, 'tender_activate');

function tender_deactivate()
{
	flush_rewrite_rules(); // Flush rewrite rules
}
register_deactivation_hook(__FILE__, 'tender_deactivate');
