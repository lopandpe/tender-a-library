<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Mostrar el perfil en la página seleccionada
function tal_users_list_template($content)
{
    $users_list_page_id = get_option('tal_users_list_page');

    // Verificar si estamos en la página de perfil y si tenemos la opción habilitada
    if ($users_list_page_id && is_page($users_list_page_id)) {
        if (!is_user_logged_in()) {
            wp_redirect(tender_profile_url());
            exit;
        }

        $current_user = wp_get_current_user();

        if (!$current_user || !tal_can_see_users_list()) {
            ob_start(); ?>
            <div class="no-results">
                <div class="">
                    <div class="">
                        <p class=""><?php echo __('You do not have permission to view this page.', 'tender-a-library'); ?></p>
                    </div>
                </div>
            </div>

        <?php return ob_get_clean();
        }


        // Mostrar la lista de usuarios
        $users = get_users(array(
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => 10,
        ));

        ob_start(); ?>

        <div class="users-list">

            <fieldset class="tender-fieldset">
                <legend>
                    <?php _e('Search user', 'tender-a-library'); ?>
                </legend>
                <div class="tender-search-container">
                    <input type="text" id="tender-user-search" class="tender-form-input" autocomplete="off" placeholder="<?php _e('Name, email, or phone', 'tender-a-library'); ?>">
                    <div id="tender-search-loading" class="tender-search-loading" style="display:none;">
                        <div class="tender-spinner"></div>
                    </div>
                </div>
                <ul id="users-list" class="tender-users-list">
                    <li><?php _e('Start typing to search for users', 'tender-a-library'); ?>
                </ul>
            </fieldset>
            <fieldset class="tender-fieldset">
                <legend>
                    <?php _e('Latest users created', 'tender-a-library'); ?>
                </legend>
                <ul id="users-list" class="tender-users-list">
                    <?php if ($users) : ?>
                        <?php foreach ($users as $user) : ?>
                            <li>
                                <a href="<?php echo esc_url(get_user_profile_url_by_id($user->ID)['profile']); ?>">
                                    <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e('No users found', 'tender-a-library'); ?></li>
                    <?php endif; ?>
                </ul>
            </fieldset>
            <fieldset class="tender-fieldset">
                <legend>
                    <?php _e('Create new user (reader)', 'tender-a-library'); ?>
                </legend>
                <?php require_once 'newUserForm.php'; ?>
            </fieldset>
            <script>
                jQuery(document).ready(function($) {
                    var userSearch = $('#tender-user-search');
                    var usersList = $('#users-list');
                    var typingTimer;
                    var doneTypingInterval = 400;

                    // Búsqueda de usuarios
                    userSearch.on('input', function() {
                        clearTimeout(typingTimer);
                        var searchTerm = $(this).val();
                        if (searchTerm.length < 2) {
                            usersList.hide().empty();
                            return;
                        }

                        $('#tender-search-loading').show();
                        typingTimer = setTimeout(function() {
                            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                action: 'tender_search_users',
                                query: searchTerm
                            }, function(response) {
                                $('#tender-search-loading').hide();
                                if (response) {
                                    let users = JSON.parse(response).data;
                                    users.forEach(user => {
                                        usersList.append(`<li><a href="${user.user_link}">${user.display_name} (${user.user_email})</a></option>`);
                                    });
                                    usersList.show();
                                } else {
                                    usersList.html('<li><?php _e('No users found', 'tender-a-library'); ?></li>').show();
                                    $('.tender-search-container').removeClass('active');
                                }
                            }, 'html');
                        }, doneTypingInterval);
                    });
                });
            </script>

    <?php return ob_get_clean();
    }

    return $content;
}

add_filter('the_content', 'tal_users_list_template');
