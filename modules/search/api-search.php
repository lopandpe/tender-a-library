<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('tender-library/v1', '/search', array(
        'methods'  => 'GET',
        'callback' => 'tender_api_search',
        'args'     => require __DIR__ . '/search-args.php',
        'permission_callback' => '__return_true', // Permitir acceso público
    ));
});

function tender_api_search($request) {
    require_once __DIR__ . '/api-utils.php';

    
    $args = tender_api_build_query_args($request);

    

    $query = new WP_Query($args);

    $results = array_map( function ($post) {

        $image_id = carbon_get_post_meta($post->ID, 'tender_book_cover');
        $image = null;
        if($image_id){
            $image = wp_get_attachment_image_url($image_id, 'medium');
        }

        return array(
            'id'        => $post->ID,
            'title'     => get_the_title($post),
            'link'      => get_permalink($post),
            'author'    => carbon_get_post_meta($post->ID, 'tender_book_author'),
            'thumbnail' => $image,
            'link'      => get_permalink($post),
            'available' => tender_can_book_be_lent($post->ID),
        );
    }, $query->posts);

    return rest_ensure_response(array(
        'total'      => $query->found_posts,
        'results'    => $results,
        'page'       => $args['paged'],
        'per_page'   => $args['posts_per_page'],
        'total_pages'=> $query->max_num_pages,
    ));
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'tender-search-scripts',
        plugin_dir_url(__FILE__) . '../../dist/js/tender-search-scripts.js',
        array( 'wp-i18n', 'wp-element', 'wp-dom-ready', 'react'),
        filemtime(plugin_dir_path(__FILE__) . '../../dist/js/tender-search-scripts.js'),
        true
    );
    wp_set_script_translations(
        'tender-search-scripts',
        'tender-a-library',
        plugin_dir_path(__FILE__) . '../../languages' // o el path correcto si cambia
    );
});

