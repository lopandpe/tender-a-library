<?php

/**
 * Template para mostrar un libro individual (tender_book)
 */

if (!defined('ABSPATH')) {
	exit; // Evitar acceso directo
}

get_header();

echo '<main class="wp-block-group wrapper alignwide single-book-content" style="padding: 100px 20px;">';
	echo '<div class="container">';
		echo '<h6 class="wp-block-heading alignwide">' . esc_html__('Book', 'tender-a-library') . '</h6>';
		echo '<h1 class="wp-block-post-title alignwide has-x-large-font-size">' . get_the_title() . '</h1>';
		echo '<div class="wp-block-columns alignwide">';
			echo '<div class="wp-block-column" style="flex-basis:30%; padding: 0 20px;">';
				echo do_blocks('<!-- wp:tender-a-library/book-cover /-->');
			echo '</div>';
			echo '<div class="wp-block-column" style="flex-basis:30%; padding: 0 20px;">';
				echo do_blocks('<!-- wp:tender-a-library/book-data /-->');
			echo '</div>';
			echo '<div class="wp-block-column" style="flex-basis:40%; padding: 0 20px;">';
				echo do_blocks('<!-- wp:tender-a-library/book-summary /-->');
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</main>';

get_footer();
