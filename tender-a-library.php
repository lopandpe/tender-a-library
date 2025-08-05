<?php

use Carbon_Fields\Carbon_Fields;

/**
 * Plugin Name:     Plugin Biblioteca (A)
 * Description:     Añade la funcionalidad de biblioteca al core de wordpress, incluyendo libros, lectores, préstamos, etc
 * Author:          Local Anarquista Magdalena
 * Author URI:      https://localanarquistamagdalena.org
 * Text Domain:     tender-a-library
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Tender_A_Library
 */

require_once __DIR__ . '/modules/db/installDBFunctions.php';
// require_once __DIR__ . '/modules/tender-book/tenderBookTemplate.php'; // ✅ AÑADIR AQUÍ

register_activation_hook(__FILE__, 'tender_create_database_tables');



function tender_bootstrap()
{
	if (file_exists(__DIR__ . '/bootstrap/TenderBootstrap.php')) {
		require_once __DIR__ . '/bootstrap/TenderBootstrap.php';
		require_once __DIR__ . '/vendor/autoload.php';
		require_once 'carbon-fields/vendor/autoload.php';
		Carbon_Fields::boot();

		$modules = [
			"modules/tender-blocks",
			"modules/localization",
			"modules/customPostBook",
			"modules/bookFunctions",
			"modules/tenderRoles",
			"modules/bookCapabilities",
			"modules/openerUserFunctions",
			"modules/tender-book/tenderBookFields",
			"modules/tender-book/tenderBookTaxonomiesFields",
			"modules/tender-book/tenderBookTemplate",
			"modules/tenderStyles",
			"modules/permalinks",
			"modules/restrictDashboard",
			"modules/lendings/lendingFunctions",
			"modules/lendings/tenderMenu",
			"modules/lendings/tenderMenuCover",
			"modules/lendings/tenderMenuNewLending",
			"modules/lendings/lendingPages",
			"modules/profile/profilePage",
			"modules/profile/editProfilePage",
			"modules/profile/profileFunctions",
			"modules/search/api-search",
			"modules/search/api-filters",
			"modules/search/searchPage",
            "modules/search/filteredURLs",
		];

		$bootstrapApp = new TenderBootstrap($modules);
		$bootstrapApp->start();
	}
	
}
add_action('after_setup_theme', 'tender_bootstrap');

register_activation_hook(__FILE__, 'tal_create_plugin_pages_on_activation');

function tal_create_plugin_pages_on_activation() {
    $lang = get_locale();
    $is_spanish = (strpos($lang, 'es_') === 0);

    // Array centralizado de páginas a crear
    $pages = [
        [
            'slug'        => $is_spanish ? 'perfil' : 'profile',
            'title'       => $is_spanish ? __('Perfil', 'tender-a-library') : __('Profile', 'tender-a-library'),
            'option_name' => 'tal_profile_page',
            'content'     => '',
        ],
        [
            'slug'        => $is_spanish ? 'editar-perfil' : 'edit-profile',
            'title'       => $is_spanish ? __('Editar perfil', 'tender-a-library') : __('Edit Profile', 'tender-a-library'),
            'option_name' => 'tal_edit_profile_page',
            'content'     => '',
        ],
        [
            'slug'        => $is_spanish ? 'biblioteca' : 'library',
            'title'       => $is_spanish ? __('Biblioteca', 'tender-a-library') : __('Library', 'tender-a-library'),
            'option_name' => 'tal_library_page_id',
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
