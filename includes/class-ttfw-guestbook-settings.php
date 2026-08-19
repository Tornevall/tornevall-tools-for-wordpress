<?php
/**
 * Guestbook-specific server-side settings.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook_Settings {
	const OPTION_NAME = 'ttfw_guestbook_options';
	const SETTINGS_GROUP = 'ttfw_guestbook_settings_group';

	/**
	 * @return array<string,string>
	 */
	public static function defaults() {
		return array(
			'api_url' => TTFW_Guestbook_API::DEFAULT_API_URL,
			'token'   => '',
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_options() {
		$options = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $options ) ? $options : array(), self::defaults() );
	}

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * @return void
	 */
	public static function register() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * @param mixed $input Raw option payload.
	 * @return array<string,string>
	 */
	public static function sanitize( $input ) {
		$current = self::get_options();
		$input   = is_array( $input ) ? $input : array();
		$url     = isset( $input['api_url'] ) ? esc_url_raw( wp_unslash( (string) $input['api_url'] ) ) : self::defaults()['api_url'];

		if ( ! wp_http_validate_url( $url ) || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			$url = self::defaults()['api_url'];
		}

		$token = isset( $input['token'] ) ? trim( sanitize_text_field( wp_unslash( (string) $input['token'] ) ) ) : '';
		if ( '' === $token ) {
			$token = (string) $current['token'];
		}

		return array(
			'api_url' => $url,
			'token'   => substr( $token, 0, 4000 ),
		);
	}

	/**
	 * @return string
	 */
	public static function api_url() {
		return (string) self::get_options()['api_url'];
	}

	/**
	 * @return string
	 */
	public static function token() {
		return (string) self::get_options()['token'];
	}
}
