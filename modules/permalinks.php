<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
function tender_add_permalink_settings()
{
	add_settings_section(
		'tender_permalink_section',
		__('Library settings', 'tender-a-library'),
		function () {
			echo '<p>' . __('Set up slugs for books and their taxonomies.', 'tender-a-library') . '</p>';
		},
		'permalink'
	);

	add_settings_field(
		'tender_book_slug',
		__('Slug for books', 'tender-a-library'),
		function () {
			$value = get_option('tender_book_slug', 'libro');
			echo '<input type="text" name="tender_book_slug" value="' . esc_attr($value) . '" class="regular-text" />';
		},
		'permalink',
		'tender_permalink_section'
	);

	add_settings_field(
		'tal_library_search_page',              // ID del campo
		__('Book search page', 'tender-a-library'),              // Etiqueta
		'tal_library_search_page_callback',     // Función de renderizado
		'permalink',                     // Página donde se muestra
		'tal_library_settings'           // ID de la sección
	);

	add_settings_section(
		'tal_profile_settings',          // ID de la sección
		__('Profile settings', 'tender-a-library'),       // Título de la sección
		'__return_false',                // Callback (no necesitamos descripción)
		'permalink'                      // Página donde se muestra
	);

	add_settings_field(
		'tal_users_page',
		__('Users management page', 'tender-a-library'),
		'tal_users_list_page_callback',
		'permalink',
		'tal_profile_settings'
	);

	add_settings_field(
		'tal_profile_page',              // ID del campo
		__('Profile page', 'tender-a-library'),              // Etiqueta
		'tal_profile_page_callback',     // Función de renderizado
		'permalink',                     // Página donde se muestra
		'tal_profile_settings'           // ID de la sección
	);

	add_settings_field(
		'tal_edit_profile_page',
		__('Profile edit page', 'tender-a-library'),
		'tal_edit_profile_page_callback',
		'permalink',
		'tal_profile_settings'
	);

	register_setting('permalink', 'tender_book_slug', 'sanitize_text_field');
	register_setting('permalink', 'tal_library_search_page', 'sanitize_text_field');
	register_setting('permalink', 'tal_profile_page');
	register_setting('permalink', 'tal_edit_profile_page');
	register_setting('permalink', 'tal_users_list_page');

}
add_action('admin_init', 'tender_add_permalink_settings');

add_filter('display_post_states', 'tal_add_special_pages_state', 10, 2);
function tal_add_special_pages_state($post_states, $post) {
    $states = [
        'tal_library_page_id'     => __('Searching page', 'tender-a-library'),
        'tal_profile_page'        => __('Profile page', 'tender-a-library'),
        'tal_edit_profile_page'   => __('Profile edit page', 'tender-a-library'),
        'tal_users_list_page'     => __('Users management page', 'tender-a-library'),
    ];

    foreach ($states as $option => $label) {
        $page_id = get_option($option);
        if ($page_id && $post->ID == $page_id) {
            $post_states[$option] = $label;
        }
    }

    return $post_states;
}



function tender_save_permalink_settings()
{
	$fields = ['permalink_structure', 'tender_book_slug', 'tal_library_search_page', 'tal_profile_page', 'tal_edit_profile_page', 'tal_users_list_page'];
	$isAnyFieldSet = false;

	// Verificar si se ha establecido algún campo
	foreach ($fields as $field) {
		if (isset($_POST[$field])) {
			if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-permalink')) {
				return;
			}
			$isAnyFieldSet = true;
			update_option($field, sanitize_text_field($_POST[$field]));
		}
	}

	// Si se ha actualizado algún campo, actualizar las reglas de reescritura
	if ($isAnyFieldSet) {
		flush_rewrite_rules();
	}
}
add_action('admin_init', 'tender_save_permalink_settings', 20);


// Callback para mostrar el selector de páginas
function tal_library_search_page_callback()
{
	$selected = get_option('tal_library_search_page');
	wp_dropdown_pages([
		'name' => 'tal_library_search_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-a-library'),
	]);
}
function tal_profile_page_callback()
{
	$selected = get_option('tal_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_profile_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-a-library'),
	]);
}

function tal_edit_profile_page_callback()
{
	$selected = get_option('tal_edit_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_edit_profile_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-a-library'),
	]);
}
function tal_users_list_page_callback()
{
	$selected = get_option('tal_users_list_page');
	wp_dropdown_pages([
		'name' => 'tal_users_list_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-a-library'),
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

function tender_add_body_classes($classes)
{
	// Verificar si es la página de perfil o la de edición de perfil
	$profile_page_id = get_option('tal_profile_page');
	if (is_page($profile_page_id)) {
		$classes[] = 'tender-page tender-profile-page';
	}

	$edit_profile_page_id = get_option('tal_edit_profile_page');
	if (is_page($edit_profile_page_id)) {
		$classes[] = 'tender-page tender-edit-profile-page';
	}
	// Verificar si estamos en la página de búsqueda de libros
	$library_search_page_id = get_option('tal_library_search_page');
	if (is_page($library_search_page_id)) {
		$classes[] = 'tender-page tender-library-search-page';
	}
	// Verificar si estamos en la página de listado de usuarios
	$users_list_page_id = get_option('tal_users_list_page');
	if (is_page($users_list_page_id)) {
		$classes[] = 'tender-page tender-users-list-page';
	}

	// Verificar si estamos en un CPT de libros
	if (is_singular('tender_book')) {
		$classes[] = 'tender-page tender-book-page';
	}

	return $classes;
}
add_filter('body_class', 'tender_add_body_classes');


function tender_profile_url(){
	$profile_page_id = get_option('tal_profile_page');
	$profile_page_url = get_permalink($profile_page_id);
	return $profile_page_url;
}