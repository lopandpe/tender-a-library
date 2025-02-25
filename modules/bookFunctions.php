<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

add_filter('use_block_editor_for_post_type', 'mgd_disable_gutenberg', 10, 2);
function mgd_disable_gutenberg($current_status, $post_type)
{
	// Use your post type key instead of 'product'
	if ($post_type === 'tender_book') return false;
	return $current_status;
}



//Generate signature
add_action('carbon_fields_post_meta_container_saved', 'save_tender_book_signature_on_creation');
function save_tender_book_signature_on_creation($post_id)
{
	if (get_post_type($post_id) !== 'tender_book') {
		return false;
	}
	if (!carbon_get_post_meta($post_id, 'tender_book_sig1')) {
		// Example of building new meta values based on existing meta
		$current_author_meta_key = 'tender_book_author';
		$sig1_meta_key = '_tender_book_sig1';
		$sig2_meta_key = '_tender_book_sig2';

		// Fetch existing meta and title
		$current_author_value = carbon_get_post_meta($post_id, $current_author_meta_key);
		$current_title = get_the_title($post_id);
		// Build the new meta values using custom functions (ensure these are defined)
		$sig1_meta_value = $current_author_value ? generate_tender_sig1($current_author_value) : 'XXX';
		$sig2_meta_value = $current_title ? generate_tender_sig2($current_title) : 'YYY';

		// Save the new meta values
		update_post_meta($post_id, $sig1_meta_key, $sig1_meta_value);
		update_post_meta($post_id, $sig2_meta_key, $sig2_meta_value);
	}
}

function generate_tender_sig1($author)
{
	$author = remove_accents($author);
	$signature = mb_substr($author, 0, 3);
	return strtoupper($signature);
}

function generate_tender_sig2($title)
{
	$title = remove_accents($title);
	$shortened_title = preg_replace("/^(el|lo|la|los|las|un|una|uno|unos|un|the|a|si)\b\ */i", "", strtolower($title));
	$signature = substr($shortened_title, 0, 3);
	return strtolower($signature);
}

add_action('carbon_fields_post_meta_container_saved', 'save_tender_taxonomies_to_categories', 10, 2);

function save_tender_taxonomies_to_categories($post_id, $container)
{
	$association_field = "tender_book_section"; //your association field
	$custom_tax = "tender_section"; //your taxonomy
	// Check if this is an autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check if current user can edit post
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	update_tender_term_association($post_id, "tender_book_section", "tender_section");
	update_tender_term_association($post_id, "tender_book_language", "tender_language");
}

function update_tender_term_association($post_id, $association_field, $custom_tax)
{

	$bnc_terms = carbon_get_post_meta($post_id, $association_field);
	$terms = [];
	if (is_array($bnc_terms) && count($bnc_terms)) {
		foreach ($bnc_terms as $term) {
			$terms[] = (int) $term['id'];
		}


		// Update post categories with BNC terms
		wp_set_object_terms($post_id, $terms, $custom_tax);
	} else {
		wp_set_object_terms($post_id, [], $custom_tax);
	}
}
