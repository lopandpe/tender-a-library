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
		$current_user = wp_get_current_user();
		if ($username) {
			// Si hay un 'tender_profile_user' en la URL, obtener ese usuario
			$user = get_user_by('slug', $username);

			if ($user) {
				// Verificar que el usuario actual tiene acceso al perfil (el propio usuario o un "opener" o "administrator")
				if ($user->ID !== $current_user->ID && !in_array('opener', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
					wp_redirect(home_url()); // Redirigir al home si no tiene permisos
					exit;
				}

				// Mostrar el perfil del usuario solicitado
				ob_start(); ?>
				<div class="perfil-usuario">
					<h2>Perfil de <?php echo esc_html($user->display_name); ?></h2>
					<p><strong>Nombre:</strong> <?php echo esc_html($user->first_name); ?></p>
					<p><strong>Apellido:</strong> <?php echo esc_html($user->last_name); ?></p>
					<p><strong>Email:</strong> <?php echo esc_html($user->user_email); ?></p>
					<p><strong>Teléfono:</strong> <?php echo esc_html(get_user_meta($user->ID, 'phone_number', true)); ?></p>

					<?php if ($user->ID === $current_user->ID): ?>
						<!-- Solo mostrar el enlace de editar perfil si es el mismo usuario -->
						<a href="<?php echo esc_url(get_permalink(get_option('tal_edit_profile_page'))); ?>" class="btn-editar">Editar Perfil</a>
					<?php endif; ?>
					<a href="<?php echo wp_logout_url(home_url()); ?>">Cerrar sesión</a>
				</div>
			<?php return ob_get_clean();
			} else {
				// Si el usuario no se encuentra, redirigir al home
				wp_redirect(home_url());
				exit;
			}
		} else {
			// Si no hay 'tender_profile' en la URL, mostrar el perfil del usuario actual
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
	}

	return $content;
}

add_filter('the_content', 'tal_profile_template');
