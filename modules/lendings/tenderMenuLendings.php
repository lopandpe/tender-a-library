<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_lendings_page()
{
?>
	<div class="wrap">
		<h1>Préstamos Activos</h1>
		<input type="text" id="search-lendings" placeholder="Buscar por usuario o libro..." style="width: 300px;">
		<div id="lendings-list"></div>
		<!-- Modal de confirmación -->
		<div id="confirmation-modal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
			<div class="bg-white p-6 rounded-lg max-w-sm w-full">
				<h3 id="modal-message" class="text-lg font-semibold mb-4"></h3>
				<button id="confirm-action" class="bg-green-500 text-white py-2 px-4 rounded mr-2">Confirmar</button>
				<button id="cancel-action" class="bg-gray-500 text-white py-2 px-4 rounded">Cancelar</button>
			</div>
		</div>
	</div>

	<script>
		jQuery(document).ready(function($) {
			// Cargar préstamos inicialmente
			function loadLendings(query = '') {
				$.post(ajaxurl, {
					action: 'tender_search_lendings',
					query: query
				}, function(response) {
					$('#lendings-list').html(response);
				});
			}

			$('#search-lendings').on('keyup', function() {
				let query = $(this).val();
				loadLendings(query);
			});

			loadLendings();

			// Delegación de eventos para los botones "Devolver" y "Renovar"
			$(document).on('click', '.lending-return', function() {
				let lendingId = $(this).closest('tr').attr('id'); // Obtener el ID de la fila
				showModal(lendingId, 'return');
			});

			$(document).on('click', '.lending-renew', function() {
				let lendingId = $(this).closest('tr').attr('id'); // Obtener el ID de la fila
				showModal(lendingId, 'renew');
			});

			// Mostrar modal de confirmación
			function showModal(lendingId, action) {
				// Configurar el mensaje del modal
				let message = '';
				if (action === 'return') {
					message = '¿Estás seguro de que quieres devolver este libro?';
				} else if (action === 'renew') {
					message = '¿Estás seguro de que quieres renovar este préstamo?';
				}

				// Mostrar modal con mensaje
				$('#confirmation-modal').show();
				$('#modal-message').text(message);
				$('#confirm-action').data('lending-id', lendingId).data('action', action);
			}

			// Confirmar acción en el modal
			$(document).on('click', '#confirm-action', function() {
				let lendingId = $(this).data('lending-id');
				let action = $(this).data('action');

				// Realizar la llamada AJAX para devolver o renovar el préstamo
				$.post(ajaxurl, {
					action: 'tender_handle_lending_action',
					lending_id: lendingId,
					action_type: action
				}, function(response) {
					alert(response.data.message); // Mostrar mensaje de éxito
					$('#confirmation-modal').hide(); // Cerrar el modal
					loadLendings(); // Recargar la lista de préstamos
				});
			});

			// Cerrar modal si el usuario cancela
			$(document).on('click', '#cancel-action', function() {
				$('#confirmation-modal').hide();
			});
		});
	</script>
<?php
}


function tender_search_lendings()
{
	global $wpdb;
	$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

	$sql = "SELECT l.id, u.display_name, b.post_title, l.lending_date, l.stimated_return_date
            FROM {$wpdb->prefix}tender_lendings l
            JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
            JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
            WHERE l.returned = 0";

	if (!empty($query)) {
		$sql .= " AND (b.post_title LIKE '%$query%' OR u.display_name LIKE '%$query%')";
	}

	$sql .= " ORDER BY l.lending_date DESC LIMIT 10";

	$results = $wpdb->get_results($sql);

	if ($results) {
		echo '<table class="w-full mt-4 text-left table-auto min-w-max bg-white">';
		echo '
			<thead>
				<tr class="border-y border-slate-200 bg-slate-50">
					<th class="transition-colors ">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Libro</p>
					</th>
					<th class="transition-colors border-slate-200 bg-slate-50">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Usuario</p>
					</th>
					<th class="transition-colors border-slate-200 bg-slate-50">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Fecha Préstamo</p>
					</th>
					<th class="transition-colors border-slate-200 bg-slate-50">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Fecha Límite</p>
					</th>
					<th class="transition-colors border-slate-200 bg-slate-50">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Acciones</p>
					</th>
				</tr>
			</thead>
			<tbody>';
		foreach ($results as $row) {
			$date = date_create($row->stimated_return_date);
			$date =  date_format($date, "d-m-Y");
			echo "
				<tr id='{$row->id}'>
					<td class='p-2 border-b border-slate-200'>{$row->post_title}</td>
					<td class='p-2 border-b border-slate-200'>{$row->display_name}</td>
					<td class='p-2 border-b border-slate-200'>{$row->lending_date}</td>
					<td class='p-2 border-b border-slate-200'>{$date}</td>
					<td class='p-2 border-b border-slate-200'>
						<button class='lending-action lending-return tender-button bg-red-500' id='lending-{$row->id}' data-action='return' data-id='{$row->id}'>Devolver</button>
						<button class='lending-action lending-renew tender-button bg-orange-500' id='lending-renew-{$row->id}' data-action='renew' data-id='{$row->id}'>Renovar</button>
					</td>
				</tr>";
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No hay préstamos activos.</p>';
	}

	wp_die();
}
add_action('wp_ajax_tender_search_lendings', 'tender_search_lendings');
