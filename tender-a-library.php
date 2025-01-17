<?php

use Carbon_Fields\Carbon_Fields;

/**
 * Plugin Name:     Plugin Biblioteca (A)
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Añade la funcionalidad de biblioteca al core de wordpress, incluyendo libros, lectores, préstamos, etc
 * Author:          Local Anarquista Magdalena
 * Author URI:      https://localanarquistamagdalena.org
 * Text Domain:     tender-a-library
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Tender_A_Library
 */

// Your code starts here.

function tender_bootstrap()
{

	/*
	*  START APP
	*  Sets initial state loading deps
	*/
	if (file_exists(__DIR__ . '/bootstrap/TenderBootstrap.php')) {
		require_once  __DIR__ . '/bootstrap/TenderBootstrap.php';
		require_once __DIR__ . '/vendor/autoload.php';
		Carbon_Fields::boot();

		$bootstrapApp = new TenderBootstrap(array(
			"modules/customPostBook",
			"modules/bookFunctions",
			"modules/tenderRoles",
			"modules/bookCapabilities",
			"modules/openerUserFunctions",
			"modules/tender-book/tenderBookFields",
		));

		$bootstrapApp->start();
	}
}
add_action('after_setup_theme', 'tender_bootstrap');
