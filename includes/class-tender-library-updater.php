<?php
/**
 * Private update checker for Tender Library.
 *
 * @package Tender_Library
 */

if (!defined('ABSPATH')) {
	exit;
}

class Tender_Library_Updater
{
	/** @var string */
	private $plugin_file;

	/** @var string */
	private $plugin_basename;

	/** @var string */
	private $current_version;

	/** @var string */
	private $metadata_url;

	/** @var string */
	private $cache_key;

	public function __construct($plugin_file, $current_version, $metadata_url)
	{
		$this->plugin_file = $plugin_file;
		$this->plugin_basename = plugin_basename($plugin_file);
		$this->current_version = $current_version;
		$this->metadata_url = $metadata_url;
		$this->cache_key = 'tender_library_update_' . md5($metadata_url);
	}

	public function register()
	{
		add_filter('pre_set_site_transient_update_plugins', array($this, 'filter_update_transient'));
		add_filter('plugins_api', array($this, 'filter_plugin_information'), 20, 3);
		add_action('upgrader_process_complete', array($this, 'clear_update_cache'), 10, 2);
	}

	public function filter_update_transient($transient)
	{
		if (!is_object($transient) || empty($transient->checked[$this->plugin_basename])) {
			return $transient;
		}

		$metadata = $this->get_metadata();
		if (!$metadata || version_compare($metadata['version'], $this->current_version, '<=')) {
			return $transient;
		}

		$update = new stdClass();
		$update->id = $this->metadata_url;
		$update->slug = $metadata['slug'];
		$update->plugin = $this->plugin_basename;
		$update->new_version = $metadata['version'];
		$update->url = $metadata['homepage'];
		$update->package = $metadata['download_url'];
		$update->tested = $metadata['tested'];
		$update->requires = $metadata['requires'];
		$update->requires_php = $metadata['requires_php'];

		$transient->response[$this->plugin_basename] = $update;

		return $transient;
	}

	public function filter_plugin_information($result, $action, $args)
	{
		if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'tender-library') {
			return $result;
		}

		$metadata = $this->get_metadata();
		if (!$metadata) {
			return $result;
		}

		$info = new stdClass();
		$info->name = $metadata['name'];
		$info->slug = $metadata['slug'];
		$info->version = $metadata['version'];
		$info->author = $metadata['author'];
		$info->homepage = $metadata['homepage'];
		$info->requires = $metadata['requires'];
		$info->tested = $metadata['tested'];
		$info->requires_php = $metadata['requires_php'];
		$info->last_updated = $metadata['last_updated'];
		$info->sections = $metadata['sections'];
		$info->download_link = $metadata['download_url'];

		return $info;
	}

	public function clear_update_cache($upgrader, $options)
	{
		if (empty($options['type']) || $options['type'] !== 'plugin') {
			return;
		}

		delete_site_transient($this->cache_key);
	}

	private function get_metadata()
	{
		$cached = get_site_transient($this->cache_key);
		if (is_array($cached)) {
			return $cached;
		}

		$url = apply_filters('tender_library_update_metadata_url', $this->metadata_url);
		$url = esc_url_raw($url);

		if (!$this->is_valid_http_url($url)) {
			return false;
		}

		$response = wp_remote_get($url, array(
			'timeout' => 8,
			'redirection' => 3,
			'user-agent' => 'Tender Library/' . $this->current_version . '; ' . home_url('/'),
			'headers' => array(
				'Accept' => 'application/json',
			),
		));

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return false;
		}

		$decoded = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($decoded)) {
			return false;
		}

		$metadata = $this->sanitize_metadata($decoded);
		if (!$metadata) {
			return false;
		}

		set_site_transient($this->cache_key, $metadata, 12 * HOUR_IN_SECONDS);

		return $metadata;
	}

	private function sanitize_metadata($data)
	{
		$version = isset($data['version']) ? sanitize_text_field($data['version']) : '';
		$download_url = isset($data['download_url']) ? esc_url_raw($data['download_url']) : '';
		$slug = isset($data['slug']) ? sanitize_key($data['slug']) : '';

		if ($slug !== "tender-library" || !$version || !$this->is_valid_http_url($download_url) || !$this->is_same_host_as_metadata($download_url)) {
			return false;
		}

		$sections = array();
		if (!empty($data['sections']) && is_array($data['sections'])) {
			foreach ($data['sections'] as $key => $value) {
				$sections[sanitize_key($key)] = wp_kses_post((string) $value);
			}
		}

		if (empty($sections['description'])) {
			$sections['description'] = __('Private library/tender management plugin.', 'tender-library');
		}

		return array(
			'name' => isset($data['name']) ? sanitize_text_field($data['name']) : 'Tender Library',
			'slug' => $slug,
			'version' => $version,
			'download_url' => $download_url,
			'requires' => isset($data['requires']) ? sanitize_text_field($data['requires']) : '6.4',
			'tested' => isset($data['tested']) ? sanitize_text_field($data['tested']) : '',
			'requires_php' => isset($data['requires_php']) ? sanitize_text_field($data['requires_php']) : '8.1',
			'last_updated' => isset($data['last_updated']) ? sanitize_text_field($data['last_updated']) : '',
			'sections' => $sections,
			'homepage' => isset($data['homepage']) ? esc_url_raw($data['homepage']) : TENDER_LIBRARY_UPDATE_URI,
			'author' => isset($data['author']) ? wp_kses_post((string) $data['author']) : 'Luis Gómez',
		);
	}

	private function is_same_host_as_metadata($url)
	{
		$url_parts = wp_parse_url($url);
		$metadata_parts = wp_parse_url($this->metadata_url);

		return !empty($url_parts["host"])
			&& !empty($metadata_parts["host"])
			&& strtolower($url_parts["host"]) === strtolower($metadata_parts["host"]);
	}

	private function is_valid_http_url($url)
	{
		if (!$url) {
			return false;
		}

		$parts = wp_parse_url($url);
		return !empty($parts['scheme'])
			&& $parts["scheme"] === "https"
			&& !empty($parts['host']);
	}
}
