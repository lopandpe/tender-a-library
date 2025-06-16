<?php

function tender_book_summary_render_callback($block_attributes, $block_content)
{

	$block_classes = 'wp-block-tender-a-library-book-summary';
	$current_post_id = get_the_ID();
	$summary = carbon_get_post_meta($current_post_id, 'tender_book_excerpt');


	ob_start();

?>
	<section class="<?php echo $block_classes; ?>">
		<div className="block-container">
			<p> <?php if ($summary) : echo $summary;
				else: _e("There is no summary", "tender-a-library");
				endif; ?></p>

		</div>
	</SECTION>

<?php

	return ob_get_clean();
}
