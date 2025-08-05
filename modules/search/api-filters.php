<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('tender-library/v1', '/filters', array(
        'methods'  => 'GET',
        'callback' => 'tender_api_filters',
        'permission_callback' => '__return_true', // Permitir acceso público
    ));
});

function tender_api_filters($request) {
    $filters = array();

    // Get all tender_section terms (including empty for full tree)
    $sections = get_terms(array(
        'taxonomy'   => 'tender_section',
        'hide_empty' => false,
    ));

    // Add Carbon Field value to each term
    foreach ($sections as &$term) {
        $term->section_number = carbon_get_term_meta($term->term_id, 'tender_section_number');
    }
    unset($term);

    // Sort by section_number (as string comparison)
    usort($sections, function($a, $b) {
        return strcmp($a->section_number, $b->section_number);
    });

    $filters['sections'] = tal_build_section_tree($sections);

    // Languages (flat)
    $filters['languages'] = get_terms(array(
        'taxonomy' => 'tender_language',
        'hide_empty' => true,
    ));

    return rest_ensure_response($filters);
}


// Build hierarchical tree
function tal_build_section_tree($terms, $parent = 0) {
    $branch = [];
    foreach ($terms as $term) {
        if ($term->parent == $parent) {
            $children = tal_build_section_tree($terms, $term->term_id);
            if ($children) {
                $term->children = $children;
            }
            $branch[] = $term;
        }
    }
    return $branch;
}