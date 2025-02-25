<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
	exit;
}

// Mostrar el perfil en la página seleccionada
function tal_profile_template($content)
{
	if (is_admin() || !is_main_query()) {
		return $content;
	}

	$profile_page_id = get_option('tal_profile_page');

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

		$current_user = wp_get_current_user();
		ob_start(); ?>

		<div class="perfil-usuario">
			<h2>Perfil de <?php echo esc_html($current_user->display_name); ?></h2>
			<p><strong>Nombre:</strong> <?php echo esc_html($current_user->first_name); ?></p>
			<p><strong>Apellido:</strong> <?php echo esc_html($current_user->last_name); ?></p>
			<p><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
			<p><strong>Teléfono:</strong> <?php echo esc_html(get_user_meta($current_user->ID, 'phone_number', true)); ?></p>

			<a href="<?php echo esc_url(get_permalink(get_option('tal_edit_profile_page'))); ?>" class="btn-editar">Editar Perfil</a>
			<a href="<?php echo wp_logout_url(home_url()); ?>">Cerrar sesión</a>
		</div>

<?php return ob_get_clean();
	}

	return $content;
}
add_filter('the_content', 'tal_profile_template');
