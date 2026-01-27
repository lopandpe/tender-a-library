<?php
if (!defined('ABSPATH')) {
	exit;
}

// 1. TEMAS CLÁSICOS (No FSE): Usar plantilla PHP
function tender_event_classic_template($template)
{
	if (wp_is_block_theme()) {
		return $template;
	}

	if (is_singular('tender_event')) {
		$plugin_template = plugin_dir_path(__DIR__) . '../templates/single-tender-event.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}

	return $template;
}
add_filter('template_include', 'tender_event_classic_template');


// 2. TEMAS FSE (desde WP 6.7): Registrar plantilla desde plugin
add_action( 'init', 'tender_event_register_fse_template' );
function tender_event_register_fse_template() {
    // Sólo en temas full‐site editing
    if ( ! wp_is_block_theme() ) {
        return;
    }

    $plugin_template_path = plugin_dir_path( __DIR__ ) . '../block-templates/';
    if ( ! file_exists( $plugin_template_path ) ) {
        return;
    }

    // Lista de todas las plantillas a registrar
    $templates = [
        [
            'id'          => 'single-tender_event',
            'title'       => __( 'Single event', 'tender-a-library' ),
            'description' => __( 'Single event custom template (CPT).', 'tender-a-library' ),
            'args'        => [
                'slug'       => 'single-tender_event',
                'post_types' => [ 'tender_event' ],
            ],
        ]
    ];

    foreach ( $templates as $tpl ) {
        $file = $plugin_template_path . $tpl['id'] . '.html';
        if ( ! file_exists( $file ) ) {
            continue;
        }

        $content = file_get_contents( $file );

        // Construimos el array de registro uniendo el contenido y los args
        $register_args = array_merge(
            [
                'title'       => $tpl['title'],
                'description' => $tpl['description'],
                'content'     => $content,
            ],
            $tpl['args']
        );

        register_block_template(
            // namespace de tu plugin + ID
            'tender-a-library//' . $tpl['id'],
            $register_args
        );
    }
}

