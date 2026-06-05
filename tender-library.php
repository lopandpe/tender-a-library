<?php

/**
 * Plugin Name: Tender Library
 * Description: Private library/tender management plugin.
 * Version: 1.0.2
 * Author: Luis Gómez
 * Text Domain: tender-library
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Update URI: https://example.com/tender-library
 *
 * @package Tender_Library
 */


define('TENDER_LIBRARY_VERSION', '1.0.2');
define('TENDER_LIBRARY_UPDATE_URI', 'https://example.com/tender-library');
define('TENDER_LIBRARY_UPDATE_METADATA_URL', 'https://example.com/tender-library/update.json');
define('TENDER_LIBRARY_PLUGIN_FILE', __FILE__);

require_once __DIR__ . '/modules/db/installDBFunctions.php';
require_once __DIR__ . '/modules/createPagesOnActivation.php';
require_once __DIR__ . '/modules/emails/notReturnedEmails.php';
require_once __DIR__ . '/includes/class-tender-library-updater.php';


function tender_library_run_version_migrations()
{
	$installed_version = get_option('tender_library_version', '0.0.0');

	if (version_compare($installed_version, TENDER_LIBRARY_VERSION, '<')) {
		update_option('tender_library_version', TENDER_LIBRARY_VERSION);
	}
}

function tender_library_register_private_updater()
{
	$metadata_url = apply_filters(
		'tender_library_update_metadata_url',
		TENDER_LIBRARY_UPDATE_METADATA_URL
	);

	$updater = new Tender_Library_Updater(
		TENDER_LIBRARY_PLUGIN_FILE,
		TENDER_LIBRARY_VERSION,
		$metadata_url
	);
	$updater->register();
}
add_action('plugins_loaded', 'tender_library_register_private_updater');
add_action('admin_init', 'tender_library_run_version_migrations');

function tender_activate()
{
	require_once __DIR__ . '/modules/customPostBook.php';
	tender_book(); // Register the custom post type and taxonomies
	tender_create_database_tables();
	tender_library_run_version_migrations();
	flush_rewrite_rules(); // Flush rewrite rules
}

function tender_deactivate()
{
	wp_clear_scheduled_hook('tal_send_not_returned_emails');
	flush_rewrite_rules(); // Flush rewrite rules
}

register_activation_hook(__FILE__, 'tal_create_plugin_pages_on_activation');
register_activation_hook(__FILE__, 'tender_activate');
register_deactivation_hook(__FILE__, 'tender_deactivate');

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
	echo esc_html__('Tender A Library: Carbon Fields could not be loaded. Run Composer install for this plugin or activate Carbon Fields.', 'tender-library');
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
			"modules/migration/tenderMigration",
			"modules/reservations/reservationFunctions",
			"modules/profile/profilePage",
			"modules/profile/editProfilePage",
			"modules/profile/profileFunctions",
			"modules/profile/usersListPage",
			"modules/profile/profilePermissions",
			"modules/profile/userCallLogs",
			"modules/search/api-search",
			"modules/search/api-filters",
			"modules/search/searchPage",
            "modules/search/filteredURLs",
            "modules/emails/notReturnedEmails",
			"modules/emails/lendingHasReservation",
			"modules/emails/reservationIsAvailableNow",
			"modules/tender-event/eventFeeds",
			"modules/tender-event/customPostEvent",
			"modules/tender-event/tenderEventFields",
			"modules/tender-event/tenderEventTemplate",
		];

		$bootstrapApp = new TenderBootstrap($modules);
		$bootstrapApp->start();
	}
	
}
add_action('after_setup_theme', 'tender_bootstrap', 20);
