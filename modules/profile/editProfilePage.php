<?php
// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Display the edit profile form on the selected page
function tal_edit_profile_template($content)
{
	$edit_page_id = get_option('tal_edit_profile_page');

	if ($edit_page_id && is_page($edit_page_id) && is_user_logged_in()) {

		$mensaje = "";
		$username = get_query_var('tal_profile_user');
		$current_user = $username ? get_user_by('slug', $username) : wp_get_current_user();
		// Comprobación para ver perfil:
		
		if ( tal_can_edit_profile($current_user->ID) === false ) {
			ob_start(); 
			?>
			<div class="no-results">
				<div class="">
					<div class="">
						<p class=""><?php echo __('You do not have permission to view this page.', 'tender-a-library'); ?></p>
					</div>
				</div>
			</div>
			<?php return ob_get_clean();
		}

		if (isset($_POST['tal_edit_profile'])) {
			check_admin_referer('tal_edit_profile_action', 'tal_edit_profile_nonce');

			$user_id = $current_user->ID;
			$first_name = sanitize_text_field($_POST['first_name']);
			$last_name = sanitize_text_field($_POST['last_name']);
			$email = sanitize_email($_POST['email']);
			$phone = sanitize_text_field($_POST['phone']);
			$newsletter = isset($_POST['newsletter']) ? 1 : 0;

			if (!is_email($email)) {
				$mensaje = '<p class="text-error text-sm font-medium">' . __('Invalid email address.', 'tender-a-library') . '</p>';
			} elseif (email_exists($email) && $email !== $current_user->user_email) {
				$mensaje = '<p class="text-error text-sm font-medium">' . __('This email is already in use.', 'tender-a-library') . '</p>';
			} else {
				wp_update_user([
					'ID'         => $user_id,
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'user_email' => $email,
				]);
				carbon_set_user_meta($user_id, 'phone_number', $phone);
				carbon_set_user_meta($user_id, 'newsletter', $newsletter);
				update_user_meta($user_id, 'phone_number', $phone);
				$mensaje = '<p class="text-success text-sm font-medium">' . __('Profile updated successfully.', 'tender-a-library') . '</p>';

				$current_user = get_user($user_id);
			}
		}

		ob_start(); ?>
		<div class="profile">
			<div class="user-info">
				<form method="post">
					<?php wp_nonce_field('tal_edit_profile_action', 'tal_edit_profile_nonce'); ?>

					<ul class="">
						<li class="form-row">
							<span class="label"><?php _e('Name', 'tender-a-library') ?>:</span>
							<span class="data"><input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
						</li>
						<li class="form-row">
							<span class="label"><?php _e('Last Name', 'tender-a-library') ?>:</span>
							<span class="data"><input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
						</li>
						<li class="form-row">
							<span class="label"><?php _e('E-mail', 'tender-a-library') ?>:</span>
							<span class="data"><input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
						</li>
						<li class="form-row">
							<span class="label"><?php _e('Phone', 'tender-a-library') ?>:</span>
							<span class="data"><input type="text" name="phone" value="<?php echo esc_attr(carbon_get_user_meta($current_user->ID, 'phone_number')); ?>" class="w-full p-1.5 border rounded text-sm"></span>
						</li>
						<li class="form-row">
							<span class="label"><?php _e('Consents to receive news by e-mail:', 'tender-a-library') ?></span>
							<span class="data"><input type="checkbox" name="newsletter" <?php echo carbon_get_user_meta($current_user->ID, 'newsletter') ? 'checked' : ''; ?> class="w-full p-1.5 border rounded text-sm"></span>
						</li>
						<li class="form-row">
							<button type="submit" name="tal_edit_profile" class="tal-button"><?php echo __('Save Changes', 'tender-a-library'); ?></button>

						</li>
					</ul>
				</form>
				<div class="message success"><?php echo $mensaje; ?></div>
				<p><a href="<?php echo esc_url(get_permalink(get_option('tal_profile_page'))) . $current_user->user_nicename; ?>" class="primary go-back"><?php echo __('Back to Profile', 'tender-a-library'); ?></a></p>
			</div>

		</div>
<?php return ob_get_clean();
	}

	return $content;
}
add_filter('the_content', 'tal_edit_profile_template');
