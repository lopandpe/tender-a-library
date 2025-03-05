<?php

if (!defined('ABSPATH')) {
	exit; // Evitar acceso directo
}

global $wpdb;

function tender_create_lending($book_id, $user_id, $stimated_return_date, $old_laravel_id = null)
{
	global $wpdb;

	$wpdb->insert(
		TENDER_TABLE_LENDINGS,
		[
			'book_id' => $book_id,
			'user_id' => $user_id,
			'lending_date' => current_time('mysql'),
			'stimated_return_date' => $stimated_return_date,
			'returned' => 0,
			'old_laravel_id' => $old_laravel_id
		],
		['%d', '%d', '%s', '%s', '%d', '%d']
	);

	return $wpdb->insert_id;
}


function tender_register_renewal($lending_id, $new_return_date)
{
	global $wpdb;

	// Obtener el número de extensiones previas
	$extensions = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM " . TENDER_TABLE_RENEWALS . " WHERE lending_id = %d",
		$lending_id
	));

	// Insertar renovación
	$wpdb->insert(
		TENDER_TABLE_RENEWALS,
		[
			'lending_id' => $lending_id,
			'extensions' => $extensions + 1,
			'extension_date' => $new_return_date
		],
		['%d', '%d', '%s']
	);

	// Actualizar fecha estimada de devolución en `tender_lendings`
	$wpdb->update(
		TENDER_TABLE_LENDINGS,
		['stimated_return_date' => $new_return_date],
		['id' => $lending_id],
		['%s'],
		['%d']
	);

	return $wpdb->insert_id;
}

/**
 * Marcar préstamo como devuelto
 */
function tender_mark_as_returned($lending_id)
{
	global $wpdb;

	return $wpdb->update(
		TENDER_TABLE_LENDINGS,
		['real_return_date' => current_time('mysql'), 'returned' => 1],
		['id' => $lending_id],
		['%s', '%d'],
		['%d']
	);
}

/**
 * Obtener historial de renovaciones de un préstamo
 */
function tender_get_renewals_by_lending($lending_id)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . TENDER_TABLE_RENEWALS . " WHERE lending_id = %d ORDER BY extension_date ASC",
		$lending_id
	));
}

/**
 * Obtener todos los préstamos de un usuario
 */
function tender_get_lendings_by_user($user_id)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . TENDER_TABLE_LENDINGS . " WHERE user_id = %d ORDER BY lending_date DESC",
		$user_id
	));
}

/**
 * Verifica si un usuario puede solicitar más préstamos
 *
 * @param int $user_id ID del usuario
 * @return bool Devuelve true si puede pedir más libros, false si ha alcanzado el límite
 */
function tender_can_user_borrow_more($user_id)
{
	global $wpdb;

	$active_lendings_count = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM " . TENDER_TABLE_LENDINGS . " WHERE user_id = %d AND returned = 0",
		$user_id
	));

	return $active_lendings_count < 2; // Puede pedir más si tiene menos de 2 préstamos activos
}

/**
 * Verifica si un libro puede ser prestado (si hay copias disponibles)
 *
 * @param int $book_id ID del libro
 * @return bool Devuelve true si hay copias disponibles, false si no
 */
function tender_can_book_be_lent($book_id)
{
	global $wpdb;

	$total_units = carbon_get_post_meta($book_id, 'tender_book_units');
	$active_book_lendings = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM " . TENDER_TABLE_LENDINGS . " WHERE book_id = %d AND returned = 0",
		$book_id
	));

	return $total_units > $active_book_lendings; // Puede prestarse si hay más copias disponibles
}


// Hooks para las llamadas AJAX
add_action('wp_ajax_tender_search_books', 'tender_search_books');
add_action('wp_ajax_tender_search_users', 'tender_search_users');
add_action('wp_ajax_tender_create_lending_ajax', 'tender_create_lending_ajax'); // Nuevo nombre para evitar conflictos



function tender_search_books()
{
	global $wpdb;

	if (!isset($_POST['query'])) {
		wp_send_json_error(['message' => 'Falta el parámetro de búsqueda']);
	}

	$query = sanitize_text_field($_POST['query']);

	// Buscar libros en el custom post type 'tender_book'
	$books = get_posts([
		'post_type' => 'tender_book',
		'posts_per_page' => 10,
		's' => $query,
	]);

	if (empty($books)) {
		wp_send_json_error(['message' => 'No se encontraron libros']);
	}

	$options = '';
	foreach ($books as $book) {
		$author = carbon_get_post_meta($book->ID, 'tender_book_author');
		$year = carbon_get_post_meta($book->ID, 'tender_book_year');
		$year = $year ? "[$year]" : '';
		$options .= "<option value='{$book->ID}'>{$book->post_title} ({$author} {$year})</option>";
	}

	echo $options;
	wp_die();
}


function tender_search_users()
{
	global $wpdb;

	if (!isset($_POST['query'])) {
		wp_send_json_error(['message' => 'Falta el parámetro de búsqueda']);
	}

	$query = sanitize_text_field($_POST['query']);

	// Buscar usuarios por nombre, apellidos o email
	$users = get_users([
		'search' => "*{$query}*",
		'search_columns' => ['user_login', 'user_email', 'display_name'],
		'number' => 10,
	]);

	if (empty($users)) {
		wp_send_json_error(['message' => 'No se encontraron usuarios']);
	}

	$options = '';
	foreach ($users as $user) {
		$options .= "<option value='{$user->ID}'>{$user->display_name} ({$user->user_email})</option>";
	}

	echo $options;
	wp_die();
}
function tender_create_lending_ajax()
{
	// Verificar que los datos necesarios están presentes
	if (!isset($_POST['book_id'], $_POST['user_id'])) {
		wp_send_json_error(['message' => 'Faltan datos obligatorios']);
	}

	$book_id = intval($_POST['book_id']);
	$user_id = intval($_POST['user_id']);

	// Validar si el usuario puede pedir más libros
	if (!tender_can_user_borrow_more($user_id)) {
		wp_send_json_error(['message' => 'El usuario ya tiene 2 préstamos activos y no puede solicitar más.']);
	}

	// Validar si el libro tiene copias disponibles
	if (!tender_can_book_be_lent($book_id)) {
		wp_send_json_error(['message' => 'No hay copias disponibles de este libro para préstamo.']);
	}

	// Registrar el préstamo si las validaciones pasan
	$lending_date = current_time('mysql');
	$stimated_return_date = date('Y-m-d', strtotime($lending_date . ' +21 days'));


	// No pasamos 'old_laravel_id' ya que en AJAX no es necesario
	$lending_id = tender_create_lending($book_id, $user_id, $stimated_return_date);

	if ($lending_id) {
		wp_send_json_success([
			'message' => 'Préstamo registrado correctamente. La fecha de devolución es el ' . date('d-m-Y', strtotime($lending_date . ' +21 days')),
			'lending_id' => $lending_id,
			'stimated_return_date' => $stimated_return_date
		]);
	} else {
		wp_send_json_error(['message' => 'Error al registrar el préstamo']);
	}
}
