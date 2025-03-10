<?php
// Archivo: tender-admin-pages.php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Función genérica para renderizar una página de préstamos
 *
 * @param array $args {
 *     Parámetros para configurar la página.
 *
 *     @type string   $title                Título de la página.
 *     @type string   $ajax_action          Nombre de la acción AJAX a llamar para cargar los datos.
 *     @type callable $render_table_callback Callback para renderizar la tabla de resultados (se usará en la respuesta AJAX).
 *     @type bool     $show_actions         Si se deben mostrar botones de "Devolver" y "Renovar". (Solo para préstamos activos)
 * }
 */
function tender_render_lending_page($args)
{
	$title                = isset($args['title']) ? $args['title'] : 'Préstamos';
	$ajax_action          = isset($args['ajax_action']) ? $args['ajax_action'] : '';
	$render_table_callback = isset($args['render_table_callback']) ? $args['render_table_callback'] : '';
	$show_actions         = isset($args['show_actions']) ? $args['show_actions'] : false;
?>
	<div class="wrap">
		<h1><?php echo esc_html($title); ?></h1>
		<input type="text" id="search-lendings" placeholder="Buscar por usuario o libro..." style="width: 300px;">
		<div id="lendings-list"></div>
		<?php if ($show_actions) : ?>
			<!-- Modal de confirmación -->
			<div id="confirmation-modal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
				<div class="w-full max-w-sm p-6 bg-white rounded-lg">
					<h3 id="modal-message" class="mb-4 text-lg font-semibold"></h3>
					<button id="confirm-action" class="px-4 py-2 mr-2 text-white bg-green-500 rounded">Confirmar</button>
					<button id="cancel-action" class="px-4 py-2 text-white bg-gray-500 rounded">Cancelar</button>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<script>
		jQuery(document).ready(function($) {
			// Función para cargar los préstamos mediante AJAX
			function loadLendings(query = '', page = 1) {
				$('#lendings-list').html('<div class="p-4 text-center">Cargando...</div>');

				$.post(ajaxurl, {
					action: '<?php echo esc_js($ajax_action); ?>',
					query: query,
					page: page
				}, function(response) {
					$('#lendings-list').html(response);
					window.scrollTo({
						top: 0,
						behavior: 'smooth'
					});
					updateUrlParameters(query, page);
				}).fail(function(jqXHR, textStatus, errorThrown) {
					let errorMsg = 'Error cargando los datos';
					if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
						errorMsg += ': ' + jqXHR.responseJSON.data;
					}
					console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
					$('#lendings-list').html('<p class="text-red-500">' + errorMsg + '</p>');
				});
			}

			// Actualiza los parámetros GET en la URL
			function updateUrlParameters(query, page) {
				const url = new URL(window.location.href);
				if (query) {
					url.searchParams.set('query', query);
				} else {
					url.searchParams.delete('query');
				}
				url.searchParams.set('tender-page', page);
				window.history.pushState({
					path: url.href
				}, '', url.href);
			}

			// Obtener parámetros de la URL
			function getParametersFromUrl() {
				const urlParams = new URLSearchParams(window.location.search);
				return {
					query: urlParams.get('query') || '',
					page: urlParams.get('tender-page') || 1
				};
			}

			// Cargar préstamos al iniciar la página
			const {
				query,
				page
			} = getParametersFromUrl();
			$('#search-lendings').val(query);
			loadLendings(query, parseInt(page));

			// Búsqueda en tiempo real
			let searchTimer;
			$('#search-lendings').on('keyup', function() {
				clearTimeout(searchTimer);
				searchTimer = setTimeout(() => {
					loadLendings($(this).val(), 1);
				}, 300);
			});

			// Delegación para la paginación
			$(document).on('click', '#pagination-links a', function(e) {
				e.preventDefault();
				let page = $(this).data('page');
				let query = $('#search-lendings').val();
				loadLendings(query, page);
			});

			<?php if ($show_actions) : ?>
				// Delegación para los botones de acción
				$(document).on('click', '.lending-return, .lending-renew', function() {
					let lendingId = $(this).closest('tr').attr('id');
					let action = $(this).data('action');
					let message = (action === 'return') ? '¿Estás seguro de que quieres devolver este libro?' : '¿Estás seguro de que quieres renovar este préstamo?';
					$('#confirmation-modal').show();
					$('#modal-message').text(message);
					$('#confirm-action').data('lending-id', lendingId).data('action', action);
				});

				// Confirmar acción en el modal
				$(document).on('click', '#confirm-action', function() {
					let lendingId = $(this).data('lending-id');
					let action = $(this).data('action');

					$.post(ajaxurl, {
						action: 'tender_handle_lending_action',
						lending_id: lendingId,
						action_type: action
					}, function(response) {
						$('#confirmation-modal').hide();
						loadLendings($('#search-lendings').val(), 1);
					});
				});

				// Cancelar acción en el modal
				$(document).on('click', '#cancel-action', function() {
					$('#confirmation-modal').hide();
				});
			<?php endif; ?>
		});
	</script>
<?php
}

/**
 * Página de préstamos activos
 */
function tender_lendings_page()
{
	tender_render_lending_page(array(
		'title'                 => 'Préstamos Activos',
		'ajax_action'           => 'tender_search_lendings',
		'render_table_callback' => 'render_lendings_table',
		'show_actions'          => true,
	));
}

/**
 * Página de préstamos finalizados
 */
function tender_old_lendings_page()
{
	tender_render_lending_page(array(
		'title'                 => 'Préstamos Finalizados',
		'ajax_action'           => 'tender_search_old_lendings',
		'render_table_callback' => 'render_old_lendings_table',
		'show_actions'          => false,
	));
}

/* =============================================================================
   A partir de aquí se encuentran las funciones AJAX y de renderizado de tablas.
   Muchas de ellas se han dejado igual, ya que su lógica (SQL, etc.) es específica.
   Se pueden unificar en función de las diferencias en la consulta (por ejemplo, el estado
   del préstamo) si se desea, o mantener separadas para mayor claridad.
   ============================================================================= */

/**
 * AJAX: Buscar préstamos activos.
 */
function tender_search_lendings()
{
	if (! defined('ABSPATH')) {
		wp_send_json_error('Acceso no autorizado');
		wp_die();
	}

	global $wpdb;

	$query    = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
	$page     = max(1, isset($_POST['page']) ? intval($_POST['page']) : 1);
	$per_page = 25;

	$sql    = "SELECT l.id, u.ID as user_id, u.user_nicename, u.display_name, b.post_title, l.lending_date, l.stimated_return_date
               FROM {$wpdb->prefix}tender_lendings l
               JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
               JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
               WHERE l.returned = 0";
	$params = array();

	if (! empty($query)) {
		$sql      .= " AND (b.post_title LIKE %s OR u.display_name LIKE %s)";
		$like_term = '%' . $wpdb->esc_like($query) . '%';
		$params[]  = $like_term;
		$params[]  = $like_term;
	}

	$sql      .= " ORDER BY l.stimated_return_date DESC LIMIT %d OFFSET %d";
	$params[]  = $per_page;
	$params[]  = ($page - 1) * $per_page;

	$prepared_sql = $wpdb->prepare($sql, $params);
	$results      = $wpdb->get_results($prepared_sql);

	if ($wpdb->last_error) {
		wp_send_json_error('Database error: ' . $wpdb->last_error);
		wp_die();
	}

	// Consulta para obtener el total
	$count_sql    = "SELECT COUNT(*)
                     FROM {$wpdb->prefix}tender_lendings l
                     JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
                     JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
                     WHERE l.returned = 0";
	$count_params = array();

	if (! empty($query)) {
		$count_sql     .= " AND (b.post_title LIKE %s OR u.display_name LIKE %s)";
		$count_params[] = $like_term;
		$count_params[] = $like_term;
	}

	$prepared_count_sql = $wpdb->prepare($count_sql, $count_params);
	$total_items        = $wpdb->get_var($prepared_count_sql);
	$total_pages        = ceil($total_items / $per_page);

	if ($results) {
		echo render_lendings_table($results);
		echo render_pagination($page, $total_pages);
	} else {
		echo '<p class="text-gray-500">' . esc_html__('No active loans found.', 'tender-plugin') . '</p>';
	}

	wp_die();
}
add_action('wp_ajax_tender_search_lendings', 'tender_search_lendings');

/**
 * AJAX: Buscar préstamos finalizados.
 */
function tender_search_old_lendings()
{
	if (! defined('ABSPATH')) {
		wp_send_json_error('Acceso no autorizado');
		wp_die();
	}

	global $wpdb;

	$query    = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
	$page     = max(1, isset($_POST['page']) ? intval($_POST['page']) : 1);
	$per_page = 25;

	$sql    = "SELECT l.id, u.ID as user_id, u.user_nicename, u.display_name, b.post_title, l.lending_date, l.stimated_return_date, l.extensions as renewals
               FROM {$wpdb->prefix}tender_lendings l
               JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
               JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
               WHERE l.returned = 1";
	$params = array();

	if (! empty($query)) {
		$sql      .= " AND (b.post_title LIKE %s OR u.display_name LIKE %s)";
		$like_term = '%' . $wpdb->esc_like($query) . '%';
		$params[]  = $like_term;
		$params[]  = $like_term;
	}

	$sql      .= " ORDER BY l.stimated_return_date DESC LIMIT %d OFFSET %d";
	$params[]  = $per_page;
	$params[]  = ($page - 1) * $per_page;

	$prepared_sql = $wpdb->prepare($sql, $params);
	$results      = $wpdb->get_results($prepared_sql);

	if ($wpdb->last_error) {
		wp_send_json_error('Database error: ' . $wpdb->last_error);
		wp_die();
	}

	$count_sql    = "SELECT COUNT(*)
                     FROM {$wpdb->prefix}tender_lendings l
                     JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
                     JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
                     WHERE l.returned = 1";
	$count_params = array();

	if (! empty($query)) {
		$count_sql     .= " AND (b.post_title LIKE %s OR u.display_name LIKE %s)";
		$count_params[] = $like_term;
		$count_params[] = $like_term;
	}

	$prepared_count_sql = $wpdb->prepare($count_sql, $count_params);
	$total_items        = $wpdb->get_var($prepared_count_sql);
	$total_pages        = ceil($total_items / $per_page);

	if ($results) {
		echo render_old_lendings_table($results);
		echo render_pagination($page, $total_pages);
	} else {
		echo '<p class="text-gray-500">' . esc_html__('No active loans found.', 'tender-plugin') . '</p>';
	}

	wp_die();
}
add_action('wp_ajax_tender_search_old_lendings', 'tender_search_old_lendings');

/**
 * Renderizar la paginación.
 */
function render_pagination($current_page, $total_pages)
{
	ob_start();
	$max_pages_to_show = 5;
	$half_max         = floor($max_pages_to_show / 2);
	$start_page       = max(1, $current_page - $half_max);
	$end_page         = min($total_pages, $current_page + $half_max);
	if ($start_page === 1) {
		$end_page = min($max_pages_to_show, $total_pages);
	}
	if ($end_page === $total_pages) {
		$start_page = max(1, $total_pages - $max_pages_to_show + 1);
	}
?>
	<div class="flex justify-center mt-4 space-x-2 pagination" id="pagination-links">
		<?php if ($current_page > 1) : ?>
			<a href="#" data-page="<?php echo $current_page - 1; ?>" class="flex items-center px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
				« <?php _e('Previous', 'tender-plugin'); ?>
			</a>
		<?php endif; ?>

		<?php if ($start_page > 1) : ?>
			<a href="#" data-page="1" class="flex items-center px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">1</a>
			<?php if ($start_page > 2) : ?>
				<span class="px-3 py-1">...</span>
			<?php endif; ?>
		<?php endif; ?>

		<?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
			<a href="#" data-page="<?php echo $i; ?>" class="flex items-center px-3 py-1 rounded <?php echo ($i == $current_page) ? 'bg-blue-500 !text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
				<?php echo $i; ?>
			</a>
		<?php endfor; ?>

		<?php if ($end_page < $total_pages) : ?>
			<?php if ($end_page < $total_pages - 1) : ?>
				<span class="px-3 py-1">...</span>
			<?php endif; ?>
			<a href="#" data-page="<?php echo $total_pages; ?>" class="flex items-center px-3 py-1 bg-gray-200 rounded hover:bg-gray-300"><?php echo $total_pages; ?></a>
		<?php endif; ?>

		<?php if ($current_page < $total_pages) : ?>
			<a href="#" data-page="<?php echo $current_page + 1; ?>" class="flex items-center px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
				<?php _e('Next', 'tender-plugin'); ?> »
			</a>
		<?php endif; ?>
	</div>
<?php
	return ob_get_clean();
}

/**
 * Renderiza la tabla para préstamos activos.
 */
function render_lendings_table($results)
{
	ob_start();
?>
	<table class="w-full mt-4 text-left bg-white table-auto min-w-max">
		<thead>
			<tr class="border-y border-slate-200 bg-slate-50">
				<th>
					<p class="px-2 text-sm text-slate-500">Libro</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Usuario</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Fecha Préstamo</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Fecha Límite</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Acciones</p>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $row) :
				try {
					$return_date  = new DateTime($row->stimated_return_date);
					$formatted_date = $return_date->format('d-m-Y');
				} catch (Exception $e) {
					$formatted_date = 'Fecha inválida';
				}
				$user_profile = get_author_posts_url($row->user_id, $row->user_nicename);
			?>
				<tr id="<?php echo esc_attr($row->id); ?>">
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($row->post_title); ?></td>
					<td class="p-2 border-b border-slate-200"><a href="<?php echo esc_url($user_profile); ?>"><?php echo esc_html($row->display_name); ?></a></td>
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($row->lending_date); ?></td>
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($formatted_date); ?></td>
					<td class="p-2 border-b border-slate-200">
						<button class="bg-red-500 lending-action lending-return tender-button" data-action="return" data-id="<?php echo esc_attr($row->id); ?>">
							Devolver
						</button>
						<button class="bg-orange-500 lending-action lending-renew tender-button" data-action="renew" data-id="<?php echo esc_attr($row->id); ?>">
							Renovar
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
	return ob_get_clean();
}

/**
 * Renderiza la tabla para préstamos finalizados.
 */
function render_old_lendings_table($results)
{
	ob_start();
?>
	<table class="w-full mt-4 text-left bg-white table-auto min-w-max">
		<thead>
			<tr class="border-y border-slate-200 bg-slate-50">
				<th>
					<p class="px-2 text-sm text-slate-500">Libro</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Usuario</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Fecha Préstamo</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Fecha de devolución</p>
				</th>
				<th>
					<p class="px-2 text-sm text-slate-500">Número de renovaciones</p>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $row) :
				try {
					$return_date  = new DateTime($row->stimated_return_date);
					$formatted_date = $return_date->format('d-m-Y');
				} catch (Exception $e) {
					$formatted_date = 'Fecha inválida';
				}
				$user_profile = get_author_posts_url($row->user_id, $row->user_nicename);
			?>
				<tr id="<?php echo esc_attr($row->id); ?>">
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($row->post_title); ?></td>
					<td class="p-2 border-b border-slate-200"><a href="<?php echo esc_url($user_profile); ?>"><?php echo esc_html($row->display_name); ?></a></td>
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($row->lending_date); ?></td>
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($formatted_date); ?></td>
					<td class="p-2 border-b border-slate-200"><?php echo esc_html($row->renewals); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
	return ob_get_clean();
}

/* =============================================================================
   El resto de funciones (las de préstamos, renovaciones, búsquedas de libros/usuarios,
   etc.) se mantienen en otro archivo, por ejemplo: lendingFunctions.php, y se incluyen
   donde corresponda.
   ============================================================================= */
