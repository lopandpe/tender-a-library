<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


function get_library_search_url_with_filters( $filters = array() ) {
    // Ajusta el ID o slug de la página de la biblioteca según tu instalación:
    $library_page_id = get_option('library_search_page_id');
    $base_url = $library_page_id ? get_permalink($library_page_id) : home_url('/biblioteca/');

    $query = [];
    foreach ( $filters as $key => $value ) {
        if ( is_array($value) ) {
            foreach ( $value as $v ) {
                $query[] = urlencode($key . '[]') . '=' . urlencode($v);
            }
        } else {
            $query[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    $query_string = implode('&', $query);

    return $base_url . ( strpos($base_url, '?' ) === false ? '?' : '&' ) . $query_string;
}