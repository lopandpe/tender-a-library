<?php

if (!defined('ABSPATH')) {
	exit;
}

function tal_notify_current_borrower_about_hold($book_id){

    $borrowers_id = tender_get_current_lending_users_for_book($book_id);
    if(is_array($borrowers_id)){
        foreach($borrowers_id as $user_id){
            $borrower = get_user($user_id);
            $book = get_post($book_id);
            
            $subject = __('Someone else wants to read the book you have borrowed', 'tender-a-library');

            $message = tal_get_your_loan_has_reservation_email_html($borrower, $book);

            // Puedes personalizar el remitente aquí si lo deseas
            $headers = ['Content-Type: text/html; charset=UTF-8'];

            wp_mail($borrower->user_email, $subject, $message, $headers);
        }
    }

}


function tal_get_your_loan_has_reservation_email_html($user, $book) {
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
        <h4><?php printf(__('Hello <strong>%s</strong>,', 'tender-a-library'), esc_html($user->display_name)); ?></h4>
        <p><?php _e('We are contacting you because someone has made a reservation on the book you have borrowed.', 'tender-a-library'); ?></p>
        <p><?php _e('From now, the book loan can not be renewed, and we ask you to please return it as soon as you finish reading it.', 'tender-a-library'); ?></p>

            <ul>
                <li><strong><?php _e('Title:', 'tender-a-library'); ?></strong> <?php echo esc_html($book->get_the_title()); ?></li>
                <li><?php _e('Author:', 'tender-a-library'); ?> <?php echo esc_html(carbon_get_post_meta($book->ID, 'tender_book_author')); ?></li>
            </ul>

        <p style="color:#1b5e20;">
            <?php _e(
                'Remember that our library is a self-managed, non-profit space that relies on the individual and collective responsibility of all its members. Returning books on time ensures that more people can enjoy them, and helps keep the project alive for everyone.',
                'tender-a-library'
            ); ?>
        </p>

        <p><?php _e('If you have lost the books, or if you think there has been a mistake, please contact us.', 'tender-a-library'); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
