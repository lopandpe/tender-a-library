<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


use Carbon_Fields\Field;
use Carbon_Fields\Container;

Container::make('post_meta', 'Datos del libro')
	->where('post_type', '=', 'tender_book')
	->add_fields(
		array(
			Field::make('image', 'tender_book_cover', __('Cover', 'tender-library')),

			Field::make('text', 'tender_book_author', __('Authors', 'tender-library'))
				->set_required(true)
				->set_attribute('placeholder', __('Surname, First name', 'tender-library'))
				->set_help_text(__('This value determines the call sign. It must begin with at least three letters.', 'tender-library')),

			Field::make('textarea', 'tender_book_other_authors', __('Otras autoras', 'tender-library')),

			Field::make('text', 'tender_book_publisher', __('Publisher', 'tender-library')),

			Field::make('text', 'tender_book_units', __('Copies', 'tender-library'))
				->set_attribute('type', 'number')
				->set_attribute('min', 0)
				->set_required(true)
				->set_default_value(1),


			Field::make(
				'association',
				'tender_book_language',
				__('Language', 'tender-library')
			)
				->set_types(array(
					array(
						'type' => 'term',
						'taxonomy' => 'tender_language',
					)
				))
				->set_max(1)
				->set_min(1)
				->set_required(true),

			Field::make('text', 'tender_book_year', __('Year of publication', 'tender-library')),

			Field::make('text', 'tender_book_edition', __('Edition number', 'tender-library')),

			Field::make('text', 'tender_book_isbn', __('ISBN')),


			Field::make('rich_text', 'tender_book_excerpt', __('Summary', 'tender-library')),

			Field::make('rich_text', 'tender_book_review', __('Review', 'tender-library')),
		)
	);


Container::make('post_meta', 'Categoría')
	->where('post_type', '=', 'tender_book')
	->add_fields(
		array(
			Field::make(
				'association',
				'tender_book_section',
				__('Library section', 'tender-library')
			)
				->set_types(array(
					array(
						'type' => 'term',
						'taxonomy' => 'tender_section',
					)
				))
				->set_max(1)
				->set_min(1)
				->set_required(true),
		)
	);

// Signatura fields are always available and required.
Container::make('post_meta', 'Signatura')
	->where('post_type', '=', 'tender_book')
	->add_fields(array(

		Field::make('text', 'tender_book_sig1', __('Author', 'tender-library'))
			->set_required(true)
			->set_attribute('placeholder', 'XXX')
			->set_help_text(__('First three letters of the author\'s first surname.', 'tender-library')),
		Field::make('text', 'tender_book_sig2', __('Title', 'tender-library'))
			->set_required(true)
			->set_attribute('placeholder', 'yyy')
			->set_help_text(__('Three first letters of the first word of more than three characters in the title, avoiding articles.', 'tender-library')),
	));
