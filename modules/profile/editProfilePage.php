<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}

// Mostrar el formulario de edición en la página seleccionada
function tal_edit_profile_template($content)
{
	if (is_admin() || !is_main_query()) {
		return $content;
	}

	$edit_page_id = get_option('tal_edit_profile_page');

	if ($edit_page_id && is_page($edit_page_id) && is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$mensaje = '';

		if (isset($_POST['tal_edit_profile'])) {
			check_admin_referer('tal_edit_profile_action', 'tal_edit_profile_nonce');

			$user_id = get_current_user_id();
			$first_name = sanitize_text_field($_POST['first_name']);
			$last_name = sanitize_text_field($_POST['last_name']);
			$email = sanitize_email($_POST['email']);
			$phone = sanitize_text_field($_POST['phone']);

			if (!is_email($email)) {
				$mensaje = '<p class="error">Correo electrónico no válido.</p>';
			} elseif (email_exists($email) && $email !== $current_user->user_email) {
				$mensaje = '<p class="error">Este correo ya está en uso.</p>';
			} else {
				wp_update_user([
					'ID'         => $user_id,
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'user_email' => $email,
				]);

				update_user_meta($user_id, 'phone_number', $phone);
				$mensaje = '<p class="success">Perfil actualizado correctamente.</p>';
			}
		}

		ob_start(); ?>
		<div class="editar-perfil">
			<h2>Editar Perfil</h2>
			<?php echo $mensaje; ?>
			<form method="post">
				<?php wp_nonce_field('tal_edit_profile_action', 'tal_edit_profile_nonce'); ?>
				<label for="first_name">Nombre:</label>
				<input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" required>

				<label for="last_name">Apellido:</label>
				<input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" required>

				<label for="email">Correo electrónico:</label>
				<input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>

				<label for="phone">Teléfono:</label>
				<input type="text" name="phone" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone_number', true)); ?>">

				<button type="submit" name="tal_edit_profile">Guardar Cambios</button>
			</form>
			<a href="<?php echo esc_url(get_permalink(get_option('tal_profile_page'))); ?>">Volver al perfil</a>
		</div>
<?php return ob_get_clean();
	}

	return $content;
}
add_filter('the_content', 'tal_edit_profile_template');
