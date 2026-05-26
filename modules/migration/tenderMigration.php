<?php

if (!defined('ABSPATH')) {
	exit;
}

define('TAL_MIGRATION_META_KEY', 'tender_old_id');
define('TAL_MIGRATION_OPTION_CSV_DIR', 'tal_migration_csv_dir');
define('TAL_MIGRATION_OPTION_MEDIA_BASE', 'tal_migration_media_base_path');
define('TAL_MIGRATION_ATTACHMENT_SOURCE_URL_META_KEY', '_tal_source_cover_url');
define('TAL_MIGRATION_DEFAULT_SECTION_NAME', 'Sin sección / No section');
define('TAL_MIGRATION_OPTION_LAST_JOB_ID', 'tal_migration_last_job_id');
define('TAL_MIGRATION_JOB_STATUS_PENDING', 'pending');
define('TAL_MIGRATION_JOB_STATUS_RUNNING', 'running');
define('TAL_MIGRATION_JOB_STATUS_COMPLETED', 'completed');
define('TAL_MIGRATION_JOB_STATUS_FAILED', 'failed');
define('TAL_MIGRATION_JOB_STATUS_CANCELLED', 'cancelled');
define('TAL_MIGRATION_BATCH_SIZE', 50);

add_action('init', 'tal_migration_ensure_storage', 5);
add_action('wp_ajax_tal_migration_job_status', 'tal_migration_ajax_job_status');
add_action('wp_ajax_tal_migration_cancel_job', 'tal_migration_ajax_cancel_job');
add_action('tal_migration_process_job', 'tal_migration_process_job_runner', 10, 1);

function tal_migration_register_menu()
{
	add_submenu_page(
		'tender-library',
		__('CSV Migration', 'tender-a-library'),
		__('CSV Migration', 'tender-a-library'),
		'manage_options',
		'tal-csv-migration',
		'tal_migration_render_page'
	);
}
add_action('admin_menu', 'tal_migration_register_menu');

function tal_migration_ensure_storage()
{
	static $checked = false;

	if ($checked || !defined('TENDER_TABLE_MIGRATION_JOBS')) {
		return;
	}

	$checked = true;

	global $wpdb;

	$table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', TENDER_TABLE_MIGRATION_JOBS));
	if ($table_exists !== TENDER_TABLE_MIGRATION_JOBS && function_exists('tender_create_database_tables')) {
		tender_create_database_tables();
	}
}

function tal_migration_get_steps_for_request($step)
{
	return $step === 'all'
		? ['sections', 'books', 'users', 'lendings', 'calls']
		: [$step];
}

function tal_migration_get_step_filename($step)
{
	$map = [
		'sections' => 'biblio_sections.csv',
		'books' => 'biblio_books.csv',
		'users' => 'biblio_users.csv',
		'lendings' => 'biblio_lendings.csv',
		'calls' => 'biblio_calls.csv',
	];

	return isset($map[$step]) ? $map[$step] : '';
}

function tal_migration_get_job($job_id)
{
	global $wpdb;

	$row = $wpdb->get_row($wpdb->prepare(
		'SELECT * FROM ' . TENDER_TABLE_MIGRATION_JOBS . ' WHERE id = %d',
		$job_id
	), ARRAY_A);

	if (!$row) {
		return null;
	}

	return tal_migration_prepare_job($row);
}

function tal_migration_prepare_job($row)
{
	if (!$row) {
		return null;
	}

	$row['id'] = (int) $row['id'];
	$row['dry_run'] = !empty($row['dry_run']);
	$row['step_index'] = (int) $row['step_index'];
	$row['offset_in_step'] = (int) $row['offset_in_step'];
	$row['total_rows'] = (int) $row['total_rows'];
	$row['processed_rows'] = (int) $row['processed_rows'];
	$row['created_count'] = (int) $row['created_count'];
	$row['updated_count'] = (int) $row['updated_count'];
	$row['skipped_count'] = (int) $row['skipped_count'];
	$row['error_count'] = (int) $row['error_count'];
	$row['steps'] = tal_migration_decode_json_field($row['steps'], []);
	$row['step_totals'] = tal_migration_decode_json_field($row['step_totals'], []);
	$row['source_paths'] = tal_migration_decode_json_field($row['source_paths'], []);
	$row['messages'] = tal_migration_decode_json_field($row['messages'], []);
	$row['errors'] = tal_migration_decode_json_field($row['errors'], []);
	$row['progress_percent'] = $row['total_rows'] > 0
		? min(100, (int) floor(($row['processed_rows'] / $row['total_rows']) * 100))
		: 0;
	$row['active'] = in_array($row['status'], [TAL_MIGRATION_JOB_STATUS_PENDING, TAL_MIGRATION_JOB_STATUS_RUNNING], true);

	return $row;
}

function tal_migration_decode_json_field($value, $default)
{
	if (empty($value)) {
		return $default;
	}

	$decoded = json_decode($value, true);
	return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
}

function tal_migration_encode_json_field($value)
{
	return wp_json_encode($value);
}

function tal_migration_get_latest_job()
{
	$job_id = (int) get_option(TAL_MIGRATION_OPTION_LAST_JOB_ID, 0);
	if ($job_id) {
		$job = tal_migration_get_job($job_id);
		if ($job) {
			return $job;
		}
	}

	global $wpdb;
	$row = $wpdb->get_row(
		'SELECT * FROM ' . TENDER_TABLE_MIGRATION_JOBS . ' ORDER BY id DESC LIMIT 1',
		ARRAY_A
	);

	return $row ? tal_migration_prepare_job($row) : null;
}

function tal_migration_get_active_job()
{
	global $wpdb;

	$row = $wpdb->get_row($wpdb->prepare(
		'SELECT * FROM ' . TENDER_TABLE_MIGRATION_JOBS . ' WHERE status IN (%s, %s) ORDER BY id DESC LIMIT 1',
		TAL_MIGRATION_JOB_STATUS_PENDING,
		TAL_MIGRATION_JOB_STATUS_RUNNING
	), ARRAY_A);

	return $row ? tal_migration_prepare_job($row) : null;
}

function tal_migration_schedule_job($job_id, $delay = 1)
{
	$job_id = (int) $job_id;
	if ($job_id <= 0) {
		return;
	}

	if (!wp_next_scheduled('tal_migration_process_job', [$job_id])) {
		wp_schedule_single_event(time() + max(1, (int) $delay), 'tal_migration_process_job', [$job_id]);
	}
}

function tal_migration_unschedule_job($job_id)
{
	$job_id = (int) $job_id;
	if ($job_id <= 0) {
		return;
	}

	while ($timestamp = wp_next_scheduled('tal_migration_process_job', [$job_id])) {
		wp_unschedule_event($timestamp, 'tal_migration_process_job', [$job_id]);
	}
}

function tal_migration_acquire_job_lock($job_id, $ttl = 55)
{
	$key = 'tal_migration_job_lock_' . (int) $job_id;
	if (get_transient($key)) {
		return false;
	}

	set_transient($key, 1, $ttl);
	return true;
}

function tal_migration_release_job_lock($job_id)
{
	delete_transient('tal_migration_job_lock_' . (int) $job_id);
}

function tal_migration_update_job($job_id, $data)
{
	global $wpdb;

	if (isset($data['steps'])) {
		$data['steps'] = tal_migration_encode_json_field($data['steps']);
	}
	if (isset($data['step_totals'])) {
		$data['step_totals'] = tal_migration_encode_json_field($data['step_totals']);
	}
	if (isset($data['source_paths'])) {
		$data['source_paths'] = tal_migration_encode_json_field($data['source_paths']);
	}
	if (isset($data['messages'])) {
		$data['messages'] = tal_migration_encode_json_field($data['messages']);
	}
	if (isset($data['errors'])) {
		$data['errors'] = tal_migration_encode_json_field($data['errors']);
	}

	$data['last_activity_at'] = current_time('mysql');

	$wpdb->update(
		TENDER_TABLE_MIGRATION_JOBS,
		$data,
		['id' => (int) $job_id]
	);
}

function tal_migration_append_log_items($items, $new_items, $limit = 100)
{
	$items = is_array($items) ? $items : [];
	$new_items = is_array($new_items) ? $new_items : [];

	foreach ($new_items as $item) {
		$item = is_scalar($item) ? trim((string) $item) : '';
		if ($item === '') {
			continue;
		}
		$items[] = $item;
	}

	if (count($items) > $limit) {
		$items = array_slice($items, -1 * $limit);
	}

	return array_values($items);
}

function tal_migration_create_job($step, $csv_dir, $media_base, $dry_run, $source_paths)
{
	global $wpdb;

	$steps = tal_migration_get_steps_for_request($step);
	$step_totals = [];
	$total_rows = 0;

	foreach ($steps as $current_step) {
		$path = isset($source_paths[$current_step]) ? $source_paths[$current_step] : '';
		if ($path === '') {
			return new WP_Error('missing_source_path', sprintf(__('Missing CSV path for step %s.', 'tender-a-library'), $current_step));
		}

		$count = tal_migration_count_csv_rows($path);
		if (is_wp_error($count)) {
			return $count;
		}

		$step_totals[$current_step] = (int) $count;
		$total_rows += (int) $count;
	}

	$now = current_time('mysql');
	$inserted = $wpdb->insert(
		TENDER_TABLE_MIGRATION_JOBS,
		[
			'requested_step' => $step,
			'current_step' => $steps[0],
			'status' => TAL_MIGRATION_JOB_STATUS_PENDING,
			'dry_run' => $dry_run ? 1 : 0,
			'csv_dir' => $csv_dir,
			'media_base_path' => $media_base,
			'step_index' => 0,
			'offset_in_step' => 0,
			'total_rows' => $total_rows,
			'processed_rows' => 0,
			'created_count' => 0,
			'updated_count' => 0,
			'skipped_count' => 0,
			'error_count' => 0,
			'steps' => tal_migration_encode_json_field($steps),
			'step_totals' => tal_migration_encode_json_field($step_totals),
			'source_paths' => tal_migration_encode_json_field($source_paths),
			'messages' => tal_migration_encode_json_field($dry_run ? [__('Dry run enabled: no data will be written.', 'tender-a-library')] : []),
			'errors' => tal_migration_encode_json_field([]),
			'last_error' => '',
			'created_at' => $now,
			'last_activity_at' => $now,
		],
		[
			'%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d',
			'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
		]
	);

	if ($inserted === false) {
		return new WP_Error('job_insert_failed', __('Could not create migration job.', 'tender-a-library'));
	}

	$job_id = (int) $wpdb->insert_id;
	update_option(TAL_MIGRATION_OPTION_LAST_JOB_ID, $job_id, false);

	return tal_migration_get_job($job_id);
}

function tal_migration_mark_job_failed($job, $errors)
{
	$errors = tal_migration_append_log_items($job['errors'], $errors);
	tal_migration_update_job($job['id'], [
		'status' => TAL_MIGRATION_JOB_STATUS_FAILED,
		'finished_at' => current_time('mysql'),
		'errors' => $errors,
		'error_count' => count($errors),
		'last_error' => !empty($errors) ? end($errors) : '',
	]);
}

function tal_migration_store_last_result_from_job($job)
{
	$result = [
		'step' => $job['requested_step'],
		'timestamp' => current_time('mysql'),
		'messages' => is_array($job['messages']) ? $job['messages'] : [],
		'errors' => is_array($job['errors']) ? $job['errors'] : [],
	];

	set_transient('tal_migration_last_result', $result, 30 * MINUTE_IN_SECONDS);
}

function tal_migration_ajax_job_status()
{
	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Insufficient permissions.', 'tender-a-library')], 403);
	}

	check_ajax_referer('tal_migration_job_status', 'nonce');

	$job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
	if ($job_id <= 0) {
		wp_send_json_error(['message' => __('Missing job ID.', 'tender-a-library')], 400);
	}

	$job = tal_migration_get_job($job_id);
	if (!$job) {
		wp_send_json_error(['message' => __('Migration job not found.', 'tender-a-library')], 404);
	}

	if ($job['active']) {
		tal_migration_schedule_job($job['id'], 1);
	}

	wp_send_json_success(['job' => $job]);
}

function tal_migration_cancel_job($job_id)
{
	$job = tal_migration_get_job($job_id);
	if (!$job) {
		return new WP_Error('job_not_found', __('Migration job not found.', 'tender-a-library'));
	}

	if (!$job['active']) {
		return $job;
	}

	$messages = tal_migration_append_log_items($job['messages'], [__('Migration cancelled by user.', 'tender-a-library')]);
	tal_migration_update_job($job['id'], [
		'status' => TAL_MIGRATION_JOB_STATUS_CANCELLED,
		'messages' => $messages,
		'finished_at' => current_time('mysql'),
	]);
	tal_migration_unschedule_job($job['id']);

	$job = tal_migration_get_job($job['id']);
	if ($job) {
		tal_migration_store_last_result_from_job($job);
	}

	return $job;
}

function tal_migration_ajax_cancel_job()
{
	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => __('Insufficient permissions.', 'tender-a-library')], 403);
	}

	check_ajax_referer('tal_migration_job_status', 'nonce');

	$job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
	if ($job_id <= 0) {
		wp_send_json_error(['message' => __('Missing job ID.', 'tender-a-library')], 400);
	}

	$job = tal_migration_cancel_job($job_id);
	if (is_wp_error($job)) {
		wp_send_json_error(['message' => $job->get_error_message()], 404);
	}

	wp_send_json_success(['job' => $job]);
}

function tal_migration_render_job_panel($job)
{
	echo '<h2>' . esc_html__('Background Job', 'tender-a-library') . '</h2>';
	echo '<div id="tal-migration-job-panel" class="tal-migration-note">';

	if (!$job) {
		echo '<p>' . esc_html__('No migration job has been started yet.', 'tender-a-library') . '</p>';
		echo '</div>';
		return;
	}

	echo '<p><strong>' . esc_html__('Status', 'tender-a-library') . ':</strong> <span data-tal-job-status>' . esc_html($job['status']) . '</span></p>';
	echo '<p><strong>' . esc_html__('Current step', 'tender-a-library') . ':</strong> <span data-tal-job-step>' . esc_html($job['current_step']) . '</span></p>';
	echo '<div style="max-width:720px;background:#e2e4e7;height:18px;border-radius:999px;overflow:hidden;">';
	echo '<div data-tal-job-progress-bar style="background:#2271b1;height:100%;width:' . esc_attr((string) $job['progress_percent']) . '%;"></div>';
	echo '</div>';
	echo '<p data-tal-job-progress-text>' . esc_html(sprintf('%d%% · %d / %d rows', $job['progress_percent'], $job['processed_rows'], $job['total_rows'])) . '</p>';
	echo '<p data-tal-job-counts>';
	echo esc_html(sprintf(
		'Created: %d · Updated: %d · Skipped: %d · Errors: %d',
		$job['created_count'],
		$job['updated_count'],
		$job['skipped_count'],
		$job['error_count']
	));
	echo '</p>';
	if ($job['active']) {
		echo '<p><button type="button" class="button button-secondary" data-tal-cancel-job>' . esc_html__('Stop Migration', 'tender-a-library') . '</button></p>';
	}
	echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;max-width:720px;">';
	foreach ((array) $job['step_totals'] as $step => $count) {
		echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:8px 10px;">';
		echo '<strong>' . esc_html(ucfirst($step)) . '</strong><br />';
		echo '<span>' . esc_html(sprintf('%d rows', (int) $count)) . '</span>';
		echo '</div>';
	}
	echo '</div>';

	echo '<div data-tal-job-errors>';
	if (!empty($job['errors'])) {
		echo '<div class="tal-migration-log-box">';
		echo '<p><strong>' . esc_html__('Recent errors', 'tender-a-library') . ':</strong></p><ul>';
		foreach (array_slice($job['errors'], -20) as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
	echo '</div>';
	echo '</div>';
}

function tal_migration_render_job_panel_script($job)
{
	if (!$job) {
		return;
	}

	$ajax_url = admin_url('admin-ajax.php');
	$nonce = wp_create_nonce('tal_migration_job_status');
	$job_id = (int) $job['id'];

	echo '<script>';
	echo '(function(){';
	echo 'const panel=document.getElementById("tal-migration-job-panel");';
	echo 'if(!panel){return;}';
	echo 'const jobId=' . wp_json_encode($job_id) . ';';
	echo 'const ajaxUrl=' . wp_json_encode($ajax_url) . ';';
	echo 'const nonce=' . wp_json_encode($nonce) . ';';
	echo 'const cancelButton=panel.querySelector("[data-tal-cancel-job]");';
	echo 'function esc(str){const div=document.createElement("div");div.textContent=String(str ?? "");return div.innerHTML;}';
	echo 'function render(job){';
	echo 'const statusEl=panel.querySelector("[data-tal-job-status]"); if(statusEl){statusEl.textContent=job.status;}';
	echo 'const stepEl=panel.querySelector("[data-tal-job-step]"); if(stepEl){stepEl.textContent=job.current_step || "-";}';
	echo 'const barEl=panel.querySelector("[data-tal-job-progress-bar]"); if(barEl){barEl.style.width=(job.progress_percent || 0)+"%";}';
	echo 'const progressEl=panel.querySelector("[data-tal-job-progress-text]"); if(progressEl){progressEl.textContent=(job.progress_percent || 0)+"% · "+(job.processed_rows || 0)+" / "+(job.total_rows || 0)+" rows";}';
	echo 'const countsEl=panel.querySelector("[data-tal-job-counts]"); if(countsEl){countsEl.textContent="Created: "+(job.created_count || 0)+" · Updated: "+(job.updated_count || 0)+" · Skipped: "+(job.skipped_count || 0)+" · Errors: "+(job.error_count || 0);}';
	echo 'const errorsEl=panel.querySelector("[data-tal-job-errors]"); if(errorsEl){if(job.errors && job.errors.length){errorsEl.innerHTML="<div class=\"tal-migration-log-box\"><p><strong>Recent errors</strong></p><ul>"+job.errors.slice(-20).map((item)=>"<li>"+esc(item)+"</li>").join("")+"</ul></div>";}else{errorsEl.innerHTML="";}}';
	echo 'if(cancelButton){cancelButton.disabled=!(job.status==="pending" || job.status==="running"); cancelButton.textContent=(job.status==="pending" || job.status==="running") ? "Stop Migration" : "Migration Stopped";}';
	echo '}';
	echo 'function cancelJob(){ if(!cancelButton || cancelButton.disabled){return;} cancelButton.disabled=true; cancelButton.textContent="Stopping..."; const body=new URLSearchParams(); body.set("action","tal_migration_cancel_job"); body.set("job_id", String(jobId)); body.set("nonce", nonce); fetch(ajaxUrl,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"},body:body.toString()}).then((res)=>res.json()).then((payload)=>{ if(payload && payload.success && payload.data && payload.data.job){ render(payload.data.job); } else { cancelButton.disabled=false; cancelButton.textContent="Stop Migration"; } }).catch(()=>{ cancelButton.disabled=false; cancelButton.textContent="Stop Migration"; }); }';
	echo 'function poll(){';
	echo 'fetch(ajaxUrl+"?action=tal_migration_job_status&job_id="+encodeURIComponent(jobId)+"&nonce="+encodeURIComponent(nonce),{credentials:"same-origin"})';
	echo '.then((res)=>res.json())';
	echo '.then((payload)=>{ if(!payload || !payload.success || !payload.data || !payload.data.job){return;} const job=payload.data.job; render(job); if(job.status==="pending" || job.status==="running"){ setTimeout(poll, 2500); } })';
	echo '.catch(()=>{ setTimeout(poll, 5000); });';
	echo '}';
	echo 'if(cancelButton){cancelButton.addEventListener("click", cancelJob);}';
	echo 'render(' . wp_json_encode($job) . ');';
	if ($job['active']) {
		echo 'setTimeout(poll, 1500);';
	}
	echo '})();';
	echo '</script>';
}

function tal_migration_render_page()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-a-library'));
	}

	$csv_dir = tal_migration_get_csv_dir();
	$media_base = get_option(TAL_MIGRATION_OPTION_MEDIA_BASE, '');
	$result = get_transient('tal_migration_last_result');
	$job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
	$job = $job_id > 0 ? tal_migration_get_job($job_id) : tal_migration_get_latest_job();

	$csv_files = [
		'biblio_sections.csv',
		'biblio_books.csv',
		'biblio_users.csv',
		'biblio_lendings.csv',
		'biblio_calls.csv',
	];
	$template_links = [
		'biblio_sections.csv' => 'sections-template.csv',
		'biblio_books.csv' => 'books-template.csv',
		'biblio_users.csv' => 'users-template.csv',
		'biblio_lendings.csv' => 'lendings-template.csv',
		'biblio_calls.csv' => 'calls-template.csv',
	];

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__('CSV Migration', 'tender-a-library') . '</h1>';
	echo '<p>' . esc_html__('Run one step at a time or the full migration. Jobs are processed in the background in small batches so large imports can continue safely without keeping the browser request open.', 'tender-a-library') . '</p>';
	echo '<style>
	.tal-migration-log-box {max-height:240px; overflow:auto; background:#fff; border:1px solid #ccd0d4; padding:10px 14px; margin:10px 0;}
	.tal-migration-log-box ul {margin:0 0 0 18px;}
	.tal-migration-log-box p {margin:0 0 8px;}
	</style>';

	if (is_array($result)) {
		echo '<div class="notice notice-info"><p><strong>' . esc_html__('Last migration run', 'tender-a-library') . ':</strong> ';
		echo esc_html($result['step'] ?? '-') . ' · ';
		echo esc_html($result['timestamp'] ?? '-') . '</p>';
		echo '<div class="tal-migration-log-box">';
		if (!empty($result['messages'])) {
			echo '<ul>';
			foreach ($result['messages'] as $message) {
				echo '<li>' . esc_html($message) . '</li>';
			}
			echo '</ul>';
		}
		if (!empty($result['errors'])) {
			echo '<p><strong>' . esc_html__('Errors', 'tender-a-library') . ':</strong></p>';
			echo '<ul>';
			foreach ($result['errors'] as $error) {
				echo '<li>' . esc_html($error) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
		echo '</div>';
	}

	tal_migration_render_job_panel($job);

	echo '<h2>' . esc_html__('CSV Source', 'tender-a-library') . '</h2>';
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
	wp_nonce_field('tal_run_migration');
	echo '<input type="hidden" name="action" value="tal_run_migration" />';

	echo '<table class="form-table"><tbody>';
	echo '<tr><th scope="row"><label for="tal_migration_csv_dir">' . esc_html__('CSV directory', 'tender-a-library') . '</label></th>';
	echo '<td><input type="text" class="regular-text" id="tal_migration_csv_dir" name="tal_migration_csv_dir" value="' . esc_attr($csv_dir) . '" />';
	echo '<p class="description">' . esc_html__('Folder containing the CSV exports.', 'tender-a-library') . '</p></td></tr>';

	echo '<tr><th scope="row"><label for="tal_migration_media_base">' . esc_html__('Media base path', 'tender-a-library') . '</label></th>';
	echo '<td><input type="text" class="regular-text" id="tal_migration_media_base" name="tal_migration_media_base" value="' . esc_attr($media_base) . '" />';
	echo '<p class="description">' . esc_html__('Optional root folder for cover_path values in biblio_books.csv. Leave empty if your books CSV uses full cover URLs.', 'tender-a-library') . '</p></td></tr>';
	echo '</tbody></table>';

	echo '<h2>' . esc_html__('Detected CSV Files', 'tender-a-library') . '</h2>';
	echo '<ul>';
	foreach ($csv_files as $file) {
		$path = trailingslashit($csv_dir) . $file;
		$status = file_exists($path) ? __('Found', 'tender-a-library') : __('Missing', 'tender-a-library');
		$template = $template_links[$file] ?? '';
		$download_url = $template ? tal_migration_template_download_url($template) : '';
		$download_html = $download_url ? ' · <a href="' . esc_url($download_url) . '">' . esc_html__('Download template', 'tender-a-library') . '</a>' : '';
		echo '<li>' . esc_html($file) . ' · ' . esc_html($status) . $download_html . '</li>';
	}
	echo '</ul>';

	echo '<h2>' . esc_html__('Upload CSV Files', 'tender-a-library') . '</h2>';
	echo '<p>' . esc_html__('Upload a file to override the CSV directory for this run.', 'tender-a-library') . '</p>';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th scope="row">' . esc_html__('Sections CSV', 'tender-a-library') . '</th><td><input type="file" name="tal_upload_sections" accept=".csv" /></td></tr>';
	echo '<tr><th scope="row">' . esc_html__('Books CSV', 'tender-a-library') . '</th><td><input type="file" name="tal_upload_books" accept=".csv" /></td></tr>';
	echo '<tr><th scope="row">' . esc_html__('Users CSV', 'tender-a-library') . '</th><td><input type="file" name="tal_upload_users" accept=".csv" /></td></tr>';
	echo '<tr><th scope="row">' . esc_html__('Lendings CSV', 'tender-a-library') . '</th><td><input type="file" name="tal_upload_lendings" accept=".csv" /></td></tr>';
	echo '<tr><th scope="row">' . esc_html__('Calls CSV', 'tender-a-library') . '</th><td><input type="file" name="tal_upload_calls" accept=".csv" /></td></tr>';
	echo '</tbody></table>';

	echo '<h2>' . esc_html__('Run Migration', 'tender-a-library') . '</h2>';
	echo '<p>' . esc_html__('Order matters: sections, books, users, lendings, calls. The books CSV handles languages and cover downloads, and connects to previously imported sections.', 'tender-a-library') . '</p>';
	echo '<style>
	.tal-migration-steps {display:flex; flex-wrap:wrap; gap:12px; margin:16px 0;}
	.tal-migration-step {background:#fff; border:1px solid #ccd0d4; border-left:4px solid #1d2327; padding:12px 14px; min-width:180px;}
	.tal-migration-step strong {display:block; margin-bottom:4px;}
	.tal-migration-note {background:#f6f7f7; border:1px solid #ccd0d4; padding:12px 14px; margin:12px 0;}
	</style>';
	echo '<div class="tal-migration-steps">';
	echo '<div class="tal-migration-step"><strong>1. Sections</strong>Taxonomy terms + numbers + hierarchy.</div>';
	echo '<div class="tal-migration-step"><strong>2. Books</strong>Books + languages + cover downloads.</div>';
	echo '<div class="tal-migration-step"><strong>3. Users</strong>WP users + meta.</div>';
	echo '<div class="tal-migration-step"><strong>4. Lendings</strong>Custom table rows.</div>';
	echo '<div class="tal-migration-step"><strong>5. Calls</strong>Custom table rows.</div>';
	echo '</div>';
	echo '<div class="tal-migration-note">';
	echo '<label><input type="checkbox" name="tal_migration_dry_run" value="1" /> ' . esc_html__('Dry run (no data will be written)', 'tender-a-library') . '</label>';
	echo '<p class="description">' . esc_html__('Use this to preview counts and missing mappings before running the real import.', 'tender-a-library') . '</p>';
	echo '</div>';
	if ($job && $job['active']) {
		echo '<p><em>' . esc_html__('A migration job is currently running. Starting a second one is blocked until it finishes.', 'tender-a-library') . '</em></p>';
	}
	echo '<p>';
	$disabled = ($job && $job['active']) ? ' disabled="disabled"' : '';
	echo '<button class="button button-primary" type="submit" name="tal_migration_step" value="all"' . $disabled . '>' . esc_html__('Run Full Migration', 'tender-a-library') . '</button> ';
	echo '<button class="button" type="submit" name="tal_migration_step" value="sections"' . $disabled . '>' . esc_html__('Import Sections', 'tender-a-library') . '</button> ';
	echo '<button class="button" type="submit" name="tal_migration_step" value="users"' . $disabled . '>' . esc_html__('Import Users', 'tender-a-library') . '</button> ';
	echo '<button class="button" type="submit" name="tal_migration_step" value="books"' . $disabled . '>' . esc_html__('Import Books', 'tender-a-library') . '</button> ';
	echo '<button class="button" type="submit" name="tal_migration_step" value="lendings"' . $disabled . '>' . esc_html__('Import Lendings', 'tender-a-library') . '</button> ';
	echo '<button class="button" type="submit" name="tal_migration_step" value="calls"' . $disabled . '>' . esc_html__('Import Calls', 'tender-a-library') . '</button>';
	echo '</p>';

	echo '</form>';
	tal_migration_render_job_panel_script($job);
	echo '</div>';
}

function tal_migration_handle_run()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-a-library'));
	}

	check_admin_referer('tal_run_migration');

	$csv_dir = isset($_POST['tal_migration_csv_dir']) ? sanitize_text_field(wp_unslash($_POST['tal_migration_csv_dir'])) : '';
	$media_base = isset($_POST['tal_migration_media_base']) ? sanitize_text_field(wp_unslash($_POST['tal_migration_media_base'])) : '';
	if (!empty($csv_dir)) {
		update_option(TAL_MIGRATION_OPTION_CSV_DIR, $csv_dir);
	}
	update_option(TAL_MIGRATION_OPTION_MEDIA_BASE, $media_base);

	$step = isset($_POST['tal_migration_step']) ? sanitize_text_field(wp_unslash($_POST['tal_migration_step'])) : 'all';
	$dry_run = !empty($_POST['tal_migration_dry_run']);
	$uploaded_paths = tal_migration_handle_uploads();

	$active_job = tal_migration_get_active_job();
	if ($active_job) {
		set_transient('tal_migration_last_result', [
			'step' => $step,
			'timestamp' => current_time('mysql'),
			'messages' => [],
			'errors' => [__('Another migration job is already running.', 'tender-a-library')],
		], 30 * MINUTE_IN_SECONDS);
		wp_safe_redirect(admin_url('admin.php?page=tal-csv-migration'));
		exit;
	}

	$steps = tal_migration_get_steps_for_request($step);
	$source_paths = [];
	foreach ($steps as $current_step) {
		$path_override = isset($uploaded_paths[$current_step]) ? $uploaded_paths[$current_step] : '';
		$filename = tal_migration_get_step_filename($current_step);
		$source_paths[$current_step] = $path_override ?: tal_migration_csv_path($csv_dir, $filename);
	}

	$job = tal_migration_create_job($step, $csv_dir, $media_base, $dry_run, $source_paths);
	if (is_wp_error($job)) {
		set_transient('tal_migration_last_result', [
			'step' => $step,
			'timestamp' => current_time('mysql'),
			'messages' => [],
			'errors' => [$job->get_error_message()],
		], 30 * MINUTE_IN_SECONDS);
		wp_safe_redirect(admin_url('admin.php?page=tal-csv-migration'));
		exit;
	}

	tal_migration_schedule_job($job['id'], 1);
	wp_safe_redirect(admin_url('admin.php?page=tal-csv-migration&job_id=' . $job['id']));
	exit;
}
add_action('admin_post_tal_run_migration', 'tal_migration_handle_run');

function tal_migration_run_step($step, $csv_dir, $media_base, $dry_run, $uploaded_paths = [])
{
	$messages = [];
	$errors = [];
	$path_override = isset($uploaded_paths[$step]) ? $uploaded_paths[$step] : '';

	switch ($step) {
		case 'sections':
			$path = $path_override ?: tal_migration_csv_path($csv_dir, 'biblio_sections.csv');
			$result = tal_migration_import_sections($path, $dry_run);
			break;
		case 'users':
			$path = $path_override ?: tal_migration_csv_path($csv_dir, 'biblio_users.csv');
			$result = tal_migration_import_users($path, $dry_run);
			break;
		case 'books':
			$path = $path_override ?: tal_migration_csv_path($csv_dir, 'biblio_books.csv');
			$result = tal_migration_import_books($path, $media_base, $dry_run);
			break;
		case 'lendings':
			$path = $path_override ?: tal_migration_csv_path($csv_dir, 'biblio_lendings.csv');
			$result = tal_migration_import_lendings($path, $dry_run);
			break;
		case 'calls':
			$path = $path_override ?: tal_migration_csv_path($csv_dir, 'biblio_calls.csv');
			$result = tal_migration_import_calls($path, $dry_run);
			break;
		default:
			return [
				'messages' => [],
				'errors' => [sprintf(__('Unknown migration step: %s', 'tender-a-library'), $step)],
			];
	}

	if (!empty($result['errors'])) {
		$errors = array_merge($errors, $result['errors']);
	}
	if (!empty($result['messages'])) {
		$messages = array_merge($messages, $result['messages']);
	}

	return [
		'messages' => $messages,
		'errors' => $errors,
	];
}

function tal_migration_handle_uploads()
{
	if (empty($_FILES)) {
		return [];
	}

	$map = [
		'sections' => 'tal_upload_sections',
		'books' => 'tal_upload_books',
		'users' => 'tal_upload_users',
		'lendings' => 'tal_upload_lendings',
		'calls' => 'tal_upload_calls',
	];

	$uploaded_paths = [];
	require_once ABSPATH . 'wp-admin/includes/file.php';

	foreach ($map as $step => $field) {
		if (empty($_FILES[$field]) || !empty($_FILES[$field]['error'])) {
			continue;
		}

		$upload = wp_handle_upload($_FILES[$field], ['test_form' => false]);
		if (!empty($upload['file'])) {
			$uploaded_paths[$step] = $upload['file'];
		}
	}

	return $uploaded_paths;
}

function tal_migration_template_download_url($template)
{
	return add_query_arg(
		[
			'action' => 'tal_download_migration_template',
			'template' => $template,
		],
		admin_url('admin-post.php')
	);
}

function tal_migration_handle_template_download()
{
	if (!current_user_can('manage_options')) {
		wp_die(__('Insufficient permissions.', 'tender-a-library'));
	}

	$template = isset($_GET['template']) ? sanitize_file_name(wp_unslash($_GET['template'])) : '';
	$allowed = [
		'sections-template.csv',
		'books-template.csv',
		'users-template.csv',
		'lendings-template.csv',
		'calls-template.csv',
	];
	if (!in_array($template, $allowed, true)) {
		wp_die(__('Invalid template.', 'tender-a-library'));
	}

	$path = __DIR__ . '/templates/' . $template;
	if (!file_exists($path)) {
		wp_die(__('Template not found.', 'tender-a-library'));
	}

	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename=' . $template);
	header('Content-Length: ' . filesize($path));
	readfile($path);
	exit;
}
add_action('admin_post_tal_download_migration_template', 'tal_migration_handle_template_download');

function tal_migration_get_csv_dir()
{
	$dir = get_option(TAL_MIGRATION_OPTION_CSV_DIR, '');
	if (!empty($dir)) {
		return $dir;
	}

	return dirname(__DIR__, 2) . '/temp';
}

function tal_migration_csv_path($dir, $filename)
{
	$dir = $dir ?: tal_migration_get_csv_dir();
	return trailingslashit($dir) . $filename;
}

function tal_migration_read_csv($path)
{
	if (!file_exists($path)) {
		return new WP_Error('missing_csv', sprintf(__('CSV not found: %s', 'tender-a-library'), $path));
	}

	$handle = fopen($path, 'r');
	if (!$handle) {
		return new WP_Error('csv_open_failed', sprintf(__('Could not open CSV: %s', 'tender-a-library'), $path));
	}

	$header = fgetcsv($handle, 0, ',', '"');
	if (!$header) {
		fclose($handle);
		return new WP_Error('csv_empty', sprintf(__('Empty CSV: %s', 'tender-a-library'), $path));
	}

	$header[0] = tal_migration_strip_bom($header[0]);
	$rows = [];

	while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
		if ($data === [null] || $data === false) {
			continue;
		}
		$row = [];
		foreach ($header as $index => $key) {
			$value = array_key_exists($index, $data) ? $data[$index] : null;
			$row[$key] = tal_migration_normalize_value($value);
		}
		$rows[] = $row;
	}

	fclose($handle);
	return $rows;
}

function tal_migration_strip_bom($value)
{
	if (!is_string($value)) {
		return $value;
	}
	return preg_replace('/^\xEF\xBB\xBF/', '', $value);
}

function tal_migration_normalize_value($value)
{
	if ($value === null) {
		return null;
	}
	$value = trim($value);
	if ($value === 'NULL') {
		return null;
	}
	return $value;
}

function tal_migration_count_csv_rows($path)
{
	if (!file_exists($path)) {
		return new WP_Error('missing_csv', sprintf(__('CSV not found: %s', 'tender-a-library'), $path));
	}

	$handle = fopen($path, 'r');
	if (!$handle) {
		return new WP_Error('csv_open_failed', sprintf(__('Could not open CSV: %s', 'tender-a-library'), $path));
	}

	$header = fgetcsv($handle, 0, ',', '"');
	if (!$header) {
		fclose($handle);
		return new WP_Error('csv_empty', sprintf(__('Empty CSV: %s', 'tender-a-library'), $path));
	}

	$count = 0;
	while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
		if ($data === [null] || $data === false) {
			continue;
		}
		$count++;
	}

	fclose($handle);
	return $count;
}

function tal_migration_read_csv_batch($path, $offset, $limit)
{
	if (!file_exists($path)) {
		return new WP_Error('missing_csv', sprintf(__('CSV not found: %s', 'tender-a-library'), $path));
	}

	$handle = fopen($path, 'r');
	if (!$handle) {
		return new WP_Error('csv_open_failed', sprintf(__('Could not open CSV: %s', 'tender-a-library'), $path));
	}

	$header = fgetcsv($handle, 0, ',', '"');
	if (!$header) {
		fclose($handle);
		return new WP_Error('csv_empty', sprintf(__('Empty CSV: %s', 'tender-a-library'), $path));
	}

	$header[0] = tal_migration_strip_bom($header[0]);

	$skipped = 0;
	while ($skipped < $offset && ($data = fgetcsv($handle, 0, ',', '"')) !== false) {
		if ($data === [null] || $data === false) {
			continue;
		}
		$skipped++;
	}

	$rows = [];
	$processed = 0;
	while ($processed < $limit && ($data = fgetcsv($handle, 0, ',', '"')) !== false) {
		if ($data === [null] || $data === false) {
			continue;
		}

		$row = [];
		foreach ($header as $index => $key) {
			$value = array_key_exists($index, $data) ? $data[$index] : null;
			$row[$key] = tal_migration_normalize_value($value);
		}
		$rows[] = $row;
		$processed++;
	}

	$complete = feof($handle);
	fclose($handle);

	return [
		'rows' => $rows,
		'processed' => count($rows),
		'complete' => $complete || count($rows) < $limit,
	];
}

function tal_migration_process_job_runner($job_id)
{
	tal_migration_ensure_storage();

	$job = tal_migration_get_job($job_id);
	if (!$job || !$job['active']) {
		return;
	}

	if (!tal_migration_acquire_job_lock($job_id)) {
		return;
	}

	try {
		$job = tal_migration_get_job($job_id);
		if (!$job || !$job['active']) {
			return;
		}

		if ($job['status'] === TAL_MIGRATION_JOB_STATUS_PENDING) {
			tal_migration_update_job($job['id'], [
				'status' => TAL_MIGRATION_JOB_STATUS_RUNNING,
				'started_at' => $job['started_at'] ?: current_time('mysql'),
			]);
			$job = tal_migration_get_job($job['id']);
		}

		$steps = (array) $job['steps'];
		if (!isset($steps[$job['step_index']])) {
			tal_migration_update_job($job['id'], [
				'status' => TAL_MIGRATION_JOB_STATUS_COMPLETED,
				'finished_at' => current_time('mysql'),
			]);
			$job = tal_migration_get_job($job['id']);
			tal_migration_store_last_result_from_job($job);
			return;
		}

		$current_step = $steps[$job['step_index']];
		$source_paths = (array) $job['source_paths'];
		$path = isset($source_paths[$current_step]) ? $source_paths[$current_step] : '';
		if ($path === '') {
			tal_migration_mark_job_failed($job, [sprintf(__('Missing source path for step %s.', 'tender-a-library'), $current_step)]);
			return;
		}

		$batch = tal_migration_process_step_batch(
			$current_step,
			$path,
			$job['offset_in_step'],
			(int) apply_filters('tal_migration_batch_size', TAL_MIGRATION_BATCH_SIZE, $current_step),
			$job['media_base_path'],
			$job['dry_run']
		);

		if (is_wp_error($batch)) {
			tal_migration_mark_job_failed($job, [$batch->get_error_message()]);
			return;
		}

		$messages = tal_migration_append_log_items($job['messages'], $batch['messages']);
		$errors = tal_migration_append_log_items($job['errors'], $batch['errors']);
		$error_count = $job['error_count'] + count($batch['errors']);
		$offset_in_step = $job['offset_in_step'] + $batch['processed'];
		$processed_rows = $job['processed_rows'] + $batch['processed'];
		$update = [
			'current_step' => $current_step,
			'offset_in_step' => $offset_in_step,
			'processed_rows' => $processed_rows,
			'created_count' => $job['created_count'] + $batch['created'],
			'updated_count' => $job['updated_count'] + $batch['updated'],
			'skipped_count' => $job['skipped_count'] + $batch['skipped'],
			'error_count' => $error_count,
			'messages' => $messages,
			'errors' => $errors,
			'last_error' => !empty($batch['errors']) ? end($batch['errors']) : $job['last_error'],
		];

		$step_total = isset($job['step_totals'][$current_step]) ? (int) $job['step_totals'][$current_step] : 0;
		$step_done = $batch['complete'] || ($step_total > 0 && $offset_in_step >= $step_total);

		if ($step_done) {
			$finalize = tal_migration_finalize_step($current_step, $path, $job['dry_run']);
			if (is_wp_error($finalize)) {
				$errors = tal_migration_append_log_items($errors, [$finalize->get_error_message()]);
				$update['errors'] = $errors;
				$update['error_count'] = $error_count + 1;
				$update['last_error'] = $finalize->get_error_message();
			} elseif (!empty($finalize['messages'])) {
				$messages = tal_migration_append_log_items($messages, $finalize['messages']);
				$update['messages'] = $messages;
			}

			$next_step_index = $job['step_index'] + 1;
			if (isset($steps[$next_step_index])) {
				$update['step_index'] = $next_step_index;
				$update['current_step'] = $steps[$next_step_index];
				$update['offset_in_step'] = 0;
			} else {
				$update['status'] = TAL_MIGRATION_JOB_STATUS_COMPLETED;
				$update['finished_at'] = current_time('mysql');
			}
		}

		tal_migration_update_job($job['id'], $update);
		$job = tal_migration_get_job($job['id']);

		if ($job && $job['active']) {
			tal_migration_schedule_job($job['id'], 1);
		} elseif ($job && $job['status'] === TAL_MIGRATION_JOB_STATUS_COMPLETED) {
			tal_migration_store_last_result_from_job($job);
		}
	} finally {
		tal_migration_release_job_lock($job_id);
	}
}

function tal_migration_process_step_batch($step, $path, $offset, $limit, $media_base, $dry_run)
{
	switch ($step) {
		case 'sections':
			return tal_migration_import_sections_batch($path, $offset, $limit, $dry_run);
		case 'users':
			return tal_migration_import_users_batch($path, $offset, $limit, $dry_run);
		case 'books':
			return tal_migration_import_books_batch($path, $offset, $limit, $media_base, $dry_run);
		case 'lendings':
			return tal_migration_import_lendings_batch($path, $offset, $limit, $dry_run);
		case 'calls':
			return tal_migration_import_calls_batch($path, $offset, $limit, $dry_run);
		default:
			return new WP_Error('unknown_step', sprintf(__('Unknown migration step: %s', 'tender-a-library'), $step));
	}
}

function tal_migration_finalize_step($step, $path, $dry_run)
{
	if ($step === 'sections') {
		return tal_migration_finalize_sections($path, $dry_run);
	}

	return ['messages' => [], 'errors' => []];
}

function tal_migration_empty_batch_result($processed = 0, $complete = false)
{
	return [
		'processed' => $processed,
		'complete' => $complete,
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'messages' => [],
		'errors' => [],
	];
}

function tal_migration_import_sections_batch($path, $offset, $limit, $dry_run = false)
{
	$batch = tal_migration_read_csv_batch($path, $offset, $limit);
	if (is_wp_error($batch)) {
		return $batch;
	}

	$result = tal_migration_empty_batch_result($batch['processed'], $batch['complete']);
	$map = tal_migration_build_term_old_id_map('tender_section');

	foreach ($batch['rows'] as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		$name = $row['name'] ?? '';
		if (!$old_id || !$name) {
			continue;
		}

		if (isset($map[$old_id])) {
			$term_id = $map[$old_id];
			$result['updated']++;
		} else {
			$existing = term_exists($name, 'tender_section');
			if ($existing) {
				$term_id = (int) (is_array($existing) ? $existing['term_id'] : $existing);
				$result['updated']++;
				if (!$dry_run) {
					update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
					$map[$old_id] = $term_id;
				}
			} else {
				if ($dry_run) {
					$result['created']++;
					continue;
				}

				$insert = wp_insert_term($name, 'tender_section');
				if (is_wp_error($insert)) {
					if ($insert->get_error_code() === 'term_exists') {
						$term_id = (int) $insert->get_error_data();
						$result['updated']++;
					} else {
						$result['errors'][] = sprintf('Section %s: %s', $name, $insert->get_error_message());
						continue;
					}
				} else {
					$term_id = (int) $insert['term_id'];
					$result['created']++;
				}

				update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
				$map[$old_id] = $term_id;
			}
		}

		$section_number = tal_migration_first_non_empty($row, ['section_number', 'number']);
		if ($section_number !== '' && !$dry_run) {
			tal_migration_set_section_number($term_id, $section_number);
		}
	}

	return $result;
}

function tal_migration_finalize_sections($path, $dry_run = false)
{
	if ($dry_run) {
		return ['messages' => [], 'errors' => []];
	}

	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return $rows;
	}

	$map = tal_migration_build_term_old_id_map('tender_section');
	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		$parent_old = (int) ($row['parent_id'] ?? $row['section_id'] ?? 0);
		if (!$old_id || !$parent_old) {
			continue;
		}
		if (!isset($map[$old_id], $map[$parent_old])) {
			continue;
		}
		wp_update_term($map[$old_id], 'tender_section', ['parent' => $map[$parent_old]]);
	}

	return ['messages' => [__('Sections hierarchy updated.', 'tender-a-library')], 'errors' => []];
}

function tal_migration_import_users_batch($path, $offset, $limit, $dry_run = false)
{
	$batch = tal_migration_read_csv_batch($path, $offset, $limit);
	if (is_wp_error($batch)) {
		return $batch;
	}

	$result = tal_migration_empty_batch_result($batch['processed'], $batch['complete']);
	$map = tal_migration_build_user_old_id_map();

	foreach ($batch['rows'] as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		if (isset($map[$old_id])) {
			$result['updated']++;
			continue;
		}

		$email = $row['email'] ?? '';
		$name = $row['name'] ?? '';
		$role = tal_migration_map_legacy_role($row['role'] ?? '');

		$existing_user = $email ? get_user_by('email', $email) : false;
		if ($existing_user) {
			if (!$dry_run) {
				update_user_meta($existing_user->ID, TAL_MIGRATION_META_KEY, $old_id);
				$map[$old_id] = $existing_user->ID;
			}
			$result['updated']++;
			continue;
		}

		$user_login = $email ? sanitize_user($email, true) : 'legacy_user_' . $old_id;
		if (username_exists($user_login)) {
			$user_login = $user_login . '_' . $old_id;
		}

		if (!$email) {
			$email = 'legacy_user_' . $old_id . '@example.local';
		}

		if ($dry_run) {
			$result['created']++;
			continue;
		}

		$user_id = wp_insert_user([
			'user_login' => $user_login,
			'user_pass' => wp_generate_password(20, true, true),
			'user_email' => $email,
			'display_name' => $name ?: $user_login,
			'role' => $role,
		]);

		if (is_wp_error($user_id)) {
			$result['errors'][] = sprintf('User %s: %s', $email, $user_id->get_error_message());
			continue;
		}

		update_user_meta($user_id, TAL_MIGRATION_META_KEY, $old_id);
		if (!empty($row['phone'])) {
			update_user_meta($user_id, 'phone_number', $row['phone']);
		}
		$map[$old_id] = $user_id;
		$result['created']++;
	}

	return $result;
}

function tal_migration_import_books_batch($path, $offset, $limit, $media_base = '', $dry_run = false)
{
	$batch = tal_migration_read_csv_batch($path, $offset, $limit);
	if (is_wp_error($batch)) {
		return $batch;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$result = tal_migration_empty_batch_result($batch['processed'], $batch['complete']);
	$book_map = tal_migration_build_post_old_id_map('tender_book');
	$lang_map = tal_migration_build_term_old_id_map('tender_language');
	$section_lookup = tal_migration_build_section_lookup();
	$section_old_id_map = tal_migration_build_term_old_id_map('tender_section');

	foreach ($batch['rows'] as $index => $row) {
		$row_number = $offset + $index + 2;
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$section_id = tal_migration_resolve_book_section($row, $section_lookup, $section_old_id_map);
		if (is_wp_error($section_id)) {
			$result['errors'][] = sprintf(__('Book row %1$d old_id %2$d section error: %3$s', 'tender-a-library'), $row_number, $old_id, $section_id->get_error_message());
			continue;
		}

		$lang_id = tal_migration_resolve_book_language($row, $lang_map, $dry_run);
		if (is_wp_error($lang_id)) {
			$result['errors'][] = sprintf(__('Book row %1$d old_id %2$d language error: %3$s', 'tender-a-library'), $row_number, $old_id, $lang_id->get_error_message());
			continue;
		}

		if ($dry_run) {
			if (isset($book_map[$old_id])) {
				$result['updated']++;
			} else {
				$result['created']++;
			}
			continue;
		}

		$title = $row['title'] ?? '';
		$post_args = [
			'post_type' => 'tender_book',
			'post_title' => $title ?: __('Untitled book', 'tender-a-library'),
			'post_status' => 'publish',
			'post_date' => !empty($row['created_at']) ? $row['created_at'] : current_time('mysql'),
			'post_modified' => !empty($row['updated_at']) ? $row['updated_at'] : current_time('mysql'),
		];

		$is_update = isset($book_map[$old_id]);
		if ($is_update) {
			$post_args['ID'] = $book_map[$old_id];
		}

		$post_id = wp_insert_post($post_args, true);
		if (is_wp_error($post_id)) {
			$result['errors'][] = sprintf('Book %s: %s', $title, $post_id->get_error_message());
			continue;
		}

		update_post_meta($post_id, TAL_MIGRATION_META_KEY, $old_id);
		tal_migration_set_book_meta($post_id, $row, $section_id, $lang_id);

		$attachment_id = tal_migration_import_book_cover($row, $media_base, $dry_run);
		if (is_wp_error($attachment_id)) {
			$result['errors'][] = sprintf(__('Book row %1$d old_id %2$d cover error: %3$s', 'tender-a-library'), $row_number, $old_id, $attachment_id->get_error_message());
		} elseif ($attachment_id) {
			tal_migration_set_book_cover($post_id, (int) $attachment_id);
		}

		$book_map[$old_id] = $post_id;
		if ($is_update) {
			$result['updated']++;
		} else {
			$result['created']++;
		}
	}

	return $result;
}

function tal_migration_import_lendings_batch($path, $offset, $limit, $dry_run = false)
{
	global $wpdb;

	$batch = tal_migration_read_csv_batch($path, $offset, $limit);
	if (is_wp_error($batch)) {
		return $batch;
	}

	$result = tal_migration_empty_batch_result($batch['processed'], $batch['complete']);
	$book_map = tal_migration_build_post_old_id_map('tender_book');
	$user_map = tal_migration_build_user_old_id_map();

	foreach ($batch['rows'] as $index => $row) {
		$row_number = $offset + $index + 2;
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$exists = $wpdb->get_var($wpdb->prepare(
			'SELECT id FROM ' . TENDER_TABLE_LENDINGS . ' WHERE old_laravel_id = %d',
			$old_id
		));
		if ($exists) {
			$result['skipped']++;
			continue;
		}

		$book_old = (int) ($row['book_id'] ?? 0);
		$user_old = (int) ($row['user_id'] ?? 0);
		if (!isset($book_map[$book_old], $user_map[$user_old])) {
			$result['errors'][] = sprintf(__('Lending row %1$d old_id %2$d missing book/user mapping.', 'tender-a-library'), $row_number, $old_id);
			continue;
		}

		if ($dry_run) {
			$result['created']++;
			continue;
		}

		$insert = $wpdb->insert(
			TENDER_TABLE_LENDINGS,
			[
				'book_id' => $book_map[$book_old],
				'user_id' => $user_map[$user_old],
				'lending_date' => $row['lending_date'] ?? current_time('mysql'),
				'stimated_return_date' => $row['stimated_return_date'] ?? current_time('mysql'),
				'real_return_date' => $row['real_return_date'] ?? null,
				'returned' => (int) ($row['returned'] ?? 0),
				'extensions' => (int) ($row['extensions'] ?? 0),
				'extension_date' => $row['extension_date'] ?? null,
				'old_laravel_id' => $old_id,
			],
			['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d']
		);

		if ($insert === false) {
			$result['errors'][] = sprintf(__('Failed to insert lending row %1$d old_id %2$d.', 'tender-a-library'), $row_number, $old_id);
			continue;
		}

		$result['created']++;
	}

	return $result;
}

function tal_migration_import_calls_batch($path, $offset, $limit, $dry_run = false)
{
	global $wpdb;

	$batch = tal_migration_read_csv_batch($path, $offset, $limit);
	if (is_wp_error($batch)) {
		return $batch;
	}

	$result = tal_migration_empty_batch_result($batch['processed'], $batch['complete']);
	$user_map = tal_migration_build_user_old_id_map();

	foreach ($batch['rows'] as $index => $row) {
		$row_number = $offset + $index + 2;
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$exists = $wpdb->get_var($wpdb->prepare(
			'SELECT id FROM ' . TENDER_TABLE_USER_CALLS . ' WHERE old_laravel_id = %d',
			$old_id
		));
		if ($exists) {
			$result['skipped']++;
			continue;
		}

		$user_old = (int) ($row['user_id'] ?? 0);
		if (!isset($user_map[$user_old])) {
			$result['errors'][] = sprintf(__('Call row %1$d old_id %2$d missing user mapping.', 'tender-a-library'), $row_number, $old_id);
			continue;
		}

		if ($dry_run) {
			$result['created']++;
			continue;
		}

		$created_at = $row['created_at'] ?? current_time('mysql');
		$call_date = $created_at ? substr($created_at, 0, 10) : current_time('Y-m-d');

		$insert = $wpdb->insert(
			TENDER_TABLE_USER_CALLS,
			[
				'user_id' => $user_map[$user_old],
				'subject' => $row['subject'] ?? '',
				'comment' => $row['comment'] ?? null,
				'call_date' => $call_date,
				'created_at' => $created_at,
				'updated_at' => $row['updated_at'] ?? $created_at,
				'old_laravel_id' => $old_id,
			],
			['%d', '%s', '%s', '%s', '%s', '%s', '%d']
		);

		if ($insert === false) {
			$result['errors'][] = sprintf(__('Failed to insert call row %1$d old_id %2$d.', 'tender-a-library'), $row_number, $old_id);
			continue;
		}

		$result['created']++;
	}

	return $result;
}

function tal_migration_import_sections($path, $dry_run = false)
{
	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$map = tal_migration_build_term_old_id_map('tender_section');
	$created = 0;
	$updated = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		$name = $row['name'] ?? '';
		if (!$old_id || !$name) {
			continue;
		}

		if (isset($map[$old_id])) {
			$term_id = $map[$old_id];
			$updated++;
		} else {
			$existing = term_exists($name, 'tender_section');
			if ($existing) {
				$term_id = (int) (is_array($existing) ? $existing['term_id'] : $existing);
				$updated++;
				if (!$dry_run) {
					update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
					$map[$old_id] = $term_id;
				}
			} else {
				if ($dry_run) {
					$created++;
					continue;
				}

				$insert = wp_insert_term($name, 'tender_section');
				if (is_wp_error($insert)) {
					if ($insert->get_error_code() === 'term_exists') {
						$term_id = (int) $insert->get_error_data();
					} else {
						$errors[] = sprintf('Section %s: %s', $name, $insert->get_error_message());
						continue;
					}
				} else {
					$term_id = (int) $insert['term_id'];
					$created++;
				}

				update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
				$map[$old_id] = $term_id;
			}
		}

		$section_number = tal_migration_first_non_empty($row, ['section_number', 'number']);
		if ($section_number !== '' && !$dry_run) {
			tal_migration_set_section_number($term_id, $section_number);
		}
	}

	// Second pass to attach parents
	if (!$dry_run) {
		foreach ($rows as $row) {
			$old_id = (int) ($row['id'] ?? 0);
		$parent_old = (int) ($row['parent_id'] ?? $row['section_id'] ?? 0);
			if (!$old_id || !$parent_old) {
				continue;
			}
			if (!isset($map[$old_id], $map[$parent_old])) {
				continue;
			}
			wp_update_term($map[$old_id], 'tender_section', ['parent' => $map[$parent_old]]);
		}
	}

	return [
		'messages' => [sprintf(__('Sections imported. Created: %d, updated: %d.', 'tender-a-library'), $created, $updated)],
		'errors' => $errors,
	];
}

function tal_migration_set_section_number($term_id, $number)
{
	if (function_exists('carbon_set_term_meta')) {
		carbon_set_term_meta($term_id, 'tender_section_number', $number);
		return;
	}
	update_term_meta($term_id, 'tender_section_number', $number);
}

function tal_migration_import_languages($path, $dry_run = false)
{
	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$map = tal_migration_build_term_old_id_map('tender_language');
	$created = 0;
	$updated = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		$name = $row['language'] ?? '';
		if (!$old_id || !$name) {
			continue;
		}

		if (isset($map[$old_id])) {
			$term_id = $map[$old_id];
			$updated++;
		} else {
			$existing = term_exists($name, 'tender_language');
			if ($existing) {
				$term_id = (int) (is_array($existing) ? $existing['term_id'] : $existing);
				$updated++;
				if (!$dry_run) {
					update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
					$map[$old_id] = $term_id;
				}
			} else {
				if ($dry_run) {
					$created++;
					continue;
				}

				$insert = wp_insert_term($name, 'tender_language');
				if (is_wp_error($insert)) {
					if ($insert->get_error_code() === 'term_exists') {
						$term_id = (int) $insert->get_error_data();
					} else {
						$errors[] = sprintf('Language %s: %s', $name, $insert->get_error_message());
						continue;
					}
				} else {
					$term_id = (int) $insert['term_id'];
					$created++;
				}

				update_term_meta($term_id, TAL_MIGRATION_META_KEY, $old_id);
				$map[$old_id] = $term_id;
			}
		}
	}

	return [
		'messages' => [sprintf(__('Languages imported. Created: %d, updated: %d.', 'tender-a-library'), $created, $updated)],
		'errors' => $errors,
	];
}

function tal_migration_import_media($path, $media_base, $dry_run = false)
{
	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$media_base = rtrim((string) $media_base, '/');
	$map = tal_migration_build_post_old_id_map('attachment');
	$created = 0;
	$skipped = 0;
	$errors = [];

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id || isset($map[$old_id])) {
			$skipped++;
			continue;
		}

		$media_url = $row['url'] ?? '';
		$relative_path = ltrim($row['path'] ?? '', '/');
		$file_name = $row['file_name'] ?? '';
		$title = $row['title'] ?: ($file_name ? pathinfo($file_name, PATHINFO_FILENAME) : '');
		$mime = $row['mime_type'] ?? '';

		if (!$media_url) {
			if (!$file_name || empty($media_base)) {
				$errors[] = sprintf(__('Media %d missing URL or file path.', 'tender-a-library'), $old_id);
				continue;
			}
			$source_path = $media_base . '/' . $relative_path . $file_name;
			if (!file_exists($source_path)) {
				$errors[] = sprintf(__('Missing media file: %s', 'tender-a-library'), $source_path);
				continue;
			}
		}

		if ($dry_run) {
			$created++;
			continue;
		}

		if ($media_url) {
			$attachment_id = tal_migration_create_attachment_from_url($media_url, $title, $mime);
		} else {
			$attachment_id = tal_migration_create_attachment_from_path(
				$source_path,
				$title,
				$mime
			);
		}

		if (is_wp_error($attachment_id)) {
			$errors[] = $attachment_id->get_error_message();
			continue;
		}

		update_post_meta($attachment_id, TAL_MIGRATION_META_KEY, $old_id);
		if (!empty($row['alt'])) {
			update_post_meta($attachment_id, '_wp_attachment_image_alt', $row['alt']);
		}
		$map[$old_id] = $attachment_id;
		$created++;
	}

	return [
		'messages' => [
			sprintf(__('Images imported. Created: %d, skipped: %d.', 'tender-a-library'), $created, $skipped),
		],
		'errors' => $errors,
	];
}

function tal_migration_create_attachment_from_path($source_path, $title, $mime_type)
{
	$existing_attachment_id = tal_migration_find_attachment_by_file_signature(basename($source_path), @filesize($source_path));
	if ($existing_attachment_id) {
		return $existing_attachment_id;
	}

	$uploads = wp_upload_dir();
	if (!empty($uploads['error'])) {
		return new WP_Error('upload_dir_error', $uploads['error']);
	}

	wp_mkdir_p($uploads['path']);
	$filename = wp_unique_filename($uploads['path'], basename($source_path));
	$destination = trailingslashit($uploads['path']) . $filename;

	if (!copy($source_path, $destination)) {
		return new WP_Error('copy_failed', sprintf(__('Could not copy file: %s', 'tender-a-library'), $source_path));
	}

	$attachment = [
		'post_mime_type' => $mime_type ?: wp_check_filetype($destination)['type'],
		'post_title' => $title,
		'post_content' => '',
		'post_status' => 'inherit',
	];

	$attachment_id = wp_insert_attachment($attachment, $destination);
	if (is_wp_error($attachment_id)) {
		return $attachment_id;
	}

	$metadata = wp_generate_attachment_metadata($attachment_id, $destination);
	if ($metadata) {
		wp_update_attachment_metadata($attachment_id, $metadata);
	}

	return $attachment_id;
}

function tal_migration_create_attachment_from_url($url, $title, $mime_type)
{
	$tmp = download_url($url);
	if (is_wp_error($tmp)) {
		return $tmp;
	}

	$filename = basename(parse_url($url, PHP_URL_PATH));
	$existing_attachment_id = tal_migration_find_attachment_by_file_signature($filename, @filesize($tmp));
	if ($existing_attachment_id) {
		@unlink($tmp);
		return $existing_attachment_id;
	}

	$file_array = [
		'name' => $filename,
		'tmp_name' => $tmp,
	];

	$attachment_id = media_handle_sideload($file_array, 0, $title);
	if (is_wp_error($attachment_id)) {
		@unlink($tmp);
		return $attachment_id;
	}

	if ($mime_type) {
		wp_update_post([
			'ID' => $attachment_id,
			'post_mime_type' => $mime_type,
		]);
	}

	return $attachment_id;
}

function tal_migration_normalize_source_url($url)
{
	$url = trim((string) $url);
	if ($url === '') {
		return '';
	}

	$parts = wp_parse_url($url);
	if (!is_array($parts) || empty($parts['host'])) {
		return $url;
	}

	$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
	$host = strtolower($parts['host']);
	$port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
	$path = isset($parts['path']) ? preg_replace('#/+#', '/', $parts['path']) : '';
	$query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

	return $scheme . '://' . $host . $port . $path . $query;
}

function tal_migration_find_attachment_by_source_url($url)
{
	global $wpdb;

	$normalized_url = tal_migration_normalize_source_url($url);
	if ($normalized_url === '') {
		return 0;
	}

	$attachment_id = $wpdb->get_var($wpdb->prepare(
		"SELECT pm.post_id
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = %s
		LIMIT 1",
		TAL_MIGRATION_ATTACHMENT_SOURCE_URL_META_KEY,
		$normalized_url,
		'attachment'
	));

	return $attachment_id ? (int) $attachment_id : 0;
}

function tal_migration_find_attachment_by_file_signature($filename, $filesize)
{
	global $wpdb;

	$filename = basename((string) $filename);
	$filesize = (int) $filesize;

	if ($filename === '' || $filesize <= 0) {
		return 0;
	}

	$candidates = $wpdb->get_col($wpdb->prepare(
		"SELECT pm.post_id
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = %s
			AND p.post_type = %s
			AND (pm.meta_value = %s OR pm.meta_value LIKE %s)",
		'_wp_attached_file',
		'attachment',
		$filename,
		'%/' . $wpdb->esc_like($filename)
	));

	foreach ($candidates as $attachment_id) {
		$attachment_id = (int) $attachment_id;
		if ($attachment_id <= 0) {
			continue;
		}

		$attached_file = get_attached_file($attachment_id);
		if (!$attached_file || !file_exists($attached_file)) {
			continue;
		}

		if (basename($attached_file) === $filename && (int) @filesize($attached_file) === $filesize) {
			return $attachment_id;
		}
	}

	return 0;
}

function tal_migration_import_users($path, $dry_run = false)
{
	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$map = tal_migration_build_user_old_id_map();
	$created = 0;
	$updated = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		if (isset($map[$old_id])) {
			$updated++;
			continue;
		}

		$email = $row['email'] ?? '';
		$name = $row['name'] ?? '';
		$role = tal_migration_map_legacy_role($row['role'] ?? '');

		$existing_user = $email ? get_user_by('email', $email) : false;
		if ($existing_user) {
			if (!$dry_run) {
				update_user_meta($existing_user->ID, TAL_MIGRATION_META_KEY, $old_id);
				$map[$old_id] = $existing_user->ID;
			}
			$updated++;
			continue;
		}

		$user_login = $email ? sanitize_user($email, true) : 'legacy_user_' . $old_id;
		if (username_exists($user_login)) {
			$user_login = $user_login . '_' . $old_id;
		}

		if (!$email) {
			$email = 'legacy_user_' . $old_id . '@example.local';
		}

		if ($dry_run) {
			$created++;
			continue;
		}

		$user_id = wp_insert_user([
			'user_login' => $user_login,
			'user_pass' => wp_generate_password(20, true, true),
			'user_email' => $email,
			'display_name' => $name ?: $user_login,
			'role' => $role,
		]);

		if (is_wp_error($user_id)) {
			$errors[] = sprintf('User %s: %s', $email, $user_id->get_error_message());
			continue;
		}

		update_user_meta($user_id, TAL_MIGRATION_META_KEY, $old_id);
		if (!empty($row['phone'])) {
			update_user_meta($user_id, 'phone_number', $row['phone']);
		}
		$map[$old_id] = $user_id;
		$created++;
	}

	return [
		'messages' => [sprintf(__('Users imported. Created: %d, updated: %d.', 'tender-a-library'), $created, $updated)],
		'errors' => $errors,
	];
}

function tal_migration_import_books($path, $media_base = '', $dry_run = false)
{
	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$book_map = tal_migration_build_post_old_id_map('tender_book');
	$lang_map = tal_migration_build_term_old_id_map('tender_language');
	$section_lookup = tal_migration_build_section_lookup();
	$section_old_id_map = tal_migration_build_term_old_id_map('tender_section');

	$created = 0;
	$updated = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$section_id = tal_migration_resolve_book_section($row, $section_lookup, $section_old_id_map);
		if (is_wp_error($section_id)) {
			$errors[] = sprintf(__('Book %d section error: %s', 'tender-a-library'), $old_id, $section_id->get_error_message());
			continue;
		}

		$lang_id = tal_migration_resolve_book_language($row, $lang_map, $dry_run);
		if (is_wp_error($lang_id)) {
			$errors[] = sprintf(__('Book %d language error: %s', 'tender-a-library'), $old_id, $lang_id->get_error_message());
			continue;
		}

		if ($dry_run) {
			if (isset($book_map[$old_id])) {
				$updated++;
			} else {
				$created++;
			}
			continue;
		}

		$title = $row['title'] ?? '';
		$post_args = [
			'post_type' => 'tender_book',
			'post_title' => $title ?: __('Untitled book', 'tender-a-library'),
			'post_status' => 'publish',
			'post_date' => !empty($row['created_at']) ? $row['created_at'] : current_time('mysql'),
			'post_modified' => !empty($row['updated_at']) ? $row['updated_at'] : current_time('mysql'),
		];

		$is_update = isset($book_map[$old_id]);
		if ($is_update) {
			$post_args['ID'] = $book_map[$old_id];
		}

		$post_id = wp_insert_post($post_args, true);

		if (is_wp_error($post_id)) {
			$errors[] = sprintf('Book %s: %s', $title, $post_id->get_error_message());
			continue;
		}

		update_post_meta($post_id, TAL_MIGRATION_META_KEY, $old_id);
		tal_migration_set_book_meta($post_id, $row, $section_id, $lang_id);

		$attachment_id = tal_migration_import_book_cover($row, $media_base, $dry_run);
		if (is_wp_error($attachment_id)) {
			$errors[] = sprintf(__('Book %d cover error: %s', 'tender-a-library'), $old_id, $attachment_id->get_error_message());
		} elseif ($attachment_id) {
			tal_migration_set_book_cover($post_id, (int) $attachment_id);
		}

		$book_map[$old_id] = $post_id;
		if ($is_update) {
			$updated++;
		} else {
			$created++;
		}
	}

	return [
		'messages' => [sprintf(__('Books imported. Created: %d, updated: %d.', 'tender-a-library'), $created, $updated)],
		'errors' => $errors,
	];
}

function tal_migration_set_book_meta($post_id, $row, $section_id, $lang_id)
{
	$summary = tal_migration_select_book_summary($row);

	if (function_exists('carbon_set_post_meta')) {
		carbon_set_post_meta($post_id, 'tender_book_author', $row['author'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_other_authors', $row['other_authors'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_publisher', $row['publisher'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_units', $row['quantity'] ?? 1);
		carbon_set_post_meta($post_id, 'tender_book_year', $row['year'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_edition', $row['edition'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_isbn', $row['isbn'] ?? '');
		carbon_set_post_meta($post_id, 'tender_book_excerpt', $summary);
		carbon_set_post_meta($post_id, 'tender_book_review', '');
		if (!empty($row['sig1'])) {
			carbon_set_post_meta($post_id, 'tender_book_sig1', $row['sig1']);
		}
		if (!empty($row['sig2'])) {
			carbon_set_post_meta($post_id, 'tender_book_sig2', $row['sig2']);
		}
	}

	if ($section_id) {
		wp_set_object_terms($post_id, [$section_id], 'tender_section');
		if (function_exists('carbon_set_post_meta')) {
			carbon_set_post_meta($post_id, 'tender_book_section', [
				[
					'id' => $section_id,
					'type' => 'term',
					'subtype' => 'tender_section',
				],
			]);
		}
	}

	if ($lang_id) {
		wp_set_object_terms($post_id, [$lang_id], 'tender_language');
		if (function_exists('carbon_set_post_meta')) {
			carbon_set_post_meta($post_id, 'tender_book_language', [
				[
					'id' => $lang_id,
					'type' => 'term',
					'subtype' => 'tender_language',
				],
			]);
		}
	}
}

function tal_migration_select_book_summary($row)
{
	$description = tal_migration_first_non_empty($row, ['description']);
	if ($description !== '') {
		return $description;
	}

	return tal_migration_first_non_empty($row, ['review']);
}

function tal_migration_set_book_cover($post_id, $attachment_id)
{
	if (function_exists('carbon_set_post_meta')) {
		carbon_set_post_meta($post_id, 'tender_book_cover', $attachment_id);
	}
	set_post_thumbnail($post_id, $attachment_id);
}

function tal_migration_resolve_book_language($row, &$lang_map, $dry_run = false)
{
	$lang_old = (int) ($row['lang_id'] ?? 0);
	if ($lang_old && isset($lang_map[$lang_old])) {
		return $lang_map[$lang_old];
	}

	$name = tal_migration_first_non_empty($row, ['language_name', 'language', 'lang']);
	if ($name === '') {
		if ($lang_old) {
			return new WP_Error('missing_language_mapping', __('Missing language mapping.', 'tender-a-library'));
		}
		return new WP_Error('missing_language_name', __('Missing language_name.', 'tender-a-library'));
	}

	$existing = term_exists($name, 'tender_language');
	if ($existing) {
		$term_id = (int) (is_array($existing) ? $existing['term_id'] : $existing);
		if ($lang_old && !$dry_run) {
			update_term_meta($term_id, TAL_MIGRATION_META_KEY, $lang_old);
			$lang_map[$lang_old] = $term_id;
		}
		return $term_id;
	}

	if ($dry_run) {
		return 0;
	}

	$insert = wp_insert_term($name, 'tender_language');
	if (is_wp_error($insert)) {
		return $insert;
	}

	$term_id = (int) $insert['term_id'];
	if ($lang_old) {
		update_term_meta($term_id, TAL_MIGRATION_META_KEY, $lang_old);
		$lang_map[$lang_old] = $term_id;
	}

	return $term_id;
}

function tal_migration_resolve_book_section($row, $section_lookup, $section_old_id_map = [])
{
	$section_old_id = (int) ($row['section_id'] ?? 0);
	if ($section_old_id && isset($section_old_id_map[$section_old_id])) {
		return (int) $section_old_id_map[$section_old_id];
	}

	$section_name = tal_migration_first_non_empty($row, ['section_name']);
	$number = tal_migration_first_non_empty($row, ['section_number']);

	if ($section_name === '' || $number === '') {
		if ($section_old_id) {
			return new WP_Error('missing_section_mapping', __('No imported section matches the provided section_id.', 'tender-a-library'));
		}
		return tal_migration_get_or_create_default_section();
	}

	$key = tal_migration_get_section_lookup_key($section_name, $number);
	if (!isset($section_lookup[$key])) {
		return new WP_Error('missing_section_mapping', __('No imported section matches the provided section_name and section_number.', 'tender-a-library'));
	}

	return (int) $section_lookup[$key];
}

function tal_migration_get_or_create_default_section()
{
	$existing = term_exists(TAL_MIGRATION_DEFAULT_SECTION_NAME, 'tender_section');
	if ($existing) {
		return (int) (is_array($existing) ? $existing['term_id'] : $existing);
	}

	$insert = wp_insert_term(TAL_MIGRATION_DEFAULT_SECTION_NAME, 'tender_section');
	if (is_wp_error($insert)) {
		if ($insert->get_error_code() === 'term_exists') {
			return (int) $insert->get_error_data();
		}
		return $insert;
	}

	return (int) $insert['term_id'];
}

function tal_migration_build_section_lookup()
{
	$terms = get_terms([
		'taxonomy' => 'tender_section',
		'hide_empty' => false,
	]);

	$lookup = [];
	if (is_wp_error($terms)) {
		return $lookup;
	}

	foreach ($terms as $term) {
		$number = function_exists('carbon_get_term_meta')
			? (string) carbon_get_term_meta($term->term_id, 'tender_section_number')
			: '';
		if ($number === '') {
			$number = (string) get_term_meta($term->term_id, 'tender_section_number', true);
		}

		if ($number === '') {
			continue;
		}

		$key = tal_migration_get_section_lookup_key($term->name, $number);
		$lookup[$key] = (int) $term->term_id;
	}

	return $lookup;
}

function tal_migration_get_section_lookup_key($name, $number)
{
	return sanitize_title((string) $name) . '|' . trim((string) $number);
}

function tal_migration_import_book_cover($row, $media_base = '', $dry_run = false)
{
	$cover_url = tal_migration_first_non_empty($row, ['cover_url', 'image_url', 'media_url', 'image']);
	if ($cover_url !== '' && wp_http_validate_url($cover_url)) {
		$normalized_url = tal_migration_normalize_source_url($cover_url);
		$existing_attachment_id = tal_migration_find_attachment_by_source_url($normalized_url);
		if ($existing_attachment_id) {
			return $existing_attachment_id;
		}

		if ($dry_run) {
			return 0;
		}

		$title = tal_migration_first_non_empty($row, ['title', 'cover_title']);
		$attachment_id = tal_migration_create_attachment_from_url($normalized_url, $title, '');
		if (!is_wp_error($attachment_id) && $attachment_id) {
			update_post_meta($attachment_id, TAL_MIGRATION_ATTACHMENT_SOURCE_URL_META_KEY, $normalized_url);
		}

		return $attachment_id;
	}

	$relative_path = ltrim(tal_migration_first_non_empty($row, ['cover_path', 'image_path', 'media_path']), '/');
	if ($relative_path === '' || $media_base === '') {
		return 0;
	}

	$source_path = trailingslashit(rtrim((string) $media_base, '/')) . $relative_path;
	if (!file_exists($source_path)) {
		return new WP_Error('missing_cover_file', sprintf(__('Missing media file: %s', 'tender-a-library'), $source_path));
	}

	if ($dry_run) {
		return 0;
	}

	$title = tal_migration_first_non_empty($row, ['title', 'cover_title']);
	return tal_migration_create_attachment_from_path($source_path, $title, '');
}

function tal_migration_first_non_empty($row, $keys)
{
	foreach ($keys as $key) {
		if (!isset($row[$key])) {
			continue;
		}

		$value = tal_migration_normalize_value($row[$key]);
		if ($value !== null && $value !== '') {
			return (string) $value;
		}
	}

	return '';
}

function tal_migration_import_lendings($path, $dry_run = false)
{
	global $wpdb;

	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$book_map = tal_migration_build_post_old_id_map('tender_book');
	$user_map = tal_migration_build_user_old_id_map();
	$created = 0;
	$skipped = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . TENDER_TABLE_LENDINGS . " WHERE old_laravel_id = %d",
			$old_id
		));
		if ($exists) {
			$skipped++;
			continue;
		}

		$book_old = (int) ($row['book_id'] ?? 0);
		$user_old = (int) ($row['user_id'] ?? 0);
		if (!isset($book_map[$book_old], $user_map[$user_old])) {
			$errors[] = sprintf(__('Lending %d missing book/user mapping.', 'tender-a-library'), $old_id);
			continue;
		}

		if ($dry_run) {
			$created++;
			continue;
		}

		$insert = $wpdb->insert(
			TENDER_TABLE_LENDINGS,
			[
				'book_id' => $book_map[$book_old],
				'user_id' => $user_map[$user_old],
				'lending_date' => $row['lending_date'] ?? current_time('mysql'),
				'stimated_return_date' => $row['stimated_return_date'] ?? current_time('mysql'),
				'real_return_date' => $row['real_return_date'] ?? null,
				'returned' => (int) ($row['returned'] ?? 0),
				'extensions' => (int) ($row['extensions'] ?? 0),
				'extension_date' => $row['extension_date'] ?? null,
				'old_laravel_id' => $old_id,
			],
			['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d']
		);

		if ($insert === false) {
			$errors[] = sprintf(__('Failed to insert lending %d.', 'tender-a-library'), $old_id);
			continue;
		}

		$created++;
	}

	return [
		'messages' => [sprintf(__('Lendings imported. Created: %d, skipped: %d.', 'tender-a-library'), $created, $skipped)],
		'errors' => $errors,
	];
}

function tal_migration_import_calls($path, $dry_run = false)
{
	global $wpdb;

	$rows = tal_migration_read_csv($path);
	if (is_wp_error($rows)) {
		return ['messages' => [], 'errors' => [$rows->get_error_message()]];
	}

	$user_map = tal_migration_build_user_old_id_map();
	$created = 0;
	$skipped = 0;
	$errors = [];

	foreach ($rows as $row) {
		$old_id = (int) ($row['id'] ?? 0);
		if (!$old_id) {
			continue;
		}

		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM " . TENDER_TABLE_USER_CALLS . " WHERE old_laravel_id = %d",
			$old_id
		));
		if ($exists) {
			$skipped++;
			continue;
		}

		$user_old = (int) ($row['user_id'] ?? 0);
		if (!isset($user_map[$user_old])) {
			$errors[] = sprintf(__('Call %d missing user mapping.', 'tender-a-library'), $old_id);
			continue;
		}

		if ($dry_run) {
			$created++;
			continue;
		}

		$created_at = $row['created_at'] ?? current_time('mysql');
		$call_date = $created_at ? substr($created_at, 0, 10) : current_time('Y-m-d');

		$insert = $wpdb->insert(
			TENDER_TABLE_USER_CALLS,
			[
				'user_id' => $user_map[$user_old],
				'subject' => $row['subject'] ?? '',
				'comment' => $row['comment'] ?? null,
				'call_date' => $call_date,
				'created_at' => $created_at,
				'updated_at' => $row['updated_at'] ?? $created_at,
				'old_laravel_id' => $old_id,
			],
			['%d', '%s', '%s', '%s', '%s', '%s', '%d']
		);

		if ($insert === false) {
			$errors[] = sprintf(__('Failed to insert call %d.', 'tender-a-library'), $old_id);
			continue;
		}

		$created++;
	}

	return [
		'messages' => [sprintf(__('Calls imported. Created: %d, skipped: %d.', 'tender-a-library'), $created, $skipped)],
		'errors' => $errors,
	];
}

function tal_migration_build_term_old_id_map($taxonomy)
{
	$terms = get_terms([
		'taxonomy' => $taxonomy,
		'hide_empty' => false,
	]);

	$map = [];
	foreach ($terms as $term) {
		$old_id = (int) get_term_meta($term->term_id, TAL_MIGRATION_META_KEY, true);
		if ($old_id) {
			$map[$old_id] = (int) $term->term_id;
		}
	}

	return $map;
}

function tal_migration_build_post_old_id_map($post_type)
{
	global $wpdb;

	$map = [];
	$results = $wpdb->get_results($wpdb->prepare(
		"SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s",
		TAL_MIGRATION_META_KEY,
		$post_type
	));

	foreach ($results as $row) {
		$old_id = (int) $row->meta_value;
		if ($old_id) {
			$map[$old_id] = (int) $row->post_id;
		}
	}

	return $map;
}

function tal_migration_build_user_old_id_map()
{
	global $wpdb;
	$map = [];
	$results = $wpdb->get_results($wpdb->prepare(
		"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
		TAL_MIGRATION_META_KEY
	));

	foreach ($results as $row) {
		$old_id = (int) $row->meta_value;
		if ($old_id) {
			$map[$old_id] = (int) $row->user_id;
		}
	}

	return $map;
}

function tal_migration_map_legacy_role($legacy_role)
{
	$map = [
		'0' => 'administrator',
		'1' => 'reader',
		'2' => 'librarian',
	];

	$legacy_role = is_scalar($legacy_role) ? (string) $legacy_role : '';
	$mapped = $map[$legacy_role] ?? 'reader';

	return apply_filters('tal_migration_role_map', $mapped, $legacy_role);
}
