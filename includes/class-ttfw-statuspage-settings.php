<?php
/**
 * Statuspage settings.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists the selected Tools status page and cache preferences.
 */
class TTFW_Statuspage_Settings {
	const OPTION_NAME = 'ttfw_statuspage_settings';

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'slug'      => '',
			'cache_ttl' => 300,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get() {
		$value = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $value ) ? $value : array(), self::defaults() );
	}

	/**
	 * @return string
	 */
	public static function slug() {
		$settings = self::get();
		return self::sanitize_slug( $settings['slug'] ?? '' );
	}

	/**
	 * @return int
	 */
	public static function cache_ttl() {
		$settings = self::get();
		$ttl = absint( $settings['cache_ttl'] ?? 300 );
		return max( 60, min( 3600, $ttl ) );
	}

	/**
	 * @param mixed $value Raw slug.
	 * @return string
	 */
	public static function sanitize_slug( $value ) {
		$value = strtolower( trim( sanitize_text_field( (string) $value ) ) );
		return preg_match( '/^[a-z0-9][a-z0-9_-]{0,190}$/', $value ) ? $value : '';
	}

	/**
	 * @param string $slug Selected slug.
	 * @param int    $ttl Cache TTL.
	 * @return bool
	 */
	public static function save( $slug, $ttl ) {
		$slug = self::sanitize_slug( $slug );
		$ttl = max( 60, min( 3600, absint( $ttl ) ) );
		return update_option(
			self::OPTION_NAME,
			array(
				'slug'      => $slug,
				'cache_ttl' => $ttl,
			),
			false
		);
	}
}
