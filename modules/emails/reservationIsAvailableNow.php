<?php


function tal_notify_availability_of_reservation($reservation, $book_id){
    $book = get_post($book_id);
    $user = get_user($reservation->user_id);
   
    $subject = __('The book you want is already available for ten days', 'tender-library');

    $message = tal_get_your_reservation_is_available_email_html($user, $book);

    // Puedes personalizar el remitente aquí si lo deseas
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    tal_email_queue_enqueue([
        'type' => 'reservation_available',
        'recipient' => $user->user_email,
        'subject' => $subject,
        'message' => $message,
        'headers' => $headers,
        'deduplication_key' => 'available_' . $reservation->id,
        'priority' => 100,
    ]);

}


function tal_get_your_reservation_is_available_email_html($user, $book) {
    // Obtener el logo actual del WordPress (site icon)
    $logo_url = get_site_icon_url(150);
    $site_name = get_bloginfo('name');

    ob_start(); ?>
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:18px;">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-width: 150px;">
            <?php else: ?>
                <span style="font-size:1.8em;font-weight:bold;"><?php echo esc_html($site_name); ?></span>
            <?php endif; ?>
        </div>
        <h4><?php printf(__('Hello <strong>%s</strong>,', 'tender-library'), esc_html($user->display_name)); ?></h4>
        <p><?php _e('We are contacting you because the book you reserved is available now.', 'tender-library'); ?></p>
        <p><?php _e('If you do not borrow it on ten days, the book will be available for everyone.', 'tender-library'); ?></p>

            <ul>
                <li><strong><?php _e('Title:', 'tender-library'); ?></strong> <?php echo esc_html($book->post_title); ?></li>
                <li><?php _e('Author:', 'tender-library'); ?> <?php echo esc_html(carbon_get_post_meta($book->ID, 'tender_book_author')); ?></li>
            </ul>

        <p style="color:#1b5e20;">
            <?php _e(
                'Thank you.',
                'tender-library'
            ); ?>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
