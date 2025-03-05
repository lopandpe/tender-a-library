<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_old_lendings_page()
{
?>
	<div class="wrap">
		<h1>Préstamos Pasados</h1>
		<input type="text" id="search-lendings" placeholder="Buscar por usuario o libro..." style="width: 300px;">
		<div id="lendings-list"></div>
	</div>

	<script>
		jQuery(document).ready(function($) {
			function loadLendings(query = '') {
				$.post(ajaxurl, {
					action: 'tender_search_old_lendings',
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
		});
	</script>
<?php
}


function tender_search_old_lendings()
{
	global $wpdb;
	$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

	$sql = "SELECT l.id, u.display_name, u.ID as user_id, u.user_nicename, b.post_title, l.lending_date, l.real_return_date
            FROM {$wpdb->prefix}tender_lendings l
            JOIN {$wpdb->prefix}posts b ON l.book_id = b.ID
            JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
            WHERE l.returned = 1";

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
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Fecha del préstamo</p>
					</th>
					<th class="transition-colors border-slate-200 bg-slate-50">
						<p class=" px-2 font-sans text-sm font-normal leading-none text-slate-500">Fecha de devolución</p>
					</th>
				</tr>
			</thead>
			<tbody>';

		foreach ($results as $row) {

			$lendingDate =  date_format(date_create($row->lending_date), "d-m-Y");
			$returnDate =  date_format(date_create($row->real_return_date), "d-m-Y");
			$user_profile = get_author_posts_url($row->user_id, $row->user_nicename);
			echo "
				<tr id='{$row->id}'>
					<td class='p-2 border-b border-slate-200'>{$row->post_title}</td>
					<td class='p-2 border-b border-slate-200'><a href='{$user_profile}'>{$row->display_name}</a></td>
					<td class='p-2 border-b border-slate-200'>{$lendingDate}</td>
					<td class='p-2 border-b border-slate-200'>{$returnDate}</td>
				</tr>";
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No hay préstamos.</p>';
	}

	wp_die();
}
add_action('wp_ajax_tender_search_old_lendings', 'tender_search_old_lendings');
