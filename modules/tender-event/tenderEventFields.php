<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


use Carbon_Fields\Field;
use Carbon_Fields\Container;

// add_action( 'admin_enqueue_scripts', 'crb_flatpickr_locale_es' );
// function crb_flatpickr_locale_es() {
//     wp_enqueue_script(
//         'flatpickr-locale-es',
//         'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js',
//         array( 'carbon-fields-core' ), // en CF 3+ el handle es este
//         null,
//         true
//     );
// }

Container::make('post_meta', __('Event', 'tender-library'))
	->where('post_type', '=', 'tender_event')
    ->set_context('side')
	->add_fields(
		array(
            // Date event field
            Field::make('date_time', 'tender_event_startdate', __('Date of the event', 'tender-library'))
                ->set_required(true)
                // Set date picker options as spanish format
                ->set_picker_options( array(
                    'todayButton' => true,
                    'enableTime' => true,
                    'time_24hr' => true,
                    'locale' => 'es',
                ) )
                ->set_storage_format( 'Y-m-d H:i:s' )
                ->set_input_format( 'd/m/Y H:i', 'd/m/Y H:i' )
                ->set_attribute( 'placeholder', __('Start datetime of the event', 'tender-library') ),

            Field::make( 'checkbox', 'tender_event_recurrent', __( 'It repeats every week', 'tender-library' ) )
                ->set_option_value( 'yes' ) // valor cuando está marcado
                ->set_help_text( __( 'Check this box if the event is recurring. Remember to add end date to the event.', 'tender-library' ) ),


            // Date event field
            Field::make('date', 'tender_event_enddate', __('End date of the event', 'tender-library'))
                ->set_required(true)
                // Set date picker options as spanish format
                ->set_picker_options( array(
                    'todayButton' => true,
                    'locale' => 'es',
                ) )
                ->set_storage_format( 'Y-m-d' )
                ->set_input_format( 'd/m/Y 23:59:59', 'd/m/Y 23:59:59' )
                ->set_attribute( 'placeholder', __('End date of the repeating event', 'tender-library') )
                ->set_conditional_logic( array(
                    'relation' => 'AND',
                    array(
                        'field' => 'tender_event_recurrent',
                        'value' => true,
                        'compare' => '=',
                    )
                ) ),
		)
	);


Container::make('post_meta', 'Categoría')
	->where('post_type', '=', 'tender_event')
	->add_fields(
		array(
			Field::make(
				'association',
				'tender_book_section',
				__('Library section', 'tender-library') . __(' (optional)', 'tender-library')
			)
				->set_types(array(
					array(
						'type' => 'term',
						'taxonomy' => 'tender_section',
					)
				))
				->set_max(1)
				->set_min(1)
				->set_required(false),
		)
	);
