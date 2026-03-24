<?php

function tender_book_data_render_callback($block_attributes, $block_content)
{
    static $script_enqueued = false;
    $block_classes = tender_get_block_classes(
        $block_attributes,
        'wp-block-tender-a-library-book-data'
    );
    $current_post_id = get_the_ID();
    $author = carbon_get_post_meta($current_post_id, 'tender_book_author');
    $publisher = carbon_get_post_meta($current_post_id, 'tender_book_publisher');
    $year = carbon_get_post_meta($current_post_id, 'tender_book_year');
    $section = carbon_get_post_meta($current_post_id, 'tender_book_section');
    $section = count($section) ? get_term($section[0]['id'], 'tender_section') : null;

    ob_start();
?>
    <section class="<?php echo $block_classes; ?>">
        <div class="block-container">
            <h2><?php _e("Book data", "tender-a-library"); ?></h2>
            <ul class="tender-book-meta">
                <li><strong><?php _e("Author", "tender-a-library"); ?></strong>: <?php echo $author; ?></li>
                <li>
                    <strong><?php _e("Publisher", "tender-a-library"); ?></strong>: <?php echo $publisher; ?>
                </li>
                <?php if ($section) :

                    $url = get_library_search_url_with_filters([
                        'sections' => [$section->slug],
                    ]);


                ?>
                    <li>
                        <strong><?php _e("Library section", "tender-a-library"); ?></strong>:
                        <?php echo '<a href="' . esc_url($url) . '">' . esc_html($section->name) . '</a>'; ?>
                    </li>
                <?php endif; ?>
                <li><strong><?php _e("Publication year", "tender-a-library"); ?></strong>: <?php echo $year; ?></li>
                <?php if ($section) :
                    $section_number = carbon_get_term_meta($section->term_id, 'tender_section_number');
                    $section_number .= ', ' . carbon_get_post_meta($current_post_id, 'tender_book_sig1') . ' - ' . carbon_get_post_meta($current_post_id, 'tender_book_sig2');
                ?>
                    <li>
                        <strong><?php _e("Signature", "tender-a-library"); ?></strong>: <?php echo $section_number; ?>
                    </li>
                <?php endif; ?>
            </ul>
            <div id="lending-info" class="tender-lending-info">
                <?php
                    $is_logged_in = is_user_logged_in();
                    $book_id      = $current_post_id;
                    $is_available = tender_can_book_be_lent($book_id);
                    $lendings     = tender_get_active_lendings_by_book($book_id);

                    // NUEVO: estado de reserva del libro
                    $has_active_res = function_exists('tal_has_active_reservation') ? tal_has_active_reservation($book_id) : false;
                    $res_user_id    = function_exists('tal_get_active_reservation_user') ? tal_get_active_reservation_user($book_id) : null;

                    if (!$is_logged_in) {
                        echo $is_available
                            ? '<p class="tender-status tender-available">' . __("Available for loan.", "tender-a-library") . '</p>'
                            : '<p class="tender-status tender-unavailable">' . __("Not available.", "tender-a-library") . '</p>';

                    } else {
                        $current_user = wp_get_current_user();
                        $user_id      = (int) $current_user->ID;
                        $roles        = (array) $current_user->roles;

                        $is_staff = in_array('opener', $roles, true) || in_array('administrator', $roles, true) || in_array('librarian', $roles, true);

                        // --- STAFF ---
                        if ($is_staff) {
                            if ($is_available) {
                                // Disponible => prestar (NO reservar)
                                echo '<p class="tender-status tender-available">' . __("Available for loan.", "tender-a-library") . '</p>';
                                echo '<button id="lend_book" class="tender-button tender-lend-button">' . __("Lend this book", "tender-a-library") . '</button>';
                            } else {
                                // No disponible
                                echo '<p class="tender-status tender-unavailable">' . __("Not available.", "tender-a-library") . '</p>';

                                if ($has_active_res) {
                                    if ($res_user_id && (int) $res_user_id === $user_id) {
                                        echo '<p class="tender-note">' . __("You already have an active reservation for this book.", "tender-a-library") . '</p>';
                                    } else {
                                        echo '<p class="tender-note">' . __("This book already has an active reservation.", "tender-a-library") . '</p>';
                                    }
                                    // Sin botón de reserva
                                } else {
                                    // No hay reserva activa -> permitir reservar
                                    echo '<button id="reserve_book" class="tender-button tender-reserve-button">' . __("Reserve this book", "tender-a-library") . '</button>';
                                }
                            }

                        // --- LECTOR ---
                        } elseif (in_array('reader', $roles, true)) {

                            if (tender_user_has_borrowed_book($user_id, $book_id)) {
                                // Tiene un préstamo activo de este libro
                                echo '<a href="' . esc_url(get_author_posts_url($user_id)) . '" class="tender-button tender-renew-button">' . __("Renew this lending", "tender-a-library") . '</a>';

                            } elseif ($is_available) {
                                // Disponible => solo aviso (NO reservar)
                                echo '<p class="tender-status tender-available">' . __("Available for loan.", "tender-a-library") . '</p>';

                            } else {
                                // No disponible
                                if ($has_active_res) {
                                    if ($res_user_id && (int) $res_user_id === $user_id) {
                                        echo '<p class="tender-note">' . __("You already have an active reservation for this book.", "tender-a-library") . '</p>';
                                    } else {
                                        echo '<p class="tender-note">' . __("This book already has an active reservation by another user.", "tender-a-library") . '</p>';
                                    }
                                    // Sin botón de reserva
                                } else {
                                    echo '<p class="tender-status tender-unavailable">' . __("Not available.", "tender-a-library") . '</p>';
                                    echo '<button id="reserve_book" class="tender-button tender-reserve-button">' . __("Reserve this book", "tender-a-library") . '</button>';
                                }
                            }
                        }
                    }

                    if (!empty($lendings) && tal_current_user_opener_or_admin()) {
                        echo '<div class="tender-active-lendings">';
                        echo (count($lendings) > 1)
                            ? '<h4>' . __('Current active lendings', 'tender-a-library') . '</h4>'
                            : '<h4>' . __('Current active lending', 'tender-a-library') . '</h4>';

                        echo '<ul class="tender-lendings-list">';
                        foreach ($lendings as $index => $lending) {
                            $return_date = date_i18n(get_option('date_format'), strtotime($lending->stimated_return_date));
                            $lending_user = get_userdata($lending->user_id);
                            $user_name = $lending_user ? $lending_user->display_name : __('Unknown user', 'tender-a-library');
                            $user_profile = get_user_profile_url_by_id($lending->user_id);

                            echo '<li class="tender-lending-item">';
                            if (count($lendings) > 1) {
                                echo '<div class="tender-lending-number">' . sprintf(__('Lending #%d', 'tender-a-library'), ($index + 1)) . '</div>';
                            }
                            echo '<div class="tender-lending-details">';
                            echo '<div class="tender-lending-user">' . __('User:', 'tender-a-library') . ' <strong>' . '<a href="' . esc_url(is_array($user_profile) ? $user_profile['profile'] : '') . '">' . esc_html($user_name) . '</a></strong></div>';
                            echo '<div class="tender-lending-date">' . __('Estimated return:', 'tender-a-library') . ' <strong>' . esc_html($return_date) . '</strong></div>';
                            echo '</div>';
                            echo '</li>';
                        }
                        echo '</ul></div>';
                    }
                ?>
            </div>
        </div>
    </section>

    <!-- Modal para préstamos -->
    <div id="tender-lend-modal" class="tender-modal">
        <div class="tender-modal-content">
            <button class="tender-modal-close tender-top-close">&times;</button>
            <h2 class="tender-modal-title"><?php _e('Lend this book', 'tender-a-library'); ?></h2>
            <p class="tender-modal-subtitle"><?php _e('Select the user who will borrow this book', 'tender-a-library'); ?></p>

            <form id="tender-lending-form" class="tender-form">
                <input type="hidden" name="book_id" id="book-id" value="<?php echo $current_post_id; ?>">

                <div class="tender-form-group">
                    <label for="tender-user-search" class="tender-form-label">
                        <?php _e('Search user', 'tender-a-library'); ?>
                    </label>
                    <div class="tender-search-container">
                        <input type="text" id="tender-user-search" class="tender-form-input" autocomplete="off" placeholder="<?php _e('Name, email, or phone', 'tender-a-library'); ?>">
                        <div id="tender-search-loading" class="tender-search-loading" style="display:none;">
                            <div class="tender-spinner"></div>
                        </div>
                    </div>
                    <select id="tender-user-id" class="tender-form-select" required style="display:none;"></select>
                    <p class="tender-form-hint"><?php _e('Start typing to search for users', 'tender-a-library'); ?></p>
                </div>

                <div id="tender-response-message" class="tender-response-message"></div>

                <div class="tender-form-actions">
                    <button type="button" class="tender-button tender-button-secondary tender-modal-close"><?php _e('Cancel', 'tender-a-library'); ?></button>
                    <button type="submit" class="tender-button tender-button-primary" disabled>
                        <span class="tender-button-text"><?php _e('Create Lending', 'tender-a-library'); ?></span>
                        <span class="tender-button-spinner" style="display:none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal para reservas -->
    <div id="tender-reserve-modal" class="tender-modal">
        <div class="tender-modal-content">
            <button class="tender-modal-close tender-top-close">&times;</button>
            <h2 class="tender-modal-title"><?php _e('Reserve this book', 'tender-a-library'); ?></h2>
            <h3 class="tender-modal-title"><?php _e('A reservation will be created for user: ', 'tender-a-library'); ?><?php echo esc_html($current_user->display_name); ?></h3>
            <form id="tender-reservation-form" class="tender-form">
                <input type="hidden" name="book_id" id="reservation-book-id" value="<?php echo $current_post_id; ?>">
                <input type="hidden" name="user_id" id="reservation-user-id" value="<?php echo $current_user->ID; ?>">

                <div id="tender-reservation-message" class="tender-response-message"></div>

                <div class="tender-form-actions">
                    <button type="button" class="tender-button tender-button-secondary tender-modal-close"><?php _e('Cancel', 'tender-a-library'); ?></button>
                    <button type="submit" class="tender-button tender-button-primary">
                        <span class="tender-button-text"><?php _e('Create Reservation', 'tender-a-library'); ?></span>
                        <span class="tender-button-spinner" style="display:none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            var modal = $('#tender-lend-modal');
            var reservationModal = $('#tender-reserve-modal');
            var userSearch = $('#tender-user-search');
            var userIdSelect = $('#tender-user-id');
            var submit = $('#tender-lending-form button[type="submit"]');
            var searchUsersNonce = '<?php echo esc_js(wp_create_nonce('tal_search_users')); ?>';
            var createLendingNonce = '<?php echo esc_js(wp_create_nonce('tal_create_lending')); ?>';
            var createReservationNonce = '<?php echo esc_js(wp_create_nonce('tal_create_reservation')); ?>';
            var typingTimer;
            var doneTypingInterval = 400;

            // Abrir modal
            $(document).on('click', '#lend_book', function(e) {
                e.preventDefault();
                modal.addClass('tender-modal-active');
                $('body').addClass('tender-modal-open');
                $('.tender-response-message').html('');
            });

            $(document).on('click', '#reserve_book', function(e) {
                e.preventDefault();
                reservationModal.addClass('tender-modal-active');
                $('body').addClass('tender-modal-open');
                $('.tender-response-message').html('');
            });

            // Cerrar modal
            $('.tender-modal-close').on('click', function() {
                closeModal();
            });

            // Cerrar al hacer clic fuera del modal
            $(document).on('click', function(e) {
                if ($(e.target).is(modal)) {
                    closeModal();
                }
            });

            function closeModal() {
                $('.tender-modal').removeClass('tender-modal-active');
                $('body').removeClass('tender-modal-open');
                userIdSelect.hide().empty();
                userSearch.val('');
                $('#tender-response-message').empty();
                $('#tender-search-loading').hide();
            }

            // Búsqueda de usuarios
            userSearch.on('input', function() {
                clearTimeout(typingTimer);
                var searchTerm = $(this).val();
                if (searchTerm.length < 2) {
                    userIdSelect.hide().empty();
                    return;
                }

                $('#tender-search-loading').show();
                typingTimer = setTimeout(function() {
                    userIdSelect.html("");
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'tender_search_users',
                        query: searchTerm,
                        nonce: searchUsersNonce
                    }, function(response) {
                        $('#tender-search-loading').hide();
                        if (response) {
                            $('.tender-search-container').addClass('active');
                            let users = JSON.parse(response).data;
                            users.forEach(user => {
                                userIdSelect.append(`<option value="${user.ID}">${user.display_name} (${user.user_email})</option>`);
                            });
                            userIdSelect.show();
                            submit.prop('disabled', false);
                        } else {
                            userIdSelect.html('<option value=""><?php _e('No users found', 'tender-a-library'); ?></option>').show();
                            $('.tender-search-container').removeClass('active');
                            submit.prop('disabled', true);
                        }
                    }, 'html');
                }, doneTypingInterval);
            });

            // Enviar formulario
            $('#tender-lending-form').submit(function(e) {
                e.preventDefault();
                submit.prop('disabled', true);
                // Validación
                if (!userIdSelect.val()) {
                    $('#tender-response-message').html('<div class="tender-alert tender-alert-error"><?php _e('Please select a user', 'tender-a-library'); ?></div>');
                    return;
                }

                var submitButton = $(this).find('.tender-button-primary');
                var buttonText = submitButton.find('.tender-button-text');
                var buttonSpinner = submitButton.find('.tender-button-spinner');

                buttonText.hide();
                buttonSpinner.show();

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'tender_create_lending_ajax',
                    book_id: $('#book-id').val(),
                    user_id: userIdSelect.val(),
                    nonce: createLendingNonce
                }, function(response) {
                    buttonText.show();
                    buttonSpinner.hide();

                    if (response.success) {
                        $('#tender-response-message').html('<div class="tender-alert tender-alert-success">' + response.data.message + '</div>');

                        // Actualizar UI después de éxito
                        setTimeout(function() {
                            closeModal();

                            // Mostrar mensaje de éxito temporal
                            $('.tender-lending-info').prepend(
                                '<div class="tender-success-message">' +
                                '<div class="tender-success-icon">✓</div>' +
                                '<div>' + response.data.message + '</div>' +
                                '</div>'
                            );

                            // Recargar después de un breve retraso
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }, 1500);
                    } else {
                        $('#tender-response-message').html('<div class="tender-alert tender-alert-error">' + response.data.message + '</div>');
                    }
                }, 'json').fail(function(jqXHR) {
                    buttonText.show();
                    buttonSpinner.hide();

                    var errorMsg = jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message ?
                        jqXHR.responseJSON.data.message :
                        '<?php _e("An error occurred. Please try again.", "tender-a-library"); ?>';

                    $('#tender-response-message').html('<div class="tender-alert tender-alert-error">' + errorMsg + '</div>');
                });
            });
            // Enviar formulario Reserva
            $('#tender-reservation-form').submit(function(e) {
                e.preventDefault();
                //submit.prop('disabled', true);
                var submitButton = $(this).find('.tender-button-primary');
                var buttonText = submitButton.find('.tender-button-text');
                var buttonSpinner = submitButton.find('.tender-button-spinner');

                buttonText.hide();
                buttonSpinner.show();

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'tender_create_reservation_ajax',
                    book_id: $('#reservation-book-id').val(),
                    user_id: $('#reservation-user-id').val(),
                    nonce: createReservationNonce
                }, function(response) {
                    buttonText.show();
                    buttonSpinner.hide();

                    if (response.success) {
                        $('#tender-reservation-message').html('<div class="tender-alert tender-alert-success">' + response.data.message + '</div>');

                        // Actualizar UI después de éxito
                        setTimeout(function() {
                            closeModal();

                            // Mostrar mensaje de éxito temporal
                            $('.tender-lending-info').prepend(
                                '<div class="tender-success-message">' +
                                '<div class="tender-success-icon">✓</div>' +
                                '<div>' + response.data.message + '</div>' +
                                '</div>'
                            );

                            // Recargar después de un breve retraso
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }, 1500);
                    } else {
                        $('#tender-reservation-message').html('<div class="tender-alert tender-alert-error">' + response.data.message + '</div>');
                    }
                }, 'json').fail(function(jqXHR) {
                    buttonText.show();
                    buttonSpinner.hide();

                    var errorMsg = jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message ?
                        jqXHR.responseJSON.data.message :
                        '<?php _e("An error occurred. Please try again.", "tender-a-library"); ?>';

                    $('#tender-reservation-message').html('<div class="tender-alert tender-alert-error">' + errorMsg + '</div>');
                });
            });
        });
    </script>

<?php

    // Encolar scripts solo una vez por página
    if (!$script_enqueued) {
        wp_enqueue_script('jquery');
        $script_enqueued = true;
    }

    return ob_get_clean();
}
