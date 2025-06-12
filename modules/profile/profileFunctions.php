<?php
// Evitar acceso directo

use ParagonIE\Sodium\Core\Curve25519\Fe;

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


function tal_register_profile_routes()
{
	tal_rewrite_profile_route('tal_profile_page'); // Registra la ruta para la página de perfil
	tal_rewrite_profile_route('tal_edit_profile_page'); // Registra la ruta para la página de edición de perfil
}
add_action('init', 'tal_register_profile_routes', 20);

function tal_rewrite_profile_route($option)
{
	$page_id = get_option($option, $option); // Asigna un valor predeterminado si no está configurado
	$page = get_post($page_id);
	if($page){
		$slug = $page->post_name;
		add_rewrite_rule('^' . $slug . '/([^/]+)/?$', 'index.php?page_id=' . $page_id . '&tal_profile_user=$matches[1]', 'top');
	}
}

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
		$profile_url = false;
		$edit_profile_url = false;

		$user_slug = $user->user_nicename;
		$profile_page_id = get_option('tal_profile_page');
		$edit_profile_page_id = get_option('tal_edit_profile_page');

		if ($profile_page_id) {
			$profile_page_url = get_permalink($profile_page_id);
			$profile_url = trailingslashit($profile_page_url) . $user_slug;
		}
		if ($edit_profile_page_id) {
			$edit_profile_page_url = get_permalink($edit_profile_page_id);
			$edit_profile_url = trailingslashit($edit_profile_page_url) . $user_slug;
		}

		return array(
			'profile' => $profile_url,
			'edit' => $edit_profile_url
		);
	}

	return false;
}
