<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


use Carbon_Fields\Field;
use Carbon_Fields\Container;

Container::make('term_meta', __('Section data', 'tender-library'))
	->where('term_taxonomy', '=', 'tender_section')
	->add_fields(array(
		Field::make('text', 'tender_section_number', __('Section number', 'tender-library'))
			->set_required(true)
			->set_attribute('placeholder', 'X.X.X')
			->set_help_text(__('The format is a sequence of parent section numbers, in order, separated by dots, including the current section number at the end. Ex: ‘2.5.1’.', 'tender-library')),
	));


// Añadir una columna nueva al listado de la taxonomía "tender_section".
add_filter('manage_edit-tender_section_columns', 'edit_tender_section_columns');
add_filter('manage_tender_section_custom_column', 'tender_section_number_show_value', 10, 3);
add_filter('manage_edit-tender_section_sortable_columns', 'make_tender_section_sortable');
add_action('pre_get_terms', 'order_by_tender_section_number');

function edit_tender_section_columns($columns)
{
	$columns['tender_section_number'] = __('Number', 'tender-library');
	unset($columns['description']);
	return $columns;
}


function tender_section_number_show_value($content, $column_name, $term_id)
{
	if ($column_name === 'tender_section_number') {
		$meta_valor = carbon_get_term_meta($term_id, 'tender_section_number');

		if (!empty($meta_valor)) {
			$content = esc_html($meta_valor);
		} else {
			$content = '<em>-</em>';
		}
	}

	return $content;
}

function make_tender_section_sortable($sortable_columns)
{
	$sortable_columns['tender_section_number'] = 'tender_section_number';
	return $sortable_columns;
}

function order_by_tender_section_number($query)
{
	if (!is_admin() || !isset($query->query_vars['taxonomy']) || $query->query_vars['taxonomy'] !== 'tender_section') {
		return;
	}

	if (isset($_GET['orderby']) && $_GET['orderby'] === 'tender_section_number') {
		$query->query_vars['meta_key'] = 'tender_section_number'; // Clave meta para ordenar.
		$query->query_vars['orderby'] = 'meta_value_num'; // Ordenar como valor numérico.
	}
}
