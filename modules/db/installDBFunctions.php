<?php

if (!defined('ABSPATH')) {
	exit; // Evitar acceso directo
}

global $wpdb;
define('TENDER_TABLE_LENDINGS', $wpdb->prefix . 'tender_lendings');
define('TENDER_TABLE_RENEWALS', $wpdb->prefix . 'tender_renewals');
define('TENDER_TABLE_RESERVATIONS', $wpdb->prefix . 'tender_reservations');
define('TENDER_TABLE_USER_CALLS', $wpdb->prefix . 'tender_user_calls');
define('TENDER_TABLE_MIGRATION_JOBS', $wpdb->prefix . 'tal_migration_jobs');


/**
 * Crear tablas personalizadas al activar el plugin
 */
function tender_create_database_tables()
{
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	// Prefijo de tabla de WordPress
	$table_lendings = TENDER_TABLE_LENDINGS;
	$table_renewals = TENDER_TABLE_RENEWALS;
	$table_reservations = TENDER_TABLE_RESERVATIONS;
	$table_user_calls = TENDER_TABLE_USER_CALLS;
	$table_migration_jobs = TENDER_TABLE_MIGRATION_JOBS;

	// SQL para crear la tabla de préstamos
	$sql_lendings = "CREATE TABLE IF NOT EXISTS $table_lendings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        book_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        lending_date DATE NOT NULL,
        stimated_return_date DATE NOT NULL,
        real_return_date DATE NULL DEFAULT NULL,
        returned TINYINT(1) NOT NULL DEFAULT 0,
        extensions INT NOT NULL DEFAULT 0,
        extension_date DATE NULL DEFAULT NULL,
		old_laravel_id BIGINT UNSIGNED,
        FOREIGN KEY (book_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";

	// SQL para crear la tabla de renovaciones
	$sql_renewals = "CREATE TABLE IF NOT EXISTS $table_renewals (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lending_id BIGINT UNSIGNED NOT NULL,
        renewal_date DATE NOT NULL,
        FOREIGN KEY (lending_id) REFERENCES $table_lendings(id) ON DELETE CASCADE
    ) $charset_collate;";


	// SQL para crear la tabla de reservas
	$sql_reservations = "CREATE TABLE IF NOT EXISTS $table_reservations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        book_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        reservation_date DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        -- pending: created and waiting loan return
        -- available: book returned and waiting new loan
        -- fulfilled: reservation succesfully finished
        -- cancelled
        available_at DATE NULL,
        pickup_exclusive_until DATE NULL,
        notified_available_at DATE NULL,
        FOREIGN KEY (book_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
        KEY book_status (book_id, status),
        KEY user_idx (user_id),
        KEY pickup_until_idx (pickup_exclusive_until)
    ) $charset_collate;";

	// SQL para crear la tabla de llamadas a usuarios
	$sql_user_calls = "CREATE TABLE IF NOT EXISTS $table_user_calls (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        subject VARCHAR(255) NOT NULL,
        comment LONGTEXT NULL,
        call_date DATE NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        old_laravel_id BIGINT UNSIGNED NULL,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
        KEY user_call_date (user_id, call_date),
        KEY old_laravel_id (old_laravel_id)
    ) $charset_collate;";

	$sql_migration_jobs = "CREATE TABLE IF NOT EXISTS $table_migration_jobs (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		requested_step VARCHAR(20) NOT NULL,
		current_step VARCHAR(20) NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		dry_run TINYINT(1) NOT NULL DEFAULT 0,
		csv_dir TEXT NULL,
		media_base_path TEXT NULL,
		step_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		offset_in_step INT UNSIGNED NOT NULL DEFAULT 0,
		total_rows INT UNSIGNED NOT NULL DEFAULT 0,
		processed_rows INT UNSIGNED NOT NULL DEFAULT 0,
		created_count INT UNSIGNED NOT NULL DEFAULT 0,
		updated_count INT UNSIGNED NOT NULL DEFAULT 0,
		skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
		error_count INT UNSIGNED NOT NULL DEFAULT 0,
		steps LONGTEXT NULL,
		step_totals LONGTEXT NULL,
		source_paths LONGTEXT NULL,
		messages LONGTEXT NULL,
		errors LONGTEXT NULL,
		last_error TEXT NULL,
		created_at DATETIME NOT NULL,
		started_at DATETIME NULL,
		finished_at DATETIME NULL,
		last_activity_at DATETIME NULL,
		KEY status (status),
		KEY current_step (current_step)
	) $charset_collate;";


	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql_lendings);
	dbDelta($sql_renewals);
	dbDelta($sql_reservations);
	dbDelta($sql_user_calls);
	dbDelta($sql_migration_jobs);
}
