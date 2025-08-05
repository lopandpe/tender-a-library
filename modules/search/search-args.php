<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

return [
    'q' => [ 'type' => 'string' ],
    'sections' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
    'languages' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
    'page' => [ 'type' => 'integer', 'default' => 1 ],
    'per_page' => [ 'type' => 'integer', 'default' => 10 ],
    'orderby' => [
        'type' => 'string',
        'default' => 'date',
        'enum' => [ 'date', 'title', 'author', 'publisher', 'language' ],
    ],
    'order' => [
        'type' => 'string',
        'default' => 'desc',
        'enum' => [ 'asc', 'desc' ],
    ],
];