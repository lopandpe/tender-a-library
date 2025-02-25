<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


use Carbon_Fields\Field;
use Carbon_Fields\Container;

Container::make('post_meta', 'Datos del libro')
	->where('post_type', '=', 'tender_book')
	->add_fields(array(
		Field::make('image', 'tender_book_cover', __('Portada')),

		Field::make('text', 'tender_book_author', __('Autoría'))
			->set_required(true)
			->set_attribute('placeholder', 'Apellido, Nombre')
			->set_help_text('Este valor determina la signatura. Es necesario que empiece mínimo por tres letras.'),

		Field::make('textarea', 'tender_book_other_authors', __('Otras autoras')),

		Field::make('text', 'tender_book_publisher', __('Editorial'))
			->set_required(true),

		Field::make('text', 'tender_book_units', __('Ejemplares'))
			->set_attribute('type', 'number')
			->set_attribute('min', 0)
			->set_required(true)
			->set_default_value(1),


		Field::make('association', 'tender_book_language', __('Idioma'))
			->set_types(array(
				array(
					'type' => 'term',
					'taxonomy' => 'tender_language',
				)
			))
			->set_max(1)
			->set_min(1)
			->set_required(true),

		Field::make('text', 'tender_book_year', __('Año de publicación')),

		Field::make('text', 'tender_book_edition', __('Número de edición')),

		Field::make('text', 'tender_book_isbn', __('ISBN')),


		Field::make('rich_text', 'tender_book_excerpt', __('Resumen')),

		Field::make('rich_text', 'tender_book_review', __('Reseña')),
	));


Container::make('post_meta', 'Categoría')
	->where('post_type', '=', 'tender_book')
	->add_fields(array(
		Field::make('association', 'tender_book_section', __('Sección de la biblioteca'))
			->set_types(array(
				array(
					'type' => 'term',
					'taxonomy' => 'tender_section',
				)
			))
			->set_max(1)
			->set_min(1)
			->set_required(true),
	));

// Solo registrar campos cuando el post está publicado
Container::make('post_meta', 'Signatura')
	->where('post_type', '=', 'tender_book')
	->where('post_status', '=', 'publish')
	->add_fields(array(

		Field::make('text', 'tender_book_sig1', __('Autor(a)'))
			->set_required(true)
			->set_attribute('placeholder', 'XXX')
			->set_help_text('Tres primeras letras del primer apellido del autor(a).'),
		Field::make('text', 'tender_book_sig2', __('Título'))
			->set_required(true)
			->set_attribute('placeholder', 'yyy')
			->set_help_text('Tres primeras letras de la primera palabra de más de tres caracteres del título, evitando artículos.'),
	));
