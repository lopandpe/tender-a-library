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
	</div>

	<script>
		jQuery(document).ready(function($) {
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
		echo '<table class="widefat">';
		echo '<thead><tr><th>Libro</th><th>Usuario</th><th>Fecha Préstamo</th><th>Fecha Límite</th></tr></thead><tbody>';
		foreach ($results as $row) {
			echo "<tr><td>{$row->post_title}</td><td>{$row->display_name}</td><td>{$row->lending_date}</td><td>{$row->stimated_return_date}</td></tr>";
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No hay préstamos activos.</p>';
	}

	wp_die();
}
add_action('wp_ajax_tender_search_lendings', 'tender_search_lendings');
