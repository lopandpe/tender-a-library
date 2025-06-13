<?php

function tender_book_data_render_callback($block_attributes, $block_content)
{

	$block_classes = 'wp-block-tender-a-library-book-data';
	$current_post_id = get_the_ID();
	$author = carbon_get_post_meta($current_post_id, 'tender_book_author');
	$publisher = carbon_get_post_meta($current_post_id, 'tender_book_publisher');
	$year = carbon_get_post_meta($current_post_id, 'tender_book_year');
	$section = carbon_get_post_meta( $current_post_id, 'tender_book_section' );
	$section = count($section) ? get_term($section[0]['id'], 'tender_section') : null;

	ob_start();

?>
	<section class="<?php echo $block_classes; ?>">		
			<div className="block-container">
				<h4><?php _e("Book data", "tender-a-library"); ?></h4>
				<ul>
					<li><?php _e("Author", "tender-a-library"); ?>: <?php echo $author; ?></li>
					<li>
						<?php _e("Publisher", "tender-a-library"); ?>: <?php echo $publisher; ?>
					</li>
					<?php 
						if($section){
							?>
								<li>
									<?php _e("Library section", "tender-a-library"); ?>:
									<a href="<?php echo get_term_link($section) ?>" aria-disabled>
										<?php echo $section->name; ?>
									</a>
								</li>
							<?php
						}
					
					?>
					<li><?php _e("Publication year", "tender-a-library"); ?>: <?php echo $year; ?></li>
					<?php 
						if($section){
							$section_number = carbon_get_term_meta($section->term_id, 'tender_section_number');
							$section_number .= ', ' . carbon_get_post_meta($current_post_id, 'tender_book_sig1') . ' - ' . carbon_get_post_meta($current_post_id, 'tender_book_sig2');
							?>
								<li>
									<?php _e("Signature", "tender-a-library"); ?>: <? echo $section_number; ?>
								</li>

							<?php
						}
					?>
				</ul>
			</div>
	</section>

<?php

	return ob_get_clean();
}
