<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}

function tal_profile_tab_url($user, $tab = 'overview', $args = array())
{
	$base_url = get_user_profile_url_by_id($user->ID)['profile'];
	$query_args = array();

	if ($tab !== 'overview') {
		$query_args['tal_profile_tab'] = $tab;
	}

	if (!empty($args)) {
		$query_args = array_merge($query_args, $args);
	}

	return empty($query_args) ? $base_url : add_query_arg($query_args, $base_url);
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
				<h2><?php echo esc_html__('Login', 'tender-library'); ?></h2>
				<p><?php echo esc_html__('You must be logged in to view your profile.', 'tender-library'); ?></p>
				<?php
				wp_login_form([
					'redirect' => get_permalink($profile_page_id),
					'remember' => true
				]);
				?>
				<p><a href="<?php echo wp_lostpassword_url(); ?>"><?php echo esc_html__('Forgot your password?', 'tender-library'); ?></a></p>
			</div>

		<?php return ob_get_clean();
		}

		// Obtener el usuario desde la URL (query var 'tal_profile_user')
		$username = get_query_var('tal_profile_user');
		$current_user = $username ? get_user_by('slug', $username) : wp_get_current_user();

		if (!($current_user instanceof WP_User)) {
			ob_start();
		?>
			<div class="no-results">
				<p class=""><?php echo __('User not found.', 'tender-library'); ?></p>
			</div>
		<?php
			return ob_get_clean();
		}

		$is_call_logs_manager = tal_current_user_can_manage_call_logs();
		$active_tab = isset($_GET['tal_profile_tab']) ? sanitize_key(wp_unslash($_GET['tal_profile_tab'])) : 'overview';
		if (!in_array($active_tab, array('overview', 'calls'), true)) {
			$active_tab = 'overview';
		}
		if (!$is_call_logs_manager && $active_tab === 'calls') {
			$active_tab = 'overview';
		}

		$call_log_message = '';
		$call_log_notice_class = 'notice-success';
		$editing_call = null;

		if (isset($_GET['tal_call_msg'])) {
			$call_msg = sanitize_key(wp_unslash($_GET['tal_call_msg']));
			switch ($call_msg) {
				case 'saved':
					$call_log_message = __('Call log saved.', 'tender-library');
					$call_log_notice_class = 'notice-success';
					break;
				case 'updated':
					$call_log_message = __('Call log updated.', 'tender-library');
					$call_log_notice_class = 'notice-success';
					break;
				case 'deleted':
					$call_log_message = __('Call log deleted.', 'tender-library');
					$call_log_notice_class = 'notice-success';
					break;
				case 'error':
					$call_log_notice_class = 'notice-error';
					$call_log_message = isset($_GET['tal_call_error']) ? sanitize_text_field(wp_unslash($_GET['tal_call_error'])) : __('Ha ocurrido un error.', 'tender-library');
					break;
			}
		}
		// Comprobación para ver perfil:
		if ( tal_can_view_profile($current_user->ID) === false) {
			ob_start(); 
			?>
			<div class="no-results">
				<div class="">
					<div class="">
						<p class=""><?php echo __('You do not have permission to view this page.', 'tender-library'); ?></p>
					</div>
				</div>
			</div>
			<?php return ob_get_clean();
		}
		else {
			if ($is_call_logs_manager) {
				$edit_call_id = isset($_GET['tal_edit_call']) ? absint($_GET['tal_edit_call']) : 0;
				if ($edit_call_id > 0) {
					$editing_call = tal_get_user_call_log($edit_call_id, (int) $current_user->ID);
					$active_tab = 'calls';
				}

				if (isset($_POST['tal_call_action'])) {
					$redirect_args = array(
						'tal_profile_tab' => 'calls',
					);
					$nonce_valid = isset($_POST['tal_call_log_nonce']) &&
						wp_verify_nonce(
							sanitize_text_field(wp_unslash($_POST['tal_call_log_nonce'])),
							'tal_call_log_action_' . $current_user->ID
						);

					if (!$nonce_valid) {
						$redirect_args['tal_call_msg'] = 'error';
						$redirect_args['tal_call_error'] = __('Security check failed. Please try again.', 'tender-library');
					} else {
						$target_user_id = isset($_POST['tal_call_log_user_id']) ? (int) $_POST['tal_call_log_user_id'] : 0;
						if ($target_user_id !== (int) $current_user->ID) {
							$redirect_args['tal_call_msg'] = 'error';
							$redirect_args['tal_call_error'] = __('Invalid target user.', 'tender-library');
						} else {
							$action = sanitize_key(wp_unslash($_POST['tal_call_action']));
							$subject = isset($_POST['tal_call_subject']) ? sanitize_text_field(wp_unslash($_POST['tal_call_subject'])) : '';
							$comment = isset($_POST['tal_call_comment']) ? wp_kses_post(wp_unslash($_POST['tal_call_comment'])) : '';
							$call_date = isset($_POST['tal_call_date']) ? sanitize_text_field(wp_unslash($_POST['tal_call_date'])) : '';
							$call_id = isset($_POST['tal_call_id']) ? absint($_POST['tal_call_id']) : 0;

							if ($action === 'add') {
								$result = tal_create_user_call_log($target_user_id, $subject, $comment, $call_date);
								if (is_wp_error($result)) {
									$redirect_args['tal_call_msg'] = 'error';
									$redirect_args['tal_call_error'] = $result->get_error_message();
								} else {
									$redirect_args['tal_call_msg'] = 'saved';
								}
							} elseif ($action === 'update') {
								$result = tal_update_user_call_log($call_id, $target_user_id, $subject, $comment, $call_date);
								if (is_wp_error($result)) {
									$redirect_args['tal_call_msg'] = 'error';
									$redirect_args['tal_call_error'] = $result->get_error_message();
								} else {
									$redirect_args['tal_call_msg'] = 'updated';
								}
							} elseif ($action === 'delete') {
								$result = tal_delete_user_call_log($call_id, $target_user_id);
								if (is_wp_error($result)) {
									$redirect_args['tal_call_msg'] = 'error';
									$redirect_args['tal_call_error'] = $result->get_error_message();
								} else {
									$redirect_args['tal_call_msg'] = 'deleted';
								}
							}
						}
					}

					$redirect_url = add_query_arg(
						$redirect_args,
						remove_query_arg(array('tal_call_msg', 'tal_call_error', 'tal_edit_call', 'tal_profile_tab'))
					);
					wp_safe_redirect($redirect_url);
					exit;
				}
			}

			ob_start(); ?>

			<div class="profile">

				<div class="user-info">
					<ul class="">
						<li class="">
							<span class="label"><?php _e('Name', 'tender-library') ?>:</span>
							<span class="data"><?php echo esc_html($current_user->first_name); ?> <?php echo esc_html($current_user->last_name); ?></span>
						</li>
						<li class="">
							<span class="label"><?php _e('E-mail', 'tender-library') ?>:</span>
							<span class="data"><?php echo esc_html($current_user->user_email); ?></span>
						</li>
						<li class="">
							<span class="label"><?php _e('Phone', 'tender-library') ?>:</span>
							<span class="data"><?php echo esc_html(carbon_get_user_meta($current_user->ID, 'phone_number')); ?></span>
						</li>
						<li class="">
							<span class="label"><?php _e('Consents to receive news by e-mail:', 'tender-library') ?></span>
							<span class="data"><?php carbon_get_user_meta($current_user->ID, 'newsletter') ? _e('Yes', 'tender-library') : _e('No', 'tender-library')  ?></span>
						</li>
					</ul>
				</div>
				<div class="profile-actions">
					<?php if (tal_can_edit_profile($current_user->ID)) : ?>
							<a href="<?php echo esc_url(get_permalink(get_option('tal_edit_profile_page'))) . $current_user->user_nicename; ?>" class="edit-profile tal-button">
								<?php echo __('Edit profile', 'tender-library') ?>
							</a>			
					<?php endif; ?>
					
					<?php if ($current_user->ID === wp_get_current_user()->ID) : ?>
							<a href="<?php echo wp_logout_url(home_url()); ?>" class="logout tal-button">
								<i><svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M18.0134 14.8884C17.6067 15.2952 17.6067 15.9548 18.0134 16.3616C18.4202 16.7683 19.0798 16.7683 19.4866 16.3616L22.5209 13.3272C22.5366 13.3116 22.5518 13.2955 22.5664 13.2792C22.7812 13.0883 22.9167 12.81 22.9167 12.5C22.9167 12.19 22.7812 11.9117 22.5664 11.7208C22.5518 11.7045 22.5366 11.6884 22.5209 11.6728L19.4866 8.63843C19.0798 8.23164 18.4202 8.23164 18.0134 8.63843C17.6067 9.04523 17.6067 9.70477 18.0134 10.1116L19.3602 11.4583H13.5417C12.9664 11.4583 12.5 11.9247 12.5 12.5C12.5 13.0753 12.9664 13.5417 13.5417 13.5417H19.3602L18.0134 14.8884Z" fill="#C40104" />
										<path d="M5.20837 2.08334C3.48249 2.08334 2.08337 3.48245 2.08337 5.20834V19.7917C2.08337 21.5176 3.48249 22.9167 5.20837 22.9167H15.1042C16.5424 22.9167 17.7084 21.7507 17.7084 20.3125V17.4298C17.5541 17.3406 17.4089 17.2301 17.2769 17.0981C16.5952 16.4165 16.4847 15.3801 16.9452 14.5833H13.5417C12.3911 14.5833 11.4584 13.6506 11.4584 12.5C11.4584 11.3494 12.3911 10.4167 13.5417 10.4167H16.9452C16.4847 9.61994 16.5952 8.58349 17.2769 7.90187C17.4089 7.7699 17.5541 7.65934 17.7084 7.57019V4.6875C17.7084 3.24926 16.5424 2.08334 15.1042 2.08334H5.20837Z" fill="#C40104" />
									</svg>
								</i>
								<?php echo __('Logout', 'tender-library') ?>
							</a>			
					<?php endif; ?>
				</div>
			</div>

			<?php if ($is_call_logs_manager) : ?>
			<div class="tal-profile-tabs">
				<a class="tal-profile-tab <?php echo $active_tab === 'overview' ? 'is-active' : ''; ?>" href="<?php echo esc_url(tal_profile_tab_url($current_user, 'overview')); ?>">
					<?php echo esc_html__('Profile', 'tender-library'); ?>
				</a>
					<a class="tal-profile-tab calls-button <?php echo $active_tab === 'calls' ? 'is-active' : ''; ?>" href="<?php echo esc_url(tal_profile_tab_url($current_user, 'calls')); ?>">
						<?php echo esc_html__('Call history', 'tender-library'); ?>
					</a>
			</div>
			<?php endif; ?>

			<?php if ($active_tab === 'calls' && $is_call_logs_manager) : ?>
				<?php $call_logs = tal_get_user_call_logs($current_user->ID, 200); ?>
				<section class="tal-call-logs tal-call-log-page">
					<div class="tal-call-log-hero">
						<div>
							<p class="tal-call-log-eyebrow"><?php echo esc_html__('Follow-up', 'tender-library'); ?></p>
							<h2><?php echo esc_html__('Call history', 'tender-library'); ?></h2>
							<p class="tal-call-log-intro"><?php echo esc_html__('Keep all follow-up calls for this user in one place, ordered by date and easy to edit later.', 'tender-library'); ?></p>
						</div>
						<div class="tal-call-log-count">
							<span class="tal-call-log-count-number"><?php echo esc_html((string) count($call_logs)); ?></span>
							<span class="tal-call-log-count-label"><?php echo esc_html__('entries', 'tender-library'); ?></span>
						</div>
					</div>

					<?php if ($call_log_message) : ?>
						<div class="notice <?php echo esc_attr($call_log_notice_class); ?> inline"><p><?php echo esc_html($call_log_message); ?></p></div>
					<?php endif; ?>

					<div class="tal-call-log-layout">
						<div class="tal-call-log-form-card">
							<h3><?php echo $editing_call ? esc_html__('Edit call entry', 'tender-library') : esc_html__('New call entry', 'tender-library'); ?></h3>
							<form method="post" class="tal-call-log-form">
								<?php wp_nonce_field('tal_call_log_action_' . $current_user->ID, 'tal_call_log_nonce'); ?>
								<input type="hidden" name="tal_call_log_user_id" value="<?php echo esc_attr($current_user->ID); ?>">
								<input type="hidden" name="tal_call_action" value="<?php echo $editing_call ? 'update' : 'add'; ?>">
								<?php if ($editing_call) : ?>
									<input type="hidden" name="tal_call_id" value="<?php echo esc_attr($editing_call->id); ?>">
								<?php endif; ?>

								<div class="tal-call-log-form-row">
									<div class="tal-call-field">
										<label class="tal-call-label" for="tal_call_subject"><?php _e('Title', 'tender-library'); ?></label>
										<input id="tal_call_subject" type="text" name="tal_call_subject" maxlength="255" class="regular-text" value="<?php echo esc_attr($editing_call ? $editing_call->subject : ''); ?>" required>
									</div>
									<div class="tal-call-field tal-call-date">
										<label class="tal-call-label" for="tal_call_date"><?php _e('Date', 'tender-library'); ?></label>
										<input id="tal_call_date" type="date" name="tal_call_date" value="<?php echo esc_attr($editing_call ? $editing_call->call_date : wp_date('Y-m-d')); ?>" required>
									</div>
									<div class="tal-call-field tal-call-comment">
										<label class="tal-call-label" for="tal_call_comment"><?php _e('Comment', 'tender-library'); ?></label>
										<textarea id="tal_call_comment" name="tal_call_comment" rows="6"><?php echo esc_textarea($editing_call ? wp_strip_all_tags($editing_call->comment) : ''); ?></textarea>
									</div>
								</div>
								<div class="tal-call-actions">
									<div class="tal-call-buttons">
										<button type="submit" class="button button-primary">
											<?php echo $editing_call ? esc_html__('Update', 'tender-library') : esc_html__('Save', 'tender-library'); ?>
										</button>
										<?php if ($editing_call) : ?>
											<a class="button button-secondary" href="<?php echo esc_url(tal_profile_tab_url($current_user, 'calls')); ?>">
												<?php _e('Cancel', 'tender-library'); ?>
											</a>
										<?php endif; ?>
									</div>
								</div>
							</form>
						</div>

						<div class="tal-call-log-list-card">
							<h3><?php echo esc_html__('Timeline', 'tender-library'); ?></h3>
							<?php if (!empty($call_logs)) : ?>
								<ul class="tal-call-log-list">
									<?php foreach ($call_logs as $call_log) : ?>
										<li class="tal-call-log-item">
											<div class="tal-call-log-date-badge">
												<span class="tal-call-log-date-day"><?php echo esc_html(wp_date('d', strtotime($call_log->call_date))); ?></span>
												<span class="tal-call-log-date-month"><?php echo esc_html(wp_date('M', strtotime($call_log->call_date))); ?></span>
											</div>
											<div class="tal-call-log-body">
												<p class="tal-call-log-head">
													<strong><?php echo esc_html($call_log->subject); ?></strong>
													<small><?php echo esc_html(wp_date(get_option('date_format'), strtotime($call_log->call_date))); ?></small>
												</p>
												<?php if (!empty($call_log->comment)) : ?>
													<div class="tal-call-log-comment"><?php echo wp_kses_post(wpautop($call_log->comment)); ?></div>
												<?php else : ?>
													<p class="tal-call-log-comment-empty"><?php echo esc_html__('No comment added.', 'tender-library'); ?></p>
												<?php endif; ?>
												<div class="tal-call-log-item-actions">
													<a class="button button-secondary button-small" href="<?php echo esc_url(tal_profile_tab_url($current_user, 'calls', array('tal_edit_call' => (int) $call_log->id))); ?>">
														<?php _e('Edit', 'tender-library'); ?>
													</a>
													<form method="post" class="tal-call-delete-form">
														<?php wp_nonce_field('tal_call_log_action_' . $current_user->ID, 'tal_call_log_nonce'); ?>
														<input type="hidden" name="tal_call_log_user_id" value="<?php echo esc_attr($current_user->ID); ?>">
														<input type="hidden" name="tal_call_action" value="delete">
														<input type="hidden" name="tal_call_id" value="<?php echo esc_attr($call_log->id); ?>">
														<button type="submit" class="button button-link-delete button-small" onclick="return confirm('<?php echo esc_js(__('Delete this call log?', 'tender-library')); ?>');">
															<?php _e('Delete', 'tender-library'); ?>
														</button>
													</form>
												</div>
											</div>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="no-results"><?php echo __('No calls logged yet for this user.', 'tender-library'); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</section>
			<?php else : ?>
			<div class="profile-lendings">
				<h2 class=""><?php echo __('Active lendings', 'tender-library'); ?></h2>
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
								$formatted_date = 'Invalid date';
								$formatted_lending_date = 'Invalid date';
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
										<div class="lending-date"><?php _e('Lending date', 'tender-library'); ?>: <span><?php echo $formatted_lending_date; ?></span></div>
										<div class="lending-date  <?php if ($delayed) {
																		echo "danger";
																	} ?>"><?php _e('Estimated return date', 'tender-library'); ?>: <span><?php echo $formatted_date; ?></span></div>

										<div class=" lending-actions">
											<?php if (count(tender_get_renewals_by_lending($lending->id)) > 3): ?>
												<p class="lending-renewal-limit"><?php _e('This loan cannot be extended', 'tender-library'); ?></p>
											<?php else: ?>
												<button class="lending-action lending-renew tal-button" data-action="renew" data-id="<?php echo esc_attr($lending->id); ?>">
													<?php _e('Loan renewal', 'tender-library'); ?>
												</button>
											<?php endif; ?>

											<?php if (tal_current_user_opener_or_admin()): ?>

												<button class="lending-action lending-return tal-button" data-action="return" data-id="<?php echo esc_attr($lending->id); ?>">
													<?php _e('Return', 'tender-library'); ?>
												</button>
											<?php endif; ?>
										</div>
									</div>
								</div>

								<?php if ($delayed): ?>
									<div class="delayed" aria-label="<?php echo __('Delayed', 'tender-library'); ?>">
										<img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/svg/delay.svg" alt="<?php echo __('Delayed', 'tender-library'); ?>" class="w-full h-full">
									</div>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<p class="no-results"><?php echo __('There are no active lendings right now', 'tender-library'); ?></p>
				<?php endif; ?>
			</div>
			<?php endif; ?>

 			<?php echo do_shortcode('[tender_user_reservations]'); ?>



			<div class="profile-lendings">
				<?php
				$past_lendings = tender_get_past_lendings_by_user($current_user->ID);
				if (!empty($past_lendings)) : ?>
					<h2 class=""><?php echo __('Past lendings', 'tender-library'); ?></h2>
					<div class="past-lendings">
						<?php foreach ($past_lendings as $lending) :

							try {
								$return_date  = strtotime($lending->stimated_return_date);
								$lending_date = strtotime($lending->lending_date);
								$formatted_date = wp_date(get_option('date_format'), $return_date);
								$formatted_lending_date = wp_date(get_option('date_format'), $lending_date);
							} catch (Exception $e) {
								$formatted_date = 'Invalid date';
								$formatted_lending_date = 'Invalid date';
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
										<span class="loan-date"><?php echo __('Lending', 'tender-library') . ': ' . $formatted_lending_date; ?></span>
										<span class="return-date"><?php echo  __('Return', 'tender-library') . ': ' . $formatted_date; ?></span>
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
			<div id="tal-confirmation-modal" class="tal-confirmation-modal" style="display: none;">
				<div class="wrapper">
					<h3 id="modal-message"></h3>
					<div class="actions">
						<button id="confirm-action" class="tal-button"><?php _e('Confirm', 'tender-library') ?></button>
						<button id="cancel-action" class="tal-button"><?php _e('Cancel', 'tender-library') ?></button>
						<button id="accept-action" class="tal-button" styles="display: none" data-reload="reload"><?php _e('Accept', 'tender-library') ?></button>
					</div>
			</div>
		<?php 
		return ob_get_clean();
		}
	
	}

	return $content;
}

add_filter('the_content', 'tal_profile_template');
