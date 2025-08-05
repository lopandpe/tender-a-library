<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function tender_api_build_query_args($request) {
    $search    = sanitize_text_field($request->get_param('q'));
    $sections  = $request->get_param('sections');
    $languages = $request->get_param('languages');

    $paged          = $request->get_param('page') ? absint($request->get_param('page')) : 1;
    $posts_per_page = $request->get_param('per_page') ? absint($request->get_param('per_page')) : 10;

    // Build base args for filters
    $base_args = [
        'post_type'      => 'tender_book',
        'post_status'    => 'publish',
        'orderby'        => $request->get_param('orderby') ? sanitize_text_field($request->get_param('orderby')) : 'date',
        'order'          => $request->get_param('order') ? sanitize_text_field($request->get_param('order')) : 'desc',
        'tax_query'      => [],
        'meta_query'     => [],
        'fields'         => 'ids',
        'posts_per_page' => -1, // We'll paginate after merging
    ];

    // Sections (taxonomy)
    if (!empty($sections) && is_array($sections)) {
        $base_args['tax_query'][] = [
            'taxonomy' => 'tender_section',
            'field'    => 'slug',
            'terms'    => array_map('sanitize_text_field', $sections),
        ];
    }

    // Languages (taxonomy)
    if (!empty($languages) && is_array($languages)) {
        $base_args['tax_query'][] = [
            'taxonomy' => 'tender_language',
            'field'    => 'slug',
            'terms'    => array_map('sanitize_text_field', $languages),
        ];
    }

    // Remove empty queries
    if (empty($base_args['meta_query'])) unset($base_args['meta_query']);
    if (empty($base_args['tax_query'])) unset($base_args['tax_query']);

    // 1. Query for 's' (title/content/excerpt)
    $ids_title = [];
    if ($search) {
        $args_title = $base_args;
        $args_title['s'] = $search;
        $query_title = new WP_Query($args_title);
        $ids_title = $query_title->posts;
    }

    // 2. Query for meta fields
    $ids_meta = [];
    if ($search) {
        $args_meta = $base_args;
        $args_meta['meta_query'][] = [
            'relation' => 'OR',
            [
                'key'     => 'tender_book_author',
                'value'   => $search,
                'compare' => 'LIKE'
            ],
            [
                'key'     => 'tender_book_publisher',
                'value'   => $search,
                'compare' => 'LIKE'
            ],
            [
                'key'     => 'tender_book_language',
                'value'   => $search,
                'compare' => 'LIKE'
            ],
            [
                'key'     => 'tender_book_excerpt',
                'value'   => $search,
                'compare' => 'LIKE'
            ],
            [
                'key'     => 'tender_book_other_authors',
                'value'   => $search,
                'compare' => 'LIKE'
            ]
        ];
        $query_meta = new WP_Query($args_meta);
        $ids_meta = $query_meta->posts;
    }

    // 3. Merge IDs (OR logic)
    $all_ids = array_unique(array_merge($ids_title, $ids_meta));

    // If no search, just use the base args for all books (with filters)
    if (!$search) {
        $final_args = $base_args;
        $final_args['fields'] = null; // get full posts
        $final_args['posts_per_page'] = $posts_per_page;
        $final_args['paged'] = $paged;
        return $final_args;
    }

    // 4. Paginate and return final args
    $offset = ($paged - 1) * $posts_per_page;
    $paged_ids = array_slice($all_ids, $offset, $posts_per_page);

    $final_args = [
        'post_type'      => 'tender_book',
        'post_status'    => 'publish',
        'orderby'        => 'post__in',
        'order'          => 'ASC',
        'post__in'       => $paged_ids ?: [0], // avoid returning all if empty
        'posts_per_page' => $posts_per_page ?: 12, // default to 12 if not set
        'paged'          => 1, // already paginated
    ];

    return $final_args;
}