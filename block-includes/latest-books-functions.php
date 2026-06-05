<?php

function tender_latest_books_render_callback($block_attributes, $block_content)
{
	$books_to_show = isset($block_attributes['booksToShow']) ? (int) $block_attributes['booksToShow'] : 6;
	$books_to_show = max(1, $books_to_show);

	$query = new WP_Query([
		'post_type' => 'tender_book',
		'post_status' => 'publish',
		'posts_per_page' => $books_to_show,
		'orderby' => 'date',
		'order' => 'DESC',
		'no_found_rows' => true,
	]);

	$block_classes = tender_get_block_classes(
		$block_attributes,
		'wp-block-tender-a-library-latest-books'
	);

	ob_start();
	?>
	<section class="<?php echo esc_attr($block_classes); ?>">
		<div class="tender-latest-books-grid">
			<?php if ($query->have_posts()) : ?>
				<?php foreach ($query->posts as $book_post) : ?>
					<?php echo tender_render_mini_book_markup($book_post->ID, 'tender-latest-books-grid__item'); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="tender-latest-books-empty"><?php esc_html_e('There are no books in the library yet.', 'tender-library'); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php

	wp_reset_postdata();

	return ob_get_clean();
}
