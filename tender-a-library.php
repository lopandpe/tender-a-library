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
			"blocks/tender-blocks",
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
		];

		$bootstrapApp = new TenderBootstrap($modules);
		$bootstrapApp->start();
	}
}
add_action('after_setup_theme', 'tender_bootstrap');





register_activation_hook(__FILE__, 'tal_create_profile_pages_on_activation');

function tal_create_profile_pages_on_activation()
{
    tal_create_profile_page('profile', 'Profile', 'tal_profile_page');
    tal_create_profile_page('edit-profile', 'Edit Profile', 'tal_edit_profile_page');

    // Asegura que las reglas de reescritura se actualicen
    flush_rewrite_rules();
}

function tal_create_profile_page($slug, $title, $option_name)
{
    // Comprobar si ya existe una página con el slug
    $existing_page = get_page_by_path($slug);

    if (!$existing_page) {
        // Crear la página
        $page_id = wp_insert_post(array(
            'post_title'    => $title,
            'post_name'     => $slug,
            'post_content'  => '[profile_placeholder]', // Opcional: Shortcode o contenido
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id()
        ));

        // Guardar la opción con el ID de la nueva página
        if ($page_id && !is_wp_error($page_id)) {
            update_option($option_name, $page_id);
        }
    } else {
        // Si la página ya existe, guardar su ID por si no estaba registrado
        update_option($option_name, $existing_page->ID);
    }
}