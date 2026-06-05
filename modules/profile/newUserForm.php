<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$mensaje = '';
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'newsletter' => 0,
];

if (isset($_POST['tal_create_user'])) {
    check_admin_referer('tal_create_user_action', 'tal_create_user_nonce');

    $form_data['first_name'] = sanitize_text_field($_POST['first_name'] ?? '');
    $form_data['last_name']  = sanitize_text_field($_POST['last_name'] ?? '');
    $form_data['email']      = sanitize_email($_POST['email'] ?? '');
    $form_data['phone']      = sanitize_text_field($_POST['phone'] ?? '');
    $form_data['newsletter'] = isset($_POST['newsletter']) ? 1 : 0;
    $success = false;
    // Validaciones
    if (empty($form_data['first_name']) || empty($form_data['last_name'])) {
        $mensaje = '<p class="text-error text-sm font-medium">' . __('Name and surname are required.', 'tender-library') . '</p>';
    } elseif (!is_email($form_data['email'])) {
        $mensaje = '<p class="text-error text-sm font-medium">' . __('Invalid email address.', 'tender-library') . '</p>';
    } elseif (email_exists($form_data['email'])) {
        $mensaje = '<p class="text-error text-sm font-medium">' . __('This email is already in use.', 'tender-library') . '</p>';
    } else {
        // Generar un username único
        $base_user = sanitize_user(explode('@', $form_data['email'])[0], true);
        $username  = $base_user;
        $i = 1;
        while (username_exists($username)) {
            $username = $base_user . $i;
            $i++;
        }
        $random_password = wp_generate_password(12, true);

        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $form_data['email'],
            'first_name'   => $form_data['first_name'],
            'last_name'    => $form_data['last_name'],
            'user_pass'    => $random_password,
            'role'         => 'reader'
        ]);
        if (is_wp_error($user_id)) {
            $mensaje = '<p class="text-error text-sm font-medium">' . __('Could not create user. Please try again.', 'tender-library') . '</p>';
        } else {
            // Extra: Guarda campos personalizados
            update_user_meta($user_id, 'phone_number', $form_data['phone']);
            update_user_meta($user_id, 'newsletter', $form_data['newsletter']);
            // Si usas Carbon Fields también:
            if (function_exists('carbon_set_user_meta')) {
                carbon_set_user_meta($user_id, 'phone_number', $form_data['phone']);
                carbon_set_user_meta($user_id, 'newsletter', $form_data['newsletter']);
            }
            // Opcional: enviar email de bienvenida
            wp_new_user_notification($user_id, null, 'user');
            $success = true;
            $mensaje = '<p class="text-success text-sm font-medium">' . __('User created successfully.', 'tender-library') . '</p>';
            $form_data = [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'newsletter' => 0,
            ];
        }
    }
}

?>
<div class="profile">
    <div class="user-info">
        <form method="post">
            <?php wp_nonce_field('tal_create_user_action', 'tal_create_user_nonce'); ?>

            <ul class="">
                <li class="form-row">
                    <span class="label"><?php _e('Name', 'tender-library') ?>:</span>
                    <span class="data"><input type="text" name="first_name" value="<?php echo esc_attr($form_data['first_name']); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
                </li>
                <li class="form-row">
                    <span class="label"><?php _e('Last Name', 'tender-library') ?>:</span>
                    <span class="data"><input type="text" name="last_name" value="<?php echo esc_attr($form_data['last_name']); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
                </li>
                <li class="form-row">
                    <span class="label"><?php _e('E-mail', 'tender-library') ?>:</span>
                    <span class="data"><input type="email" name="email" value="<?php echo esc_attr($form_data['email']); ?>" required class="w-full p-1.5 border rounded text-sm"></span>
                </li>
                <li class="form-row">
                    <span class="label"><?php _e('Phone', 'tender-library') ?>:</span>
                    <span class="data"><input type="text" name="phone" value="<?php echo esc_attr($form_data['phone']); ?>" class="w-full p-1.5 border rounded text-sm"></span>
                </li>
                <li class="form-row">
                    <span class="label"><?php _e('Consents to receive news by e-mail:', 'tender-library') ?></span>
                    <span class="data"><input type="checkbox" name="newsletter" <?php checked($form_data['newsletter'], 1); ?> class="w-full p-1.5 border rounded text-sm"></span>
                </li>
                <li class="form-row">
                    <button type="submit" name="tal_create_user" class="tal-button"><?php echo __('Create user', 'tender-library'); ?></button>
                </li>
            </ul>
        </form>
		<?php if (!empty($mensaje)): ?>
            <div id="tal-confirmation-modal" class="tal-confirmation-modal">
                <div class="wrapper">
                    <?php echo $mensaje; ?>
                    <div class="actions">
                        <button id="accept-action" class="tal-button" <?php if ($success) {echo "data-reload='reload'";}?>><?php _e('Accept', 'tender-library') ?></button>
                    </div>
            </div>
        <?php endif; ?>
    </div>
</div>