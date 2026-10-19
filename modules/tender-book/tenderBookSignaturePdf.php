<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

define('TAL_SIGNATURE_PDF_SETTINGS_OPTION', 'tal_signature_pdf_settings');

/**
 * Returns the settings used to lay out signature labels on an A4 page.
 */
function tal_signature_pdf_get_settings()
{
	$defaults = [
		'font_family' => 'sans-serif',
		'font_size' => 16,
		'line_height' => 1.2,
		'margin_cm' => 1.5,
		'label_width_cm' => 4,
		'label_height_cm' => 3,
		'gap_cm' => 0.2,
		'columns' => 4,
		'rows' => 8,
		'show_guides' => false,
	];

	$saved = get_option(TAL_SIGNATURE_PDF_SETTINGS_OPTION, []);
	return wp_parse_args(is_array($saved) ? $saved : [], $defaults);
}

function tal_signature_pdf_sanitize_settings($settings)
{
	$font_family = isset($settings['font_family']) ? sanitize_key($settings['font_family']) : 'sans-serif';
	if (!in_array($font_family, ['sans-serif', 'serif', 'monospace'], true)) {
		$font_family = 'sans-serif';
	}

	return [
		'font_family' => $font_family,
		'font_size' => min(72, max(6, (float) ($settings['font_size'] ?? 16))),
		'line_height' => min(3, max(0.8, (float) ($settings['line_height'] ?? 1.2))),
		'margin_cm' => min(5, max(0, (float) ($settings['margin_cm'] ?? 1.5))),
		'label_width_cm' => min(15, max(4, (float) ($settings['label_width_cm'] ?? 4))),
		'label_height_cm' => min(15, max(3, (float) ($settings['label_height_cm'] ?? 3))),
		'gap_cm' => min(5, max(0, (float) ($settings['gap_cm'] ?? 0.2))),
		'columns' => min(10, max(1, absint($settings['columns'] ?? 4))),
		'rows' => min(14, max(1, absint($settings['rows'] ?? 8))),
		'show_guides' => !empty($settings['show_guides']),
	];
}

function tal_signature_pdf_handle_settings_save()
{
	if (!tal_settings_user_can_access()) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	check_admin_referer('tal_signature_pdf_settings');
	$settings = isset($_POST['tal_signature_pdf']) && is_array($_POST['tal_signature_pdf'])
		? wp_unslash($_POST['tal_signature_pdf'])
		: [];
	update_option(TAL_SIGNATURE_PDF_SETTINGS_OPTION, tal_signature_pdf_sanitize_settings($settings), false);

	wp_safe_redirect(add_query_arg('updated', '1', tal_settings_page_url('signature-labels')));
	exit;
}
add_action('admin_post_tal_signature_pdf_settings', 'tal_signature_pdf_handle_settings_save');

function tal_signature_pdf_render_settings()
{
	if (!tal_settings_user_can_access()) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}

	$settings = tal_signature_pdf_get_settings();
	if (isset($_GET['updated'])) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Signature label settings saved.', 'tender-library') . '</p></div>';
	}

	echo '<p>' . esc_html__('Choose how signature labels are laid out on A4 pages. Labels contain the section number, author signature, and title signature.', 'tender-library') . '</p>';
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
	wp_nonce_field('tal_signature_pdf_settings');
	echo '<input type="hidden" name="action" value="tal_signature_pdf_settings" />';
	echo '<table class="form-table" role="presentation">';

	$fields = [
		'font_family' => [__('Font family', 'tender-library'), 'select'],
		'font_size' => [__('Font size (px)', 'tender-library'), 'number', 6, 72, 1],
		'line_height' => [__('Line height', 'tender-library'), 'number', 0.8, 3, 0.1],
		'margin_cm' => [__('Page margin (cm)', 'tender-library'), 'number', 0, 5, 0.1],
		'label_width_cm' => [__('Label width (cm)', 'tender-library'), 'number', 4, 15, 0.1],
		'label_height_cm' => [__('Label height (cm)', 'tender-library'), 'number', 3, 15, 0.1],
		'gap_cm' => [__('Separation between labels (cm)', 'tender-library'), 'number', 0, 5, 0.1],
		'columns' => [__('Columns', 'tender-library'), 'number', 1, 10, 1],
		'rows' => [__('Rows per page', 'tender-library'), 'number', 1, 14, 1],
	];

	foreach ($fields as $key => $field) {
		echo '<tr><th scope="row"><label for="tal-signature-pdf-' . esc_attr($key) . '">' . esc_html($field[0]) . '</label></th><td>';
		if ($field[1] === 'select') {
			echo '<select id="tal-signature-pdf-font_family" name="tal_signature_pdf[font_family]">';
			$options = [
				'sans-serif' => __('Sans serif (Helvetica)', 'tender-library'),
				'serif' => __('Serif (Times)', 'tender-library'),
				'monospace' => __('Monospace (Courier)', 'tender-library'),
			];
			foreach ($options as $value => $label) {
				echo '<option value="' . esc_attr($value) . '"' . selected($settings[$key], $value, false) . '>' . esc_html($label) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input id="tal-signature-pdf-' . esc_attr($key) . '" name="tal_signature_pdf[' . esc_attr($key) . ']" type="number" min="' . esc_attr((string) $field[2]) . '" max="' . esc_attr((string) $field[3]) . '" step="' . esc_attr((string) $field[4]) . '" value="' . esc_attr((string) $settings[$key]) . '" />';
		}
		echo '</td></tr>';
	}

	echo '<tr><th scope="row">' . esc_html__('Cutting guides', 'tender-library') . '</th><td><label><input name="tal_signature_pdf[show_guides]" type="checkbox" value="1"' . checked($settings['show_guides'], true, false) . ' /> ' . esc_html__('Draw a border around each label.', 'tender-library') . '</label></td></tr>';
	echo '</table>';
	echo '<p class="description">' . esc_html__('Defaults: bold 16px sans-serif text, 1.5 cm page margins, 4 × 3 cm labels, 0.2 cm separation, and a 4 × 8 grid.', 'tender-library') . '</p>';
	submit_button(__('Save Changes', 'tender-library'));
	echo '</form>';
}

function tal_signature_pdf_add_bulk_action($actions)
{
	$actions['tal_print_signatures'] = __('Print signatures (PDF)', 'tender-library');
	return $actions;
}
add_filter('bulk_actions-edit-tender_book', 'tal_signature_pdf_add_bulk_action');

function tal_signature_pdf_open_bulk_pdf_in_new_tab()
{
	$screen = get_current_screen();
	if (!$screen || $screen->id !== 'edit-tender_book') {
		return;
	}
	?>
	<script>
	(function () {
		var form = document.getElementById('posts-filter');
		if (!form) {
			return;
		}

		form.addEventListener('submit', function () {
			var actions = [
				document.getElementById('bulk-action-selector-top'),
				document.getElementById('bulk-action-selector-bottom')
			];
			var isPrintingSignatures = actions.some(function (action) {
				return action && action.value === 'tal_print_signatures';
			});
			form.target = isPrintingSignatures ? '_blank' : '';
		});
	}());
	</script>
	<?php
}
add_action('admin_footer-edit.php', 'tal_signature_pdf_open_bulk_pdf_in_new_tab');

function tal_signature_pdf_maybe_generate_bulk_pdf()
{
	global $typenow;
	if ($typenow !== 'tender_book') {
		return;
	}

	$action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '-1';
	if ($action === '-1' && isset($_REQUEST['action2'])) {
		$action = sanitize_key(wp_unslash($_REQUEST['action2']));
	}
	if ($action !== 'tal_print_signatures') {
		return;
	}

	if (!current_user_can('edit_tender_books')) {
		wp_die(__('Insufficient permissions.', 'tender-library'));
	}
	check_admin_referer('bulk-posts');

	$post_ids = isset($_REQUEST['post']) ? wp_parse_id_list(wp_unslash($_REQUEST['post'])) : [];
	$post_ids = array_values(array_filter($post_ids, static function ($post_id) {
		return get_post_type($post_id) === 'tender_book' && current_user_can('edit_post', $post_id);
	}));

	if (!$post_ids) {
		wp_die(__('Select at least one book to print its signature.', 'tender-library'));
	}

	tal_signature_pdf_send($post_ids);
}
add_action('load-edit.php', 'tal_signature_pdf_maybe_generate_bulk_pdf');

function tal_signature_pdf_get_book_lines($post_id)
{
	$section_number = '';
	$terms = get_the_terms($post_id, 'tender_section');
	if ($terms && !is_wp_error($terms)) {
		$term = reset($terms);
		$section_number = function_exists('carbon_get_term_meta')
			? carbon_get_term_meta($term->term_id, 'tender_section_number')
			: get_term_meta($term->term_id, 'tender_section_number', true);
	}

	$get_meta = static function ($key) use ($post_id) {
		return function_exists('carbon_get_post_meta')
			? carbon_get_post_meta($post_id, $key)
			: get_post_meta($post_id, $key, true);
	};
	$author_signature = (string) $get_meta('tender_book_sig1');
	$title_signature = (string) $get_meta('tender_book_sig2');
	$author_signature = function_exists('mb_strtoupper') ? mb_strtoupper($author_signature, 'UTF-8') : strtoupper($author_signature);
	$title_signature = function_exists('mb_strtolower') ? mb_strtolower($title_signature, 'UTF-8') : strtolower($title_signature);

	return [
		(string) $section_number,
		$author_signature,
		$title_signature,
	];
}

function tal_signature_pdf_escape_text($text)
{
	$text = wp_strip_all_tags((string) $text);
	if (function_exists('iconv')) {
		$converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
		if ($converted !== false) {
			$text = $converted;
		}
	}
	return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $text);
}

function tal_signature_pdf_font_name($font_family)
{
	$fonts = [
		'serif' => 'Times-Bold',
		'monospace' => 'Courier-Bold',
		'sans-serif' => 'Helvetica-Bold',
	];
	return $fonts[$font_family] ?? $fonts['sans-serif'];
}

function tal_signature_pdf_text_width($text, $font_size, $font_family)
{
	if ($font_family === 'monospace') {
		return strlen((string) $text) * $font_size * 0.6;
	}

	$widths = $font_family === 'serif'
		? [
			'0' => 500, '1' => 500, '2' => 500, '3' => 500, '4' => 500, '5' => 500, '6' => 500, '7' => 500, '8' => 500, '9' => 500,
			'A' => 722, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 778, 'I' => 389, 'J' => 500, 'K' => 778, 'L' => 667, 'M' => 944, 'N' => 722, 'O' => 778, 'P' => 611, 'Q' => 778, 'R' => 722, 'S' => 556, 'T' => 667, 'U' => 722, 'V' => 722, 'W' => 1000, 'X' => 722, 'Y' => 722, 'Z' => 667,
			'a' => 500, 'b' => 556, 'c' => 444, 'd' => 556, 'e' => 444, 'f' => 333, 'g' => 500, 'h' => 556, 'i' => 278, 'j' => 333, 'k' => 556, 'l' => 278, 'm' => 833, 'n' => 556, 'o' => 500, 'p' => 556, 'q' => 556, 'r' => 444, 's' => 389, 't' => 333, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 444,
		]
		: [
			'0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
			'A' => 722, 'B' => 722, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278, 'J' => 556, 'K' => 722, 'L' => 611, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
			'a' => 556, 'b' => 611, 'c' => 556, 'd' => 611, 'e' => 556, 'f' => 333, 'g' => 611, 'h' => 611, 'i' => 278, 'j' => 278, 'k' => 556, 'l' => 278, 'm' => 889, 'n' => 611, 'o' => 611, 'p' => 611, 'q' => 611, 'r' => 444, 's' => 556, 't' => 333, 'u' => 611, 'v' => 556, 'w' => 722, 'x' => 556, 'y' => 556, 'z' => 500,
		];
	$punctuation_widths = $font_family === 'serif'
		? [' ' => 250, '.' => 250, ',' => 250, ':' => 333, ';' => 333, '-' => 333, '_' => 500, '/' => 278, '\\' => 278, '(' => 333, ')' => 333, '[' => 333, ']' => 333]
		: [' ' => 278, '.' => 278, ',' => 278, ':' => 333, ';' => 333, '-' => 333, '_' => 556, '/' => 278, '\\' => 278, '(' => 333, ')' => 333, '[' => 333, ']' => 333];
	$widths = array_merge($widths, $punctuation_widths);

	$width = 0;
	foreach (str_split((string) $text) as $character) {
		$width += $widths[$character] ?? 600;
	}

	return ($width / 1000) * $font_size;
}

function tal_signature_pdf_build_document($post_ids, $settings)
{
	$page_width = 595.28; // A4 portrait, in points.
	$page_height = 841.89;
	$points_per_cm = 28.34646;
	$margin = $settings['margin_cm'] * $points_per_cm;
	$label_width = $settings['label_width_cm'] * $points_per_cm;
	$label_height = $settings['label_height_cm'] * $points_per_cm;
	$gap = $settings['gap_cm'] * $points_per_cm;
	$columns = (int) $settings['columns'];
	$rows = (int) $settings['rows'];
	$required_width = ($columns * $label_width) + (($columns - 1) * $gap);
	$required_height = ($rows * $label_height) + (($rows - 1) * $gap);

	if ($required_width > ($page_width - (2 * $margin)) || $required_height > ($page_height - (2 * $margin))) {
		wp_die(
			esc_html__('The selected rows, columns, label size, separation, and margins do not fit on an A4 page. Adjust the Signature Labels settings and try again.', 'tender-library'),
			esc_html__('Signature labels do not fit', 'tender-library'),
			['back_link' => true]
		);
	}

	$labels_per_page = $columns * $rows;
	$font_size = $settings['font_size'] * 0.75; // CSS pixels to PDF points.
	$line_spacing = $font_size * $settings['line_height'];
	$font_name = tal_signature_pdf_font_name($settings['font_family']);
	$streams = [];

	foreach (array_chunk($post_ids, $labels_per_page) as $page_ids) {
		$commands = [];
		foreach ($page_ids as $index => $post_id) {
			$row = intdiv($index, $columns);
			$column = $index % $columns;
			$x = $margin + ($column * ($label_width + $gap));
			$y = $page_height - $margin - (($row + 1) * $label_height) - ($row * $gap);

			if ($settings['show_guides']) {
				$commands[] = sprintf('0.35 w %.2F %.2F %.2F %.2F re S', $x, $y, $label_width, $label_height);
			}

			$lines = tal_signature_pdf_get_book_lines($post_id);
			$first_baseline = $y + ($label_height / 2) + $line_spacing - ($font_size * 0.35);
			foreach ($lines as $line_index => $line) {
				$text = tal_signature_pdf_escape_text($line);
				$text_width = tal_signature_pdf_text_width($text, $font_size, $settings['font_family']);
				// Treat each line as a full-width, centered block within the label.
				// Do not clamp long section numbers to the left edge.
				$text_x = $x + (($label_width - $text_width) / 2);
				$text_y = $first_baseline - ($line_index * $line_spacing);
				$commands[] = sprintf('BT /F1 %.2F Tf %.2F %.2F Td (%s) Tj ET', $font_size, $text_x, $text_y, $text);
			}
		}
		$streams[] = implode("\n", $commands) . "\n";
	}

	$objects = [];
	$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
	$page_object_numbers = [];
	$next_object = 5;
	foreach ($streams as $stream) {
		$page_object_numbers[] = $next_object;
		$objects[$next_object] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $page_width . ' ' . $page_height . '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . ($next_object + 1) . ' 0 R >>';
		$objects[$next_object + 1] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
		$next_object += 2;
	}
	$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static function ($number) { return $number . ' 0 R'; }, $page_object_numbers)) . '] /Count ' . count($page_object_numbers) . ' >>';
	$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /' . $font_name . ' >>';
	$objects[4] = '<< /Producer (Tender Library) /Title (Book signatures) >>';
	ksort($objects);

	$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
	$offsets = [0];
	foreach ($objects as $number => $object) {
		$offsets[$number] = strlen($pdf);
		$pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
	}
	$xref_offset = strlen($pdf);
	$pdf .= 'xref' . "\n0 " . (max(array_keys($objects)) + 1) . "\n0000000000 65535 f \n";
	for ($number = 1; $number <= max(array_keys($objects)); $number++) {
		$pdf .= sprintf('%010d 00000 n ', $offsets[$number] ?? 0) . "\n";
	}
	$pdf .= 'trailer' . "\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R /Info 4 0 R >>\nstartxref\n" . $xref_offset . "\n%%EOF";

	return $pdf;
}

function tal_signature_pdf_send($post_ids)
{
	$pdf = tal_signature_pdf_build_document($post_ids, tal_signature_pdf_get_settings());
	nocache_headers();
	header('Content-Type: application/pdf');
	header('Content-Disposition: inline; filename="book-signatures.pdf"');
	header('Content-Length: ' . strlen($pdf));
	echo $pdf;
	exit;
}
