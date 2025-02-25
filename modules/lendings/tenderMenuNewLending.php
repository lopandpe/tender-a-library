<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_new_lending_page()
{
?>
	<div class="wrap">
		<h1>Nuevo Préstamo</h1>
		<form id="new-lending-form">
			<label>Buscar Libro:</label>
			<input type="text" id="search-book" placeholder="Título del libro">
			<select id="book-id" required></select>

			<label>Buscar Usuario:</label>
			<input type="text" id="search-user" placeholder="Nombre o email">
			<select id="user-id" required></select>

			<label>Fecha del préstamo:</label>
			<input type="date" id="return-date" required>

			<button type="submit">Registrar Préstamo</button>
		</form>
		<div id="response-message"></div>
	</div>

	<script>
		jQuery(document).ready(function($) {
			function searchBooks(query) {
				$.post(ajaxurl, {
					action: 'tender_search_books',
					query: query
				}, function(response) {
					$('#book-id').html(response);
				});
			}

			function searchUsers(query) {
				$.post(ajaxurl, {
					action: 'tender_search_users',
					query: query
				}, function(response) {
					$('#user-id').html(response);
				});
			}

			$('#search-book').on('keyup', function() {
				searchBooks($(this).val());
			});

			$('#search-user').on('keyup', function() {
				searchUsers($(this).val());
			});

			$('#new-lending-form').submit(function(e) {
				e.preventDefault();
				$.post(ajaxurl, {
					action: 'tender_create_lending_ajax',
					book_id: $('#book-id').val(),
					user_id: $('#user-id').val(),
					return_date: $('#return-date').val()
				}, function(response) {
					if (response.success) {
						$('#response-message').html('<p style="color:green;">' + response.data.message + '</p>');
						$('#new-lending-form')[0].reset();
						$('#book-id').html('');
						$('#user-id').html('');
					} else {
						$('#response-message').html('<p style="color:red;">' + response.data.message + '</p>');
					}
				}, 'json');
			});
		});
	</script>
<?php
}
