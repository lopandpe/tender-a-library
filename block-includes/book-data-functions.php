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
				<h2><?php _e("Book data", "tender-a-library"); ?></h4>
				<ul>
					<li><strong><?php _e("Author", "tender-a-library"); ?></strong>: <?php echo $author; ?></li>
					<li>
						<strong><?php _e("Publisher", "tender-a-library"); ?></strong>: <?php echo $publisher; ?>
					</li>
					<?php 
						if($section){
							?>
								<li>
									<strong><?php _e("Library section", "tender-a-library"); ?></strong>:
									<a href="<?php echo get_term_link($section) ?>" aria-disabled>
										<?php echo $section->name; ?>
									</a>
								</li>
							<?php
						}
					
					?>
					<li><strong><?php _e("Publication year", "tender-a-library"); ?></strong>: <?php echo $year; ?></li>
					<?php 
						if($section){
							$section_number = carbon_get_term_meta($section->term_id, 'tender_section_number');
							$section_number .= ', ' . carbon_get_post_meta($current_post_id, 'tender_book_sig1') . ' - ' . carbon_get_post_meta($current_post_id, 'tender_book_sig2');
							?>
								<li>
									<strong><?php _e("Signature", "tender-a-library"); ?></strong>: <? echo $section_number; ?>
								</li>

							<?php
						}
					?>
				</ul>
				<div id="lending-info">
					<?php
					$is_logged_in = is_user_logged_in();
					$book_id = $current_post_id;
					global $wpdb;
					$is_available = tender_can_book_be_lent($book_id);					
					$lendings = tender_get_active_lendings_by_book($book_id);
					if (!$is_logged_in) {
						echo $is_available
							? '<p class="success">' . __("Available for loan.", "tender-a-library") . '</p>'
							: '<p class="error">' . __("Not available.", "tender-a-library") . '</p>';
					} else {
						$current_user = wp_get_current_user();
						$user_id = $current_user->ID;
						$roles = (array) $current_user->roles;

						if (in_array('opener', $roles) || in_array('administrator', $roles)) {
							if ($is_available) {
								echo '<a id="lend_book" class="wp-block-button__link" href="#">' . __("Lend this book", "tender-a-library") . '</a>';
							} else {
								echo'<p class="error">' . __("Not available.", "tender-a-library") . '</p>';
								echo '<a id="reserve_book" class="wp-block-button__link" href="#">' . __("Reserve this book", "tender-a-library") . '</a>';
							}
						} elseif (in_array('reader', $roles)) {
							if (tender_user_has_borrowed_book($user_id, $book_id)) {
								echo '<a href="' . esc_url(get_author_posts_url($user_id)) . '">' . __("Renew this lending from your profile", "tender-a-library") . '</a></p>';
							} elseif ($is_available) {
								echo '<p class="success">' . __("Available for loan.", "tender-a-library") . '</p>';
								echo '<a id="reserve_book" class="wp-block-button__link" href="#">' . __("Reserve this book", "tender-a-library") . '</a>';
							} else {
								echo '<p class="error">' . __("Not available.", "tender-a-library") . '</p>';
								echo '<a id="reserve_book" class="wp-block-button__link" href="#">' . __("Reserve this book", "tender-a-library") . '</a>';
							}
						}
					}



					if (!empty($lendings)) {
						echo '<div class="lending-dates">';
						
						if (count($lendings) > 1) {
							echo '<h6>' . __('Current active lendings', 'tender-a-library') . ':</h6>';
						} else {
							echo '<h6>' . __('Current active lending', 'tender-a-library') . ':</h6>';
						}

						echo '<ul>';
						foreach ($lendings as $index => $lending) {
							$return_date = date_i18n(get_option('date_format'), strtotime($lending->stimated_return_date));
							$current = $index + 1;

							echo '<li>';
							if (count($lendings) > 1) {
								/* translators: Number of current lendings  */
								echo '<strong>' . sprintf(__('Lending #%d', 'tender-a-library'), $current) . '</strong><br>';
							}
							echo __('Estimated return', 'tender-a-library') . ': ' . esc_html($return_date);
							echo '</li>';
						}
						echo '</ul></div>';
					}

					?>
				</div>
			</div>
	</section>

<?php

	return ob_get_clean();
}
