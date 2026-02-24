<?php

/**
 * Plugin Name:     Plugin Biblioteca (A)
 * Description:     Adds library functionality to the core of WordPress, including books, readers, loans, etc.
 * Author:          Local Anarquista Magdalena
 * Author URI:      https://localanarquistamagdalena.org
 * Text Domain:     tender-a-library
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Tender_A_Library
 */

require_once __DIR__ . '/modules/db/installDBFunctions.php';
require_once __DIR__ . '/modules/createPagesOnActivation.php';
require_once __DIR__ . '/modules/emails/notReturnedEmails.php';

register_activation_hook(__FILE__, 'tender_create_database_tables');
register_activation_hook(__FILE__, 'tal_create_plugin_pages_on_activation');

/**
 * Boot Carbon Fields from an existing load or bundled Composer vendor.
 *
 * @return bool
 */
function tal_boot_carbon_fields()
{
	if (class_exists('\Carbon_Fields\Carbon_Fields')) {
		\Carbon_Fields\Carbon_Fields::boot();
		return true;
	}

	$autoload_candidates = array(
		__DIR__ . '/vendor/autoload.php',
	);

	foreach ($autoload_candidates as $autoload_path) {
		if (!file_exists($autoload_path)) {
			continue;
		}

		require_once $autoload_path;

		if (class_exists('\Carbon_Fields\Carbon_Fields')) {
			\Carbon_Fields\Carbon_Fields::boot();
			return true;
		}
	}

	return false;
}

function tal_carbon_fields_missing_notice()
{
	if (!current_user_can('activate_plugins')) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__('Tender A Library: Carbon Fields could not be loaded. Run Composer install for this plugin or activate Carbon Fields.', 'tender-a-library');
	echo '</p></div>';
}


function tender_bootstrap()
{
	if (file_exists(__DIR__ . '/bootstrap/TenderBootstrap.php')) {
		require_once __DIR__ . '/bootstrap/TenderBootstrap.php';

		if (!tal_boot_carbon_fields()) {
			add_action('admin_notices', 'tal_carbon_fields_missing_notice');
			return;
		}

		$modules = [
			"modules/tender-blocks",
			"modules/localization",
			"modules/customPostBook",
			"modules/bookFunctions",
			"modules/tenderRoles",
			"modules/bookCapabilities",
			"modules/openerUserFunctions",
			"modules/tender-book/tenderBookFields",
			"modules/tender-book/tenderBookSignatureAutofill",
			"modules/tender-book/tenderBookTaxonomiesFields",
			"modules/tender-book/tenderBookTemplate",
			"modules/tenderStyles",
			"modules/permalinks",
			"modules/restrictDashboard",
			"modules/lendings/lendingFunctions",
			"modules/lendings/downloadCsvLendings",
			"modules/lendings/tenderMenu",
			"modules/lendings/tenderMenuCover",
			"modules/lendings/tenderMenuNewLending",
			"modules/lendings/lendingPages",
			"modules/reservations/reservationFunctions",
			"modules/profile/profilePage",
			"modules/profile/editProfilePage",
			"modules/profile/profileFunctions",
			"modules/profile/usersListPage",
			"modules/profile/profilePermissions",
			"modules/search/api-search",
			"modules/search/api-filters",
			"modules/search/searchPage",
            "modules/search/filteredURLs",
            "modules/emails/notReturnedEmails",
			"modules/emails/lendingHasReservation",
			"modules/emails/reservationIsAvailableNow",
			"modules/tender-event/customPostEvent",
			"modules/tender-event/tenderEventFields",
			"modules/tender-event/tenderEventTemplate",
		];

		$bootstrapApp = new TenderBootstrap($modules);
		$bootstrapApp->start();
	}
	
}
add_action('after_setup_theme', 'tender_bootstrap', 20);
