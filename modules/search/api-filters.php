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

    // Sort by dotted section number so 1.10 comes after 1.3.
    usort($sections, function($a, $b) {
        return tal_compare_section_numbers($a->section_number, $b->section_number);
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

function tal_compare_section_numbers($left, $right) {
    $left = is_scalar($left) ? trim((string) $left) : '';
    $right = is_scalar($right) ? trim((string) $right) : '';

    if ($left === $right) {
        return 0;
    }
    if ($left === '') {
        return 1;
    }
    if ($right === '') {
        return -1;
    }

    $left_parts = preg_split('/[^\d]+/', $left, -1, PREG_SPLIT_NO_EMPTY);
    $right_parts = preg_split('/[^\d]+/', $right, -1, PREG_SPLIT_NO_EMPTY);
    $max = max(count($left_parts), count($right_parts));

    for ($i = 0; $i < $max; $i++) {
        $left_part = isset($left_parts[$i]) ? (int) $left_parts[$i] : -1;
        $right_part = isset($right_parts[$i]) ? (int) $right_parts[$i] : -1;

        if ($left_part < $right_part) {
            return -1;
        }
        if ($left_part > $right_part) {
            return 1;
        }
    }

    return strcmp($left, $right);
}
