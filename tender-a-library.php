<?php

use Carbon_Fields\Carbon_Fields;

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
		];

		$bootstrapApp = new TenderBootstrap($modules);
		$bootstrapApp->start();
	}
	
}
add_action('after_setup_theme', 'tender_bootstrap');
