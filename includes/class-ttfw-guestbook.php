<?php
/**
 * Public Tools guestbook embed integration.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the public Tools guestbook shortcode.
 */
class TTFW_Guestbook {
	const DEFAULT_EMBED_URL = 'https://tools.tornevall.net/guestbook/embed.js';

	/**
	 * Shortcode instance counter.
	 *
	 * @var int
	 */
	private static $instance = 0;

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'tornevall_guestbook', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Renders a guestbook target and enqueues the matching Tools embed script.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'theme' => 'tools',
				'limit' => 10,
			),
			is_array( $atts ) ? $atts : array(),
			'tornevall_guestbook'
		);

		$theme = sanitize_key( (string) $atts['theme'] );
		if ( ! in_array( $theme, array( 'tools', 'miazma', 'terminal' ), true ) ) {
			$theme = 'tools';
		}

		$limit = absint( $atts['limit'] );
		$limit = max( 1, min( 50, $limit ) );

		self::$instance++;
		$target_id = sprintf( 'ttfw-guestbook-%d-%d', self::$instance, wp_rand( 1000, 999999 ) );
		$embed_url = self::get_embed_url();
		$script_url = add_query_arg(
			array(
				'theme'  => $theme,
				'limit'  => $limit,
				'target' => $target_id,
			),
			$embed_url
		);

		wp_enqueue_script(
			'ttfw-guestbook-embed-' . self::$instance,
			$script_url,
			array(),
			null,
			true
		);

		return sprintf(
			'<div id="%1$s" class="ttfw-guestbook-embed" data-theme="%2$s"></div>',
			esc_attr( $target_id ),
			esc_attr( $theme )
		);
	}

	/**
	 * Returns the approved HTTPS guestbook embed endpoint.
	 *
	 * Developers can override this for staging through the
	 * ttfw_guestbook_embed_url filter. Invalid or non-HTTPS values fall back
	 * to the production Tools endpoint.
	 *
	 * @return string
	 */
	private static function get_embed_url() {
		$url = apply_filters( 'ttfw_guestbook_embed_url', self::DEFAULT_EMBED_URL );
		$url = is_string( $url ) ? esc_url_raw( $url ) : '';

		if ( ! wp_http_validate_url( $url ) || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return self::DEFAULT_EMBED_URL;
		}

		return $url;
	}
}
