<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}


function tal_create_plugin_pages_on_activation() {
    $lang = get_locale();
    $is_spanish = (strpos($lang, 'es_') === 0);

    // Array centralizado de páginas a crear
    $pages = [
        [
            'slug'        => $is_spanish ? 'perfil' : 'profile',
            'title'       => $is_spanish ? __('Perfil', 'tender-library') : __('Profile', 'tender-library'),
            'option_name' => 'tal_profile_page',
            'content'     => '',
        ],
        [
            'slug'        => $is_spanish ? 'editar-perfil' : 'edit-profile',
            'title'       => __('Edit Profile', 'tender-library'),
            'option_name' => 'tal_edit_profile_page',
            'content'     => '',
        ],
        [
            'slug'        => $is_spanish ? 'usuarios' : 'users',
            'title'       => __('Users list', 'tender-library'),
            'option_name' => 'tal_users_list_page',
            'content'     => '',
        ],
        [
            'slug'        => $is_spanish ? 'biblioteca' : 'library',
            'title'       => __('Library', 'tender-library'),
            'option_name' => 'tal_library_search_page',
            // Usa tu shortcode o bloque real aquí
            'content'     => '<!-- wp:tender-a-library/book-search /-->', 
        ],
    ];

    foreach ($pages as $page) {
        tal_create_plugin_page(
            $page['slug'],
            $page['title'],
            $page['option_name'],
            $page['content']
        );
    }

    flush_rewrite_rules();
}

/**
 * Crea una página solo si no hay ninguna asociada (o si la asociada ya no existe), y guarda su ID en una opción.
 */
function tal_create_plugin_page($slug, $title, $option_name, $content = '') {
    $page_id = get_option($option_name);

    // Si ya hay una página asociada y existe, no hacer nada.
    if ($page_id && get_post_status($page_id) === 'publish') {
        return;
    }

    // Si la opción está vacía o la página fue borrada, crea una nueva.
    $author_id = 1; // admin por defecto

    $new_page_id = wp_insert_post([
        'post_title'    => $title,
        'post_name'     => $slug,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_author'   => $author_id,
    ]);

    if ($new_page_id && !is_wp_error($new_page_id)) {
        update_option($option_name, $new_page_id);
    }
}
