<?php

if (!defined('ABSPATH')) {
	exit; // Evitar acceso directo
}

global $wpdb;

function tender_create_lending($book_id, $user_id, $stimated_return_date, $old_laravel_id = null)
{
	global $wpdb;

	if(tal_has_active_reservation($book_id)){
		$res_return = tal_finish_reservation_proccess($book_id);
		if($res_return === false){
			return new WP_Error('reservation_error', __('Error updating reservation', 'tender-a-library'));
		}
	}
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


function tender_register_renewal($lending_id, $new_return_date = null)
{
	global $wpdb;

	// Si no se ha proporcionado una nueva fecha de retorno, calculamos 21 días a partir de hoy
	if (empty($new_return_date)) {
		$new_return_date = date('Y-m-d', strtotime('+21 days'));  // Calcula la fecha 21 días después de hoy
	}

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
			'renewal_date' => $new_return_date
		],
		['%d', '%d', '%s']
	);

	// Actualizar fecha estimada de devolución en `tender_lendings`
	$wpdb->update(
		TENDER_TABLE_LENDINGS,
		[
			'stimated_return_date' => $new_return_date,
			'extensions' => $extensions + 1,
		],
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

	$book_id = $wpdb->get_var($wpdb->prepare(
		"SELECT book_id FROM " . TENDER_TABLE_LENDINGS . " WHERE id = %d", $lending_id
	));

	if(tal_has_active_reservation($book_id)){
		error_log('Marking reservation as available for book ID: ' . $book_id);
		$res_update = tal_mark_reservation_as_available($book_id);
		if($res_update === false){
			return new WP_Error('reservation_error', __('Error updating reservation status', 'tender-a-library'));
		}
	}

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
		"SELECT * FROM " . TENDER_TABLE_RENEWALS . " WHERE lending_id = %d ORDER BY renewal_date ASC",
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
 * Obtener los préstamos activos de un usuario
 */
function tender_get_active_lendings_by_user($user_id)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . TENDER_TABLE_LENDINGS . " WHERE user_id = %d AND returned = 0",
		$user_id
	));
}

/**
 * Obtener los préstamos activos de un libro
 */
function tender_get_active_lendings_by_book($book_id)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . TENDER_TABLE_LENDINGS . " WHERE book_id = %d AND returned = 0",
		$book_id
	));
}

/**
 * Comprobar si un usuario tiene un libro concreto prestado ahora mismo
 */
function tender_user_has_borrowed_book($user_id, $book_id)
{
	global $wpdb;

	$result = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM " . TENDER_TABLE_LENDINGS . " 
		 WHERE user_id = %d AND book_id = %d AND returned = 0",
		$user_id,
		$book_id
	));

	return $result > 0;
}

/**
 * Obtener la fecha estimada de devolución de un préstamo
 */
function tender_get_stimated_return_date_by_book_id($book_id)
{
	global $wpdb;

	return $wpdb->get_row($wpdb->prepare(
		"SELECT stimated_return_date FROM " . TENDER_TABLE_LENDINGS . " WHERE book_id = %d AND returned = 0 ORDER BY lending_date DESC LIMIT 1",
		$book_id
	));
}

/**
 * Obtener los préstamos pasados de un usuario
 */
function tender_get_past_lendings_by_user($user_id)
{
	global $wpdb;

	return $wpdb->get_results($wpdb->prepare(
		"SELECT * FROM " . TENDER_TABLE_LENDINGS . " WHERE user_id = %d AND returned = 1",
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

	$active_lendings_count = count(tender_get_active_lendings_by_user($user_id));

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

function tender_get_current_lending_users_for_book($book_id)
{
	global $wpdb;

	$users_id = $wpdb->get_var($wpdb->prepare(
		"SELECT user_id FROM " . TENDER_TABLE_LENDINGS . " WHERE book_id = %d AND returned = 0",
		$book_id
	));

	return $users_id; 

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

	// 1. Buscar usuarios por nombre, email, etc. (como ya hacías)
	$users_by_name = get_users([
		'search'         => "*{$query}*",
		'search_columns' => ['user_login', 'user_email', 'display_name'],
		'number'        => 10,
	]);

	// 2. Buscar usuarios por teléfono (meta_key 'phone_number')
	$users_by_phone = get_users([
		'meta_query' => [
			[
				'key'     => 'phone_number', // Asegúrate de que este es el meta_key correcto
				'value'   => $query,
				'compare' => 'LIKE',
			],
		],
		'number' => 10,
	]);

	// Combinar resultados y eliminar duplicados
	$users = array_merge($users_by_name, $users_by_phone);
	$users = array_unique($users, SORT_REGULAR);

	if (empty($users)) {
		wp_send_json_error(['message' => 'No se encontraron usuarios']);
	}

	$options = [];
	foreach ($users as $user) {
		$user_link = get_user_profile_url_by_id($user->ID);
		array_push($options, [
			'ID' => $user->ID,
			'display_name' => $user->display_name,
			'user_email' => $user->user_email,
			'user_link' => $user_link ? $user_link['profile'] : '',
		]);
	}

	wp_send_json_success($options);
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

	if(tal_has_active_reservation($book_id)){
		$borrower = tal_get_active_reservation_on_book($book_id);
		if($borrower->user_id != $user_id){
			wp_send_json_error(['message' => __('This book has an active reservation by some other user.', 'tender-a-library')]);
		}
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

function tender_handle_lending_action()
{
	global $wpdb;

	// Validar nonce (si lo usas) y permisos aquí (muy recomendable)

	$lending_id = isset($_POST['lending_id']) ? intval($_POST['lending_id']) : 0;
	$action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
	$result = false;
	$message = '';
	if ($lending_id && in_array($action_type, ['return', 'renew'])) {
		if ($action_type == 'return') {
			if (tender_mark_as_returned($lending_id)) {
				$message = __('Loan successfully returned.', 'tender-a-library');
				$result = true;
			} else {
				$message = __('Could not return the loan.', 'tender-a-library');
			}
		} elseif ($action_type == 'renew') {
			if (tender_register_renewal($lending_id)) {
				// Mejor consulta la nueva fecha desde la DB
				$new_date = $wpdb->get_var($wpdb->prepare(
					"SELECT stimated_return_date FROM {$wpdb->prefix}tender_lendings WHERE id = %d",
					$lending_id
				));
				$message = sprintf(
					__('Loan renewed successfully. New return date: %s', 'tender-a-library'),
					$new_date ? date_i18n('d/m/Y', strtotime($new_date)) : '-'
				);
				$result = true;
			} else {
				$message = __('Could not renew the loan.', 'tender-a-library');
			}
		}
		if ($result) {
			wp_send_json_success(['message' => $message]);
		} else {
			wp_send_json_error(['message' => $message]);
		}
	} else {
		wp_send_json_error(['message' => __('Action could not be performed.', 'tender-a-library')]);
	}

	wp_die();
}

add_action('wp_ajax_tender_handle_lending_action', 'tender_handle_lending_action');
add_action("wp_ajax_nopriv_tender_handle_lending_action", "tender_handle_lending_action");
