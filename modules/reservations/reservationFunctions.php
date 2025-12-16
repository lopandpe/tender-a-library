<?php

if (!defined('ABSPATH')) {
	exit;
}


function tal_book_is_on_loan($book_id){
    return !tender_can_book_be_lent($book_id);
}

function tal_has_active_reservation($book_id){
    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';

    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $res_table
         WHERE book_id = %d
           AND (
                 status = 'pending'
                 OR (status = 'available'
                     AND (pickup_exclusive_until IS NULL OR pickup_exclusive_until >= %s)
                 )
               )",
        $book_id, current_time('mysql')
    ));
}

function tal_get_user_active_reservations( $user_id ) {
    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';

    $sql = $wpdb->prepare(
        "SELECT r.*
               , p.post_title AS book_title
         FROM $res_table r
         INNER JOIN {$wpdb->posts} p ON p.ID = r.book_id
         WHERE r.user_id = %d
           AND (
                 r.status = 'pending'
                 OR (r.status = 'available'
                     AND (r.pickup_exclusive_until IS NULL OR r.pickup_exclusive_until >= %s)
                 )
               )
         ORDER BY r.id ASC",
        (int) $user_id, current_time('mysql')
    );

    return $wpdb->get_results( $sql );
}

function tal_get_active_reservation_user($book_id){
    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';

    return $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $res_table
         WHERE book_id = %d
           AND (
                 status = 'pending'
                 OR (status = 'available'
                     AND (pickup_exclusive_until IS NULL OR pickup_exclusive_until >= %s)
                 )
               )
         ORDER BY id ASC
         LIMIT 1",
        $book_id, current_time('mysql')
    ));
}

function tal_mark_reservation_as_available($book_id){
    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';
    error_log('Fetching active reservation for book ID: ' . $book_id);
    $reservation = tal_get_active_reservation_on_book($book_id);
    error_log('Active reservation found: ' . print_r($reservation, true));
    $now = current_time('mysql');
    $ten_days = date('Y-m-d H:i:s', strtotime('+10 days', strtotime($now)));
    $upd = $wpdb->update(
        $res_table,
        [
            'status' => 'available',
            'available_at' => $now,
            'pickup_exclusive_until' => $ten_days
        ],
        ['ID' => $reservation->id],
        ['%s', '%s', '%s'],
        ['%d']
    );
    error_log('Reservation update result: ' . $upd);

    tal_notify_availability_of_reservation($reservation, $book_id);

    return $upd;
}

function tal_finish_reservation_proccess($book_id){

    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';
    $reservation = tal_get_active_reservation_on_book($book_id);
    $upd = $wpdb->update(
        $res_table,
        ['status' => 'fulfilled'],
        ['ID' => $reservation->id],
        ['%s'],
        ['%d']
    );

    return $upd;
}

function tal_get_active_reservation_on_book($book_id){
    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';

    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $res_table
         WHERE book_id = %d
           AND (
                 status = 'pending'
                 OR (status = 'available'
                     AND (pickup_exclusive_until IS NULL OR pickup_exclusive_until >= %s)
                 )
               )
         ORDER BY id ASC
         LIMIT 1",
        $book_id, current_time('mysql')
    ));
}

function tal_create_reservation($book_id, $user_id){
    if(!get_post($book_id) || !get_user($user_id)){
        return new WP_Error('invalid_data', __('Invalid book or user', 'tender-a-library'));
    }
    if(!tal_book_is_on_loan($book_id)){
        return new WP_Error('not_on_loan', __('This book is not currently on loan', 'tender-a-library'));
    }
    if(tal_has_active_reservation($book_id)){
        return new WP_Error('has_reservation', __('This book has already a reservation', 'tender-a-library'));
    }

    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations';
    $now = current_time('mysql');

    $wpdb->query('START TRANSACTION');

    // BLOQUEO: comprobar y bloquear reservas activas de este libro
    $check_sql = $wpdb->prepare(
        "SELECT id FROM $res_table
         WHERE book_id = %d
           AND (
                 status = 'pending'
                 OR (status = 'available'
                     AND (pickup_exclusive_until IS NULL OR pickup_exclusive_until >= %s)
                 )
               )
         FOR UPDATE",
        $book_id, $now
    );
    $has_active = $wpdb->get_var($check_sql);

    if($has_active){
        $wpdb->query('ROLLBACK');
        return new WP_Error('race_condition', __('Another reservation has been made right now', 'tender-a-library'));
    }

    $ok = $wpdb->insert($res_table, [
        'book_id'          => $book_id,
        'user_id'          => $user_id,
        'reservation_date' => $now,
        'status'           => 'pending',
    ], ['%d','%d','%s','%s']);

    if(!$ok){
        $wpdb->query('ROLLBACK');
        return new WP_Error('db_error', __('Error creating reservation', 'tender-a-library'));
    }

    $wpdb->query('COMMIT');

    $res_id = $wpdb->insert_id;
    tal_notify_current_borrower_about_hold($book_id);
    return $res_id;
}


function tender_create_reservation_ajax()
{
	// Verificar que los datos necesarios están presentes
	if (!isset($_POST['book_id'], $_POST['user_id'])) {
		wp_send_json_error(['message' => 'Faltan datos obligatorios']);
	}

	$book_id = intval($_POST['book_id']);
	$user_id = intval($_POST['user_id']);
    if (!get_post($book_id) || !get_user($user_id)) {
        wp_send_json_error(['message' => __('Invalid book or user', 'tender-a-library')]);
    }
    if (!tal_book_is_on_loan($book_id)) {
        wp_send_json_error(['message' => __('This book is not currently on loan', 'tender-a-library')]);
    }
    if(tender_user_has_borrowed_book($user_id, $book_id)) {
        wp_send_json_error(['message' => __('You have have an active lending for this book', 'tender-a-library')]);
    }
	if (tal_has_active_reservation($book_id)) {
		wp_send_json_error(['message' => __('This book has an active reservation by some other user.', 'tender-a-library')]);
	}
    $reservation_id = tal_create_reservation($book_id, $user_id);

	if ($reservation_id) {
		wp_send_json_success([
			'message' => __('Reservation created successfully.', 'tender-a-library'),
			'reservation_id' => $reservation_id
		]);
	} else {
		wp_send_json_error(['message' => __('Error creating reservation', 'tender-a-library')]);
	}
}
add_action('wp_ajax_tender_create_reservation_ajax', 'tender_create_reservation_ajax'); // Nuevo nombre para evitar conflictos


// Render de reservas activas del usuario + botón Cancelar (AJAX)
function tender_user_reservations_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . esc_html__( 'You must be logged in to view your reservations.', 'tender-a-library' ) . '</p>';
    }

    $username = get_query_var('tal_profile_user');
	$current_user = $username ? $user = get_user_by('slug', $username) : wp_get_current_user();
    $user = $current_user;
    
    $rows = tal_get_user_active_reservations( $user->ID );

    // Encolar JS con AJAX y texto
    wp_enqueue_script( 'tender-reservations-js' );
    wp_localize_script( 'tender-reservations-js', 'TenderResAjax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'i18n'     => array(
            'confirm' => __( 'Do you really want to cancel this reservation?', 'tender-a-library' ),
            'error'   => __( 'Unexpected error. Please try again.', 'tender-a-library' ),
        )
    ) );

    ob_start();
    ?>
    <div class="profile-reservations">

        <h2 class=""><?php echo __('My active reservations', 'tender-a-library'); ?></h2>

        <?php if ( empty( $rows ) ) : ?>
            <p><?php esc_html_e('You do not have active reservations.', 'tender-a-library'); ?></p>
        <?php else : ?>
            <ul class="active-reservations">
                <?php foreach ($rows as $reservation) :
                    $formatted_date = $reservation->reservation_date ? wp_date(get_option('date_format', 'd/m/Y'), strtotime($reservation->reservation_date)) : 'Fecha inválida';
                    $formatted_until_date = $reservation->pickup_exclusive_until ? wp_date(get_option('date_format', 'd/m/Y'), strtotime($reservation->pickup_exclusive_until)) : 'Fecha inválida';
                    $cover_id = carbon_get_post_meta($reservation->book_id, 'tender_book_cover');


                    $nonce = wp_create_nonce( 'tal_cancel_reservation_' . $reservation->id );
                    ?>
                    <li class="reservation">
                        <div class="tender-book-preview">
                            <div class="cover">
                                <?php if ($cover_id): ?>
                                    <?php echo wp_get_attachment_image($cover_id, 'medium'); ?>
                                <?php else: ?>
                                    <img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/svg/default-book.svg"" alt=" No cover"> <?php endif; ?>
                            </div>
                            <div class="book-info">
                                <a class="title" href="<?php echo get_permalink($reservation->book_id); ?>"><?php echo get_the_title($reservation->book_id); ?></a>
                                <div class="author"><?php echo carbon_get_post_meta($reservation->book_id, 'tender_book_author'); ?></div>

                            </div>
                        </div>


                        <div class="reservation-info">
                            <div class="dates">
                                <div class="reservation-date"><?php _e('Reservation date', 'tender-a-library'); ?>: <span><?php echo $formatted_date; ?></span></div>
                                <?php if ($reservation->status === 'available') : ?>
                                    <div class="reservation-date available">
                                        <?php printf(
                                            /* translators: %s: formatted date */
                                            esc_html__('Status: Available for pickup until %s', 'tender-a-library'),
                                            '<span>' . esc_html($formatted_until_date) . '</span>'
                                        ); ?>
                                    </div>
                                <?php else : ?>
                                    <div class="reservation-date">
                                        <?php printf(
                                            /* translators: Status label */
                                            esc_html__('Status: %s', 'tender-a-library'),
                                            '<span>' . esc_html__('Reserved', 'tender-a-library') . '</span>'
                                        ); 
                                        
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="reservation-actions">
                                        <button class="tal-button tender-cancel-reservation button" data-res-id="<?php echo (int) $reservation->id; ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
                                            <?php _e('Cancel reservation', 'tender-a-library'); ?>
                                        </button>
                                </div>
                            </div>
                        </div>

                    </li>
                <?php endforeach; ?>
            </ul>
            
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tender_user_reservations', 'tender_user_reservations_shortcode' );

function tender_cancel_reservation_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( ['message' => __( 'Not authorized', 'tender-a-library' )], 401 );
    }

    $res_id = isset($_POST['res_id']) ? (int) $_POST['res_id'] : 0;
    if ( ! $res_id ) {
        wp_send_json_error( ['message' => __( 'Invalid request', 'tender-a-library' )], 400 );
    }

    // Usa check_ajax_referer con nombre standard 'nonce'
    check_ajax_referer( 'tal_cancel_reservation_' . $res_id, 'nonce' );

    global $wpdb;
    $res_table = $wpdb->prefix . 'tender_reservations'; // o 'tal_reservations' -> verifica tu nombre real
    $res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $res_table WHERE id = %d", $res_id ) );

    if ( ! $res ) {
        wp_send_json_error( ['message' => __( 'Reservation not found', 'tender-a-library' )], 404 );
    }

    $current_user = wp_get_current_user();
    $user_id      = (int) $current_user->ID;
    $roles        = (array) $current_user->roles;

    $is_staff = in_array('administrator', $roles, true)
             || in_array('librarian', $roles, true)
             || in_array('opener', $roles, true);

    if ( (int) $res->user_id !== $user_id && ! $is_staff ) {
        wp_send_json_error( ['message' => __( 'You cannot cancel this reservation', 'tender-a-library' )], 403 );
    }

    $now = current_time('mysql');
    $is_active = ( $res->status === 'pending' )
              || ( $res->status === 'available' && ( empty($res->pickup_exclusive_until) || $res->pickup_exclusive_until >= $now ) );

    if ( ! $is_active ) {
        wp_send_json_error( ['message' => __( 'Reservation is not active', 'tender-a-library' )], 409 );
    }

    $ok = $wpdb->update(
        $res_table,
        [ 'status' => 'cancelled' ],
        [ 'id' => $res_id ],
        [ '%s' ],
        [ '%d' ]
    );

    if ( false === $ok ) {
        wp_send_json_error( ['message' => __( 'Database error', 'tender-a-library' )], 500 );
    }

    wp_send_json_success([
        'message' => __( 'Reservation cancelled successfully', 'tender-a-library' ),
        'res_id'  => $res_id,
        'reload'  => true, // la UI puede recargar si quieres
    ]);
}
add_action( 'wp_ajax_tender_cancel_reservation', 'tender_cancel_reservation_ajax' );
