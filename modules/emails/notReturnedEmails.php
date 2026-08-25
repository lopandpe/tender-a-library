<?php
/**
 * Envío de avisos de préstamos no devueltos (Plugin Biblioteca)
 * Archivo: modules/emails/notReturnedEmails.php
 * Carga automática vía el array de módulos del plugin principal
 */

if (!defined('ABSPATH')) {
    exit;
}

// === CONFIGURACIÓN ===
// Cambia estas constantes si tu base cambia de nombre/tablas
if (!defined('TENDER_TABLE_LENDINGS')) {
    define('TENDER_TABLE_LENDINGS', $GLOBALS['wpdb']->prefix . 'tender_lendings');
}
if (!defined('TENDER_CPT_BOOK')) {
    define('TENDER_CPT_BOOK', 'tender_book');
}

// === CRON SETUP ===
// Registra el cron event sólo si no existe
add_action('init', function () {
    if (!wp_next_scheduled('tal_send_not_returned_emails')) {
        // Lanza cada hora; ejecutamos sólo los domingos a las 2:00 (ver función)
        wp_schedule_event(time(), 'hourly', 'tal_send_not_returned_emails');
    }
});

// Hook principal: ejecuta el proceso de avisos
add_action('tal_send_not_returned_emails', function () {
    // Sólo ejecutar domingos a las 2:00am (servidor/local)
    if (intval(date('w')) !== 0 || intval(date('G')) !== 2) {
        return;
    }
    tal_send_late_books_reminders();
});



/**
 * Consulta préstamos vencidos, agrupa por usuario y envía los avisos
 */
function tal_send_late_books_reminders() {
    global $wpdb;

    $now = current_time('mysql');

    // Traer todos los préstamos vencidos y no devueltos
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . TENDER_TABLE_LENDINGS . " WHERE returned = 0 AND stimated_return_date < %s",
        $now
    ));

    if (!$rows) return;

    // Agrupar por usuario
    $loans_by_user = [];
    foreach ($rows as $row) {
        $user_id = (int)$row->user_id;
        $book_id = (int)$row->book_id;

        // Título y autor del libro
        $book_title = get_the_title($book_id);
        if (!$book_title) $book_title = __('[Deleted book]', 'tender-library');
        $book_author = function_exists('carbon_get_post_meta')
            ? carbon_get_post_meta($book_id, 'tender_book_author')
            : '';
        if (!$book_author) $book_author = __('Unknown', 'tender-library');
        $due_date = date_i18n(get_option('date_format'), strtotime($row->stimated_return_date));

        if (!isset($loans_by_user[$user_id])) {
            $loans_by_user[$user_id] = [];
        }
        $loans_by_user[$user_id][] = [
            'title' => $book_title,
            'author' => $book_author,
            'due_date' => $due_date,
        ];
    }

    // Enviar un solo mail por usuario
    foreach ($loans_by_user as $user_id => $lendings) {
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) continue;

        $subject = __('Reminder: Books pending return', 'tender-library');
        $message = tal_get_overdue_email_html($user, $lendings);

        // Puedes personalizar el remitente aquí si lo deseas
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        tal_email_queue_enqueue([
            'type' => 'overdue_reminder',
            'recipient' => $user->user_email,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'deduplication_key' => 'overdue_' . $user_id . '_' . gmdate('o_W'),
            'priority' => 20,
        ]);

        // Opcional: log de envío (puedes implementar con una tabla o fichero)
        // tal_log_lending_notice($user_id, $lendings);
    }
}

/**
 * Devuelve el HTML del email, multiidioma y seguro
 *
 * @param WP_User $user
 * @param array $lendings
 * @return string
 */
function tal_get_overdue_email_html($user, $lendings) {
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
        <p><?php _e('We are contacting you because our system shows that you have the following books pending to return.', 'tender-library'); ?></p>
        <p><?php _e('Please come by the library to return them as soon as possible.', 'tender-library'); ?></p>

        <?php foreach ($lendings as $lending): ?>
            <ul>
                <li><strong><?php _e('Title:', 'tender-library'); ?></strong> <?php echo esc_html($lending['title']); ?></li>
                <li><?php _e('Author:', 'tender-library'); ?> <?php echo esc_html($lending['author']); ?></li>
                <li><?php _e('Estimated return date:', 'tender-library'); ?> <?php echo esc_html($lending['due_date']); ?></li>
            </ul>
        <?php endforeach; ?>

        <p style="color:#1b5e20;">
            <?php _e(
                'Remember that our library is a self-managed, non-profit space that relies on the individual and collective responsibility of all its members. Returning books on time ensures that more people can enjoy them, and helps keep the project alive for everyone.',
                'tender-library'
            ); ?>
        </p>

        <p><?php _e('If you have lost the books, or if you think there has been a mistake, please contact us.', 'tender-library'); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
