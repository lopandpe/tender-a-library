<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
function tender_add_permalink_settings()
{
	add_settings_section(
		'tender_permalink_section',
		__('Library settings', 'tender-library'),
		function () {
			echo '<p>' . __('Set up slugs for books and their taxonomies.', 'tender-library') . '</p>';
		},
		'permalink'
	);

	add_settings_field(
		'tender_book_slug',
		__('Slug for books', 'tender-library'),
		function () {
			$value = get_option('tender_book_slug', 'libro');
			echo '<input type="text" name="tender_book_slug" value="' . esc_attr($value) . '" class="regular-text" />';
		},
		'permalink',
		'tender_permalink_section'
	);

	add_settings_field(
		'tal_library_search_page',              // ID del campo
		__('Book search page', 'tender-library'),              // Etiqueta
		'tal_library_search_page_callback',     // Función de renderizado
		'permalink',                     // Página donde se muestra
		'tender_permalink_section'       // ID de la sección
	);

	add_settings_section(
		'tal_profile_settings',          // ID de la sección
		__('Profile settings', 'tender-library'),       // Título de la sección
		'__return_false',                // Callback (no necesitamos descripción)
		'permalink'                      // Página donde se muestra
	);

	add_settings_field(
		'tal_users_page',
		__('Users management page', 'tender-library'),
		'tal_users_list_page_callback',
		'permalink',
		'tal_profile_settings'
	);

	add_settings_field(
		'tal_profile_page',              // ID del campo
		__('Profile page', 'tender-library'),              // Etiqueta
		'tal_profile_page_callback',     // Función de renderizado
		'permalink',                     // Página donde se muestra
		'tal_profile_settings'           // ID de la sección
	);

	add_settings_field(
		'tal_edit_profile_page',
		__('Profile edit page', 'tender-library'),
		'tal_edit_profile_page_callback',
		'permalink',
		'tal_profile_settings'
	);

	register_setting('permalink', 'tender_book_slug', 'sanitize_text_field');
	register_setting('permalink', 'tal_library_search_page', 'absint');
	register_setting('permalink', 'tal_profile_page', 'absint');
	register_setting('permalink', 'tal_edit_profile_page', 'absint');
	register_setting('permalink', 'tal_users_list_page', 'absint');

}
add_action('admin_init', 'tender_add_permalink_settings');

function tal_migrate_legacy_library_page_option()
{
	$legacy_page_id = absint(get_option('tal_library_page_id'));
	$current_page_id = absint(get_option('tal_library_search_page'));

	if (!$current_page_id && $legacy_page_id) {
		update_option('tal_library_search_page', $legacy_page_id);
	}
}
add_action('init', 'tal_migrate_legacy_library_page_option');

add_filter('display_post_states', 'tal_add_special_pages_state', 10, 2);
function tal_add_special_pages_state($post_states, $post) {
    $states = [
        'tal_library_search_page' => __('Searching page', 'tender-library'),
        'tal_profile_page'        => __('Profile page', 'tender-library'),
        'tal_edit_profile_page'   => __('Profile edit page', 'tender-library'),
        'tal_users_list_page'     => __('Users management page', 'tender-library'),
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
	$fields = [
		'permalink_structure' => 'sanitize_text_field',
		'tender_book_slug' => 'sanitize_text_field',
		'tal_library_search_page' => 'absint',
		'tal_profile_page' => 'absint',
		'tal_edit_profile_page' => 'absint',
		'tal_users_list_page' => 'absint',
	];
	$isAnyFieldSet = false;

	// Verificar si se ha establecido algún campo
	foreach ($fields as $field => $sanitizer) {
		if (isset($_POST[$field])) {
			if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-permalink')) {
				return;
			}
			$isAnyFieldSet = true;
			$value = wp_unslash($_POST[$field]);
			$clean = is_callable($sanitizer) ? call_user_func($sanitizer, $value) : sanitize_text_field($value);
			update_option($field, $clean);
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
		'show_option_none' => __('Select page', 'tender-library'),
	]);
}
function tal_profile_page_callback()
{
	$selected = get_option('tal_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_profile_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-library'),
	]);
}

function tal_edit_profile_page_callback()
{
	$selected = get_option('tal_edit_profile_page');
	wp_dropdown_pages([
		'name' => 'tal_edit_profile_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-library'),
	]);
}
function tal_users_list_page_callback()
{
	$selected = get_option('tal_users_list_page');
	wp_dropdown_pages([
		'name' => 'tal_users_list_page',
		'selected' => $selected,
		'show_option_none' => __('Select page', 'tender-library'),
	]);
}



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
