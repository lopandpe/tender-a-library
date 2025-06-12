<?php

/**
 * Template para mostrar un libro individual (tender_book)
 */

if (!defined('ABSPATH')) {
	exit; // Evitar acceso directo
}

get_header(); ?>

<main id="tender-book">
	<div class="container">
		<article <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<div class="book-meta">
				<p><strong>Autor:</strong> <?php the_author(); ?></p>
				<p><strong>Fecha de publicación:</strong> <?php echo get_the_date(); ?></p>
			</div>
			<div class="book-content">
				<?php the_content(); ?>
			</div>
		</article>
	</div>
</main>

<?php get_footer(); ?>