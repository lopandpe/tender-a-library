<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}

// Mostrar el perfil en la página seleccionada
function tal_profile_template($content)
{
	$profile_page_id = get_option('tal_profile_page');

	// Verificar si estamos en la página de perfil y si tenemos la opción habilitada
	if ($profile_page_id && is_page($profile_page_id)) {
		if (!is_user_logged_in()) {
			ob_start(); ?>
			<div class="login-form-container">
				<h2>Iniciar Sesión</h2>
				<p>Debes iniciar sesión para ver tu perfil.</p>
				<?php
				wp_login_form([
					'redirect' => get_permalink($profile_page_id),
					'remember' => true
				]);
				?>
				<p><a href="<?php echo wp_lostpassword_url(); ?>">¿Olvidaste tu contraseña?</a></p>
			</div>

		<?php return ob_get_clean();
		}

		// Obtener el usuario desde la URL (query var 'tender_profile')
		$username = get_query_var('tal_profile_user');
		$current_user = $username ? $user = get_user_by('slug', $username) : wp_get_current_user();
		if (!$current_user) {
			ob_start(); ?>
			<div class="no-results">
				<div class="">
					<div class="">
						<p class=""><?php echo __('There is no user with this information', 'tender-a-library'); ?></p>
					</div>
				</div>
			</div>

		<?php return ob_get_clean();
		}
		ob_start(); ?>

		<div class="profile">

			<div class="user-info">
				<ul class="">
					<li class="">
						<span class="label"><?php _e('Name', 'tender-a-library') ?>:</span>
						<span class="data"><?php echo esc_html($current_user->first_name); ?> <?php echo esc_html($current_user->last_name); ?></span>
					</li>
					<li class="">
						<span class="label"><?php _e('E-mail', 'tender-a-library') ?>:</span>
						<span class="data"><?php echo esc_html($current_user->user_email); ?></span>
					</li>
					<li class="">
						<span class="label"><?php _e('Phone', 'tender-a-library') ?>:</span>
						<span class="data"><?php echo esc_html(carbon_get_user_meta($current_user->ID, 'phone_number')); ?></span>
					</li>
					<li class="">
						<span class="label"><?php _e('Consents to receive news by e-mail:', 'tender-a-library') ?></span>
						<span class="data"><?php carbon_get_user_meta($current_user->ID, 'newsletter') ? _e('Yes', 'tender-a-library') : _e('No', 'tender-a-library')  ?></span>
					</li>
				</ul>
			</div>
			<div class="profile-actions">

				<a href="<?php echo esc_url(get_permalink(get_option('tal_edit_profile_page'))) . $current_user->user_nicename; ?>" class="edit-profile tal-button">
					<?php echo __('Edit profile', 'tender-a-library') ?>
				</a>
				<a href="<?php echo wp_logout_url(home_url()); ?>" class="logout tal-button">
					<i><svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M18.0134 14.8884C17.6067 15.2952 17.6067 15.9548 18.0134 16.3616C18.4202 16.7683 19.0798 16.7683 19.4866 16.3616L22.5209 13.3272C22.5366 13.3116 22.5518 13.2955 22.5664 13.2792C22.7812 13.0883 22.9167 12.81 22.9167 12.5C22.9167 12.19 22.7812 11.9117 22.5664 11.7208C22.5518 11.7045 22.5366 11.6884 22.5209 11.6728L19.4866 8.63843C19.0798 8.23164 18.4202 8.23164 18.0134 8.63843C17.6067 9.04523 17.6067 9.70477 18.0134 10.1116L19.3602 11.4583H13.5417C12.9664 11.4583 12.5 11.9247 12.5 12.5C12.5 13.0753 12.9664 13.5417 13.5417 13.5417H19.3602L18.0134 14.8884Z" fill="#C40104" />
							<path d="M5.20837 2.08334C3.48249 2.08334 2.08337 3.48245 2.08337 5.20834V19.7917C2.08337 21.5176 3.48249 22.9167 5.20837 22.9167H15.1042C16.5424 22.9167 17.7084 21.7507 17.7084 20.3125V17.4298C17.5541 17.3406 17.4089 17.2301 17.2769 17.0981C16.5952 16.4165 16.4847 15.3801 16.9452 14.5833H13.5417C12.3911 14.5833 11.4584 13.6506 11.4584 12.5C11.4584 11.3494 12.3911 10.4167 13.5417 10.4167H16.9452C16.4847 9.61994 16.5952 8.58349 17.2769 7.90187C17.4089 7.7699 17.5541 7.65934 17.7084 7.57019V4.6875C17.7084 3.24926 16.5424 2.08334 15.1042 2.08334H5.20837Z" fill="#C40104" />
						</svg>
					</i>
					<?php echo __('Logout', 'tender-a-library') ?>
				</a>
			</div>
		</div>

		<div class="profile-lendings">
			<h2 class=""><?php echo __('Active lendings', 'tender-a-library'); ?></h2>
			<?php
			$active_lendings = tender_get_active_lendings_by_user($current_user->ID);
			if (!empty($active_lendings)) : ?>
				<ul class="active-lendings">
					<?php foreach ($active_lendings as $lending) :

						try {
							$return_date  = strtotime($lending->stimated_return_date);
							$lending_date = strtotime($lending->lending_date);
							$formatted_date = wp_date(get_option('date_format'), $return_date);
							$formatted_lending_date = wp_date(get_option('date_format'), $lending_date);
						} catch (Exception $e) {
							$formatted_date = 'Fecha inválida';
							$formatted_lending_date = 'Fecha inválida';
						}
						$cover_id = carbon_get_post_meta($lending->book_id, 'tender_book_cover');
						$delayed = $return_date < time() ? 'delayed' : '';
					?>
						<li class="lending <?php echo $delayed; ?>">
							<div class="tender-book-preview">
								<div class="cover">
									<?php if ($cover_id): ?>
										<?php echo wp_get_attachment_image($cover_id, 'medium'); ?>
									<?php else: ?>
										<img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/svg/default-book.svg"" alt=" No cover"> <?php endif; ?>
								</div>
								<div class="book-info">
									<a class="title" href="<?php echo get_permalink($lending->book_id); ?>"><?php echo get_the_title($lending->book_id); ?></a>
									<div class="author"><?php echo carbon_get_post_meta($lending->book_id, 'tender_book_author'); ?></div>

								</div>
							</div>


							<div class="lending-info">
								<div class="dates">
									<div class="lending-date"><?php _e('Lending date', 'tender-a-library'); ?>: <span><?php echo $formatted_lending_date; ?></span></div>
									<div class="lending-date  <?php if ($delayed) {
																	echo "danger";
																} ?>"><?php _e('Estimated return date', 'tender-a-library'); ?>: <span><?php echo $formatted_date; ?></span></div>

									<div class=" lending-actions">
										<?php if (count(tender_get_renewals_by_lending($lending->id)) > 3): ?>
											<p class="lending-renewal-limit"><?php _e('This loan cannot be extended', 'tender-a-library'); ?></p>
										<?php else: ?>
											<button class="lending-action lending-renew tal-button" data-action="renew" data-id="<?php echo esc_attr($lending->id); ?>">
												<?php _e('Loan renewal', 'tender-a-library'); ?>
											</button>
										<?php endif; ?>

										<?php if (tal_current_user_opener_or_admin()): ?>

											<button class="lending-action lending-return tal-button" data-action="return" data-id="<?php echo esc_attr($lending->id); ?>">
												<?php _e('Return', 'tender-a-library'); ?>
											</button>
										<?php endif; ?>
									</div>
								</div>
							</div>

							<?php if ($delayed): ?>
								<div class="delayed">
									<img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/svg/delay.svg" alt="Delayed" class="w-full h-full">
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else: ?>
				<p class="no-results"><?php echo __('There are no active lendings right now', 'tender-a-library'); ?></p>
			<?php endif; ?>
		</div>


		<div class="profile-lendings">
			<?php
			$past_lendings = tender_get_past_lendings_by_user($current_user->ID);
			if (!empty($past_lendings)) : ?>
				<h2 class=""><?php echo __('Past lendings', 'tender-a-library'); ?></h2>
				<div class="past-lendings">
					<?php foreach ($past_lendings as $lending) :

						try {
							$return_date  = strtotime($lending->stimated_return_date);
							$lending_date = strtotime($lending->lending_date);
							$formatted_date = wp_date(get_option('date_format'), $return_date);
							$formatted_lending_date = wp_date(get_option('date_format'), $lending_date);
						} catch (Exception $e) {
							$formatted_date = 'Fecha inválida';
							$formatted_lending_date = 'Fecha inválida';
						}
						$cover_id = carbon_get_post_meta($lending->book_id, 'tender_book_cover');
					?>

						<a class="tender-book-preview" href="<?php echo get_permalink($lending->book_id); ?>">
							<div class="cover">
								<?php if ($cover_id): ?>
									<?php echo wp_get_attachment_image($cover_id, 'medium'); ?>
								<?php else: ?>
									<img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/svg/default-book.svg"" alt=" No cover"> <?php endif; ?>
								<div class="dates">
									<span class="loan-date"><?php echo __('Lending') . ': ' . $formatted_lending_date; ?></span>
									<span class="return-date"><?php echo  __('Return') . ': ' . $formatted_date; ?></span>
								</div>
							</div>
							<div class="book-info">
								<div class="title" href="<?php echo get_permalink($lending->book_id); ?>"><?php echo get_the_title($lending->book_id); ?></div>
								<div class="author"><?php echo carbon_get_post_meta($lending->book_id, 'tender_book_author'); ?></div>

							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Modal de confirmación -->
		<div id="confirmation-modal" class="tal-confirmation-modal" style="display: none;">
			<div class="wrapper">
				<h3 id="modal-message"></h3>
				<button id="confirm-action" class="tal-button">Confirmar</button>
				<button id="cancel-action" class="tal-button">Cancelar</button>
				<button id="accept-action" class="tal-button" styles="display: none">Aceptar</button>
			</div>
		</div>
<?php return ob_get_clean();
	}

	return $content;
}

add_filter('the_content', 'tal_profile_template');
