<?php
// Archivo: tender-admin-pages.php

if (!defined('ABSPATH')) {
	exit;
}

add_action('tal_admin_pre_active_lendings', 'tal_render_export_lendings_page');
function tal_render_export_lendings_page() {
    ?>
    <div class="wrap">
        <form method="post" class="download_csv_form" action="">
            <label for="tal_export_lendings_csv"><?php _e('Export active lendings to CSV', 'tender-library'); ?></label>
            <?php wp_nonce_field('tal_export_lendings_action', 'tal_export_lendings_nonce'); ?>
            <button type="submit" class="button button-primary" name="tal_export_lendings_csv">
                <?php _e('CSV Download', 'tender-library'); ?>
            </button>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    if (
        isset($_POST['tal_export_lendings_csv']) &&
        current_user_can('manage_options') &&
        check_admin_referer('tal_export_lendings_action', 'tal_export_lendings_nonce')
    ) {
        tal_export_lendings_to_csv();
        exit;
    }
});

function tal_export_lendings_to_csv() {
    global $wpdb;

    // 1. Consulta de préstamos
    $lendings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tender_lendings WHERE returned = 0");

    // 2. Cabeceras para descargar como CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=lendings_export_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');

    // 3. Cabecera del CSV
    fputcsv($output, [
        'ID',
        __('User', 'tender-library'),
        __('E-mail', 'tender-library'),
        __('Phone number', 'tender-library'),
        __('Book Title', 'tender-library'),
        __('Author(s)', 'tender-library'),
        __('Signature', 'tender-library'),
        __('Loan date', 'tender-library'),
        __('Estimated return date', 'tender-library')
    ]);

    foreach ($lendings as $lending) {
        // USER
        $user = get_userdata($lending->user_id);

		$phone_number = carbon_get_user_meta($lending->user_id, 'phone_number');

        // LIBRO (Post Type)
        $book_post = get_post($lending->book_id);
        $book_title = $book_post ? $book_post->post_title : '';
        // Campos extra de Carbon Fields
        $book_author = carbon_get_post_meta($lending->book_id, 'tender_book_author');

        fputcsv($output, [
            $lending->id,
            $user->display_name,
            $user->user_email,
            $phone_number,
            $book_title,
            $book_author,
            get_tender_signature($lending->book_id),
            $lending->lending_date,
            $lending->stimated_return_date,
        ]);
    }
    fclose($output);
    exit;
}
