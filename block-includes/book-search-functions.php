<?php

function tender_book_search_render_callback($block_attributes, $block_content)
{

	$block_classes = tender_get_block_classes(
		$block_attributes,
		'wp-block-tender-a-library-book-search'
	);

       
    
    ob_start(); ?>
    
    <div class="<?php echo $block_classes; ?>">
		<div id="library-search-root"
			data-api-url="<?php echo esc_url(rest_url('tender-library/v1/search')); ?>"
			data-filters-url="<?php echo esc_url(rest_url('tender-library/v1/filters')); ?>"
			>
		</div>
		
	</div>

    <?php return ob_get_clean();
}