<?php
if (!defined('ABSPATH')) {
	exit;
}

// 1. TEMAS CLÁSICOS (No FSE): Usar plantilla PHP
function tender_book_classic_template($template)
{
	if (wp_is_block_theme()) {
		return $template;
	}

	if (is_singular('tender_book')) {
		$plugin_template = plugin_dir_path(__DIR__) . '../templates/single-tender-book.php';
		if (file_exists($plugin_template)) {
			return $plugin_template;
		}
	}

	return $template;
}
add_filter('template_include', 'tender_book_classic_template');


// 2. TEMAS FSE (desde WP 6.7): Registrar plantilla desde plugin
add_action( 'init', 'tender_book_register_fse_template' );
function tender_book_register_fse_template() {
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
            'id'          => 'single-tender_book',
            'title'       => __( 'Single book', 'tender-library' ),
            'description' => __( 'Single book custom template (CPT).', 'tender-library' ),
            'args'        => [
                'slug'       => 'single-tender_book',
                'post_types' => [ 'tender_book' ],
            ],
        ],
        [
            'id'          => 'archive-tender_book',
            'title'       => __( 'Tender books archive', 'tender-library' ),
            'description' => __( 'Tender books archive template (CPT).', 'tender-library' ),
            'args'        => [
                'slug'       => 'archive-tender_book',
                'post_types' => [ 'tender_book' ],
            ],
        ],
        [
            'id'          => 'taxonomy-tender_section',
            'title'       => __( 'Taxonomy: Tender Section', 'tender-library' ),
            'description' => __( 'Custom template for Tender Section taxonomy.', 'tender-library' ),
            'args'        => [
                'slug'       => 'taxonomy-tender_section',
                'taxonomies' => [ 'tender_section' ],
            ],
        ],
        [
            'id'          => 'taxonomy-tender_language',
            'title'       => __( 'Taxonomy: Tender Language', 'tender-library' ),
            'description' => __( 'Custom template for Tender Language taxonomy.', 'tender-library' ),
            'args'        => [
                'slug'       => 'taxonomy-tender_language',
                'taxonomies' => [ 'tender_language' ],
            ],
        ],
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

