<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}
function tal_hide_author_page()
{
	if (is_author()) {
		global $wp_query;

		$author_id = get_queried_object_id();
		$author_user = get_userdata($author_id);

		// Verificar si el usuario tiene el rol "reader" o "opener"
		if (in_array('reader', $author_user->roles) || in_array('opener', $author_user->roles)) {
			$args = array(
				'author' => $author_id,
				'post_type' => 'any', // Buscar cualquier tipo de contenido
				'post_status' => 'publish',
				'posts_per_page' => 1, // Solo necesitamos comprobar si hay al menos un post
			);

			$author_posts = new WP_Query($args);

			if (!$author_posts->have_posts()) {
				wp_redirect(home_url());
				exit;
			}
		}
	}
}
add_action('template_redirect', 'tal_hide_author_page');

function tal_exclude_author_queries($query)
{
	if (!is_admin() && $query->is_main_query() && is_author()) {
		$author_id = get_queried_object_id();
		$author_user = get_userdata($author_id);

		// Verificar si el usuario tiene el rol "reader" o "opener"
		if (in_array('reader', $author_user->roles) || in_array('opener', $author_user->roles)) {
			$args = array(
				'author' => $author_id,
				'post_type' => 'any',
				'post_status' => 'publish',
				'posts_per_page' => 1,
			);

			$author_posts = new WP_Query($args);

			if (!$author_posts->have_posts()) {
				$query->set_404(); // Muestra error 404 si el usuario no tiene contenido
			}
		}
	}
}
add_action('pre_get_posts', 'tal_exclude_author_queries');


function tal_register_profile_route()
{
	// Obtener la URL base de la página de perfil desde la opción 'tal_profile_page'
	$profile_id = get_option('tal_profile_page', 'perfil'); // Asigna un valor predeterminado si no está configurado
	$profile_page = get_post($profile_id);
	$slug = $profile_page->post_name;
	// Registra la regla de reescritura personalizada para '/perfil/xxxxx' -> 'index.php?tender_profile_user=$matches[1]'
	add_rewrite_rule('^' . $slug . '/([^/]+)/?$', 'index.php?page_id=' . $profile_id . '&tal_profile_user=$matches[1]', 'top');
}
add_action('init', 'tal_register_profile_route', 20);



function tal_add_perfil_query_var($vars)
{
	$vars[] = 'tal_profile_user'; // Usamos 'tal_profile_user' como la query var
	return $vars;
}
add_filter('query_vars', 'tal_add_perfil_query_var');



function get_user_profile_url_by_id($user_id)
{
	// Obtener el usuario por su ID
	$user = get_user_by('id', $user_id);

	if ($user) {
		// Obtener el slug del usuario
		$user_slug = $user->user_nicename;

		// Obtener la ID de la página configurada como 'tal_profile_page'
		$profile_page_id = get_option('tal_profile_page');

		if ($profile_page_id) {
			// Obtener el permalink de la página de perfil
			$profile_page_url = get_permalink($profile_page_id);

			// Asegurarnos de que la URL tiene el formato adecuado
			$profile_url = trailingslashit($profile_page_url) . $user_slug;

			return $profile_url;
		}
	}

	return false; // Retornar false si no se encuentra el usuario o no se puede obtener la URL
}
