<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!defined('TENDER_TABLE_USER_CALLS')) {
	define('TENDER_TABLE_USER_CALLS', $GLOBALS['wpdb']->prefix . 'tender_user_calls');
}

/**
 * Openers and administrators can manage the call registry.
 */
function tal_current_user_can_manage_call_logs()
{
	return function_exists('tal_current_user_opener_or_admin') && tal_current_user_opener_or_admin();
}

/**
 * Garantiza que la tabla exista también en instalaciones ya activadas.
 */
function tal_ensure_user_calls_table_exists()
{
	static $checked = false;
	if ($checked) {
		return;
	}

	global $wpdb;
	$table_name = TENDER_TABLE_USER_CALLS;
	$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));

	if ($exists !== $table_name && function_exists('tender_create_database_tables')) {
		tender_create_database_tables();
	}

	$checked = true;
}

/**
 * Guarda una nueva llamada de seguimiento para un usuario.
 *
 * @param int    $user_id   ID de usuario.
 * @param string $subject   Asunto de la llamada.
 * @param string $comment   Comentario de la llamada.
 * @param string $call_date Fecha de la llamada (Y-m-d).
 * @return int|WP_Error
 */
function tal_create_user_call_log($user_id, $subject, $comment, $call_date)
{
	tal_ensure_user_calls_table_exists();

	if ($user_id <= 0 || !get_user_by('id', $user_id)) {
		return new WP_Error('invalid_user', __('Invalid user.', 'tender-a-library'));
	}

	$call_date = sanitize_text_field($call_date);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $call_date)) {
		return new WP_Error('invalid_date', __('Invalid date format.', 'tender-a-library'));
	}

	$subject = sanitize_text_field($subject);
	$comment = wp_kses_post($comment);

	if ($subject === '') {
		return new WP_Error('missing_subject', __('Title is required.', 'tender-a-library'));
	}

	global $wpdb;
	$now = current_time('mysql');

	$inserted = $wpdb->insert(
		TENDER_TABLE_USER_CALLS,
		array(
			'user_id'    => (int) $user_id,
			'subject'    => $subject,
			'comment'    => $comment,
			'call_date'  => $call_date,
			'created_at' => $now,
			'updated_at' => $now,
		),
		array('%d', '%s', '%s', '%s', '%s', '%s')
	);

	if ($inserted === false) {
		return new WP_Error('db_error', __('Could not save call log.', 'tender-a-library'));
	}

	return (int) $wpdb->insert_id;
}

/**
 * Obtiene una llamada concreta de un usuario.
 *
 * @param int $call_id ID de llamada.
 * @param int $user_id ID de usuario propietario.
 * @return object|null
 */
function tal_get_user_call_log($call_id, $user_id)
{
	tal_ensure_user_calls_table_exists();

	global $wpdb;
	return $wpdb->get_row(
		$wpdb->prepare(
			'SELECT id, user_id, subject, comment, call_date, created_at, updated_at
             FROM ' . TENDER_TABLE_USER_CALLS . '
             WHERE id = %d AND user_id = %d
             LIMIT 1',
			(int) $call_id,
			(int) $user_id
		)
	);
}

/**
 * Actualiza una llamada existente.
 *
 * @param int    $call_id   ID de llamada.
 * @param int    $user_id   ID de usuario propietario.
 * @param string $subject   Asunto.
 * @param string $comment   Comentario.
 * @param string $call_date Fecha (Y-m-d).
 * @return true|WP_Error
 */
function tal_update_user_call_log($call_id, $user_id, $subject, $comment, $call_date)
{
	tal_ensure_user_calls_table_exists();

	$existing = tal_get_user_call_log($call_id, $user_id);
	if (!$existing) {
		return new WP_Error('not_found', __('Call log entry not found.', 'tender-a-library'));
	}

	$call_date = sanitize_text_field($call_date);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $call_date)) {
		return new WP_Error('invalid_date', __('Invalid date format.', 'tender-a-library'));
	}

	$subject = sanitize_text_field($subject);
	$comment = wp_kses_post($comment);

	if ($subject === '') {
		return new WP_Error('missing_subject', __('Title is required.', 'tender-a-library'));
	}

	global $wpdb;
	$updated = $wpdb->update(
		TENDER_TABLE_USER_CALLS,
		array(
			'subject'    => $subject,
			'comment'    => $comment,
			'call_date'  => $call_date,
			'updated_at' => current_time('mysql'),
		),
		array(
			'id'      => (int) $call_id,
			'user_id' => (int) $user_id,
		),
		array('%s', '%s', '%s', '%s'),
		array('%d', '%d')
	);

	if ($updated === false) {
		return new WP_Error('db_error', __('Could not update call log.', 'tender-a-library'));
	}

	return true;
}

/**
 * Elimina una llamada.
 *
 * @param int $call_id ID de llamada.
 * @param int $user_id ID de usuario propietario.
 * @return true|WP_Error
 */
function tal_delete_user_call_log($call_id, $user_id)
{
	tal_ensure_user_calls_table_exists();

	$existing = tal_get_user_call_log($call_id, $user_id);
	if (!$existing) {
		return new WP_Error('not_found', __('Call log entry not found.', 'tender-a-library'));
	}

	global $wpdb;
	$deleted = $wpdb->delete(
		TENDER_TABLE_USER_CALLS,
		array(
			'id'      => (int) $call_id,
			'user_id' => (int) $user_id,
		),
		array('%d', '%d')
	);

	if ($deleted === false) {
		return new WP_Error('db_error', __('Could not delete call log.', 'tender-a-library'));
	}

	return true;
}

/**
 * Obtiene el histórico de llamadas de un usuario.
 *
 * @param int $user_id ID de usuario.
 * @param int $limit   Máximo de filas.
 * @return array
 */
function tal_get_user_call_logs($user_id, $limit = 100)
{
	tal_ensure_user_calls_table_exists();

	global $wpdb;
	$limit = max(1, min(500, (int) $limit));

	return $wpdb->get_results(
		$wpdb->prepare(
			'SELECT id, user_id, subject, comment, call_date, created_at, updated_at
             FROM ' . TENDER_TABLE_USER_CALLS . '
             WHERE user_id = %d
             ORDER BY call_date DESC, id DESC
             LIMIT %d',
			(int) $user_id,
			$limit
		)
	);
}
