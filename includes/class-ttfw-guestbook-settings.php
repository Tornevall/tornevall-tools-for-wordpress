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
			'api_url'               => TTFW_Guestbook_API::DEFAULT_API_URL,
			'token'                 => '',
			'turnstile_site_key'    => '',
			'turnstile_secret_key'  => '',
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

		$token = self::preserve_secret( $input, 'token', (string) $current['token'], 4000 );
		$turnstile_site_key = isset( $input['turnstile_site_key'] )
			? trim( sanitize_text_field( wp_unslash( (string) $input['turnstile_site_key'] ) ) )
			: (string) $current['turnstile_site_key'];
		$turnstile_secret_key = self::preserve_secret(
			$input,
			'turnstile_secret_key',
			(string) $current['turnstile_secret_key'],
			4000
		);

		return array(
			'api_url'              => $url,
			'token'                => substr( $token, 0, 4000 ),
			'turnstile_site_key'   => substr( $turnstile_site_key, 0, 4000 ),
			'turnstile_secret_key' => substr( $turnstile_secret_key, 0, 4000 ),
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

	/**
	 * @return bool
	 */
	public static function turnstile_configured() {
		return '' !== self::turnstile_site_key() && '' !== self::turnstile_secret_key();
	}

	/**
	 * @return string
	 */
	public static function turnstile_site_key() {
		return trim( (string) self::get_options()['turnstile_site_key'] );
	}

	/**
	 * @return string
	 */
	public static function turnstile_secret_key() {
		return trim( (string) self::get_options()['turnstile_secret_key'] );
	}

	/**
	 * @param array<string,mixed> $input Raw settings input.
	 * @param string              $key Settings key.
	 * @param string              $current Current secret.
	 * @param int                 $limit Maximum length.
	 * @return string
	 */
	private static function preserve_secret( $input, $key, $current, $limit ) {
		$value = isset( $input[ $key ] )
			? trim( sanitize_text_field( wp_unslash( (string) $input[ $key ] ) ) )
			: '';

		if ( '' === $value ) {
			$value = $current;
		}

		return substr( $value, 0, $limit );
	}
}
