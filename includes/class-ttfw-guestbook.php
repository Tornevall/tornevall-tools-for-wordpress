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
	 * Renders a guestbook target and, when configured, a local signing form.
	 *
	 * The browser posts only to the local WordPress REST endpoint. The Tools
	 * guestbook API token is added later by PHP and is never exposed here.
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
		$form_id   = $target_id . '-form';
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

		if ( TTFW_Guestbook_API::configured() ) {
			wp_enqueue_script(
				'ttfw-guestbook-form',
				TTFW_URL . 'assets/guestbook.js',
				array(),
				TTFW_VERSION,
				true
			);
		}

		$html = sprintf(
			'<div id="%1$s" class="ttfw-guestbook-embed" data-theme="%2$s"></div>',
			esc_attr( $target_id ),
			esc_attr( $theme )
		);

		if ( ! TTFW_Guestbook_API::configured() ) {
			return $html;
		}

		$html .= sprintf(
			'<form id="%1$s" class="ttfw-guestbook-form" data-ttfw-guestbook-form data-endpoint="%2$s">',
			esc_attr( $form_id ),
			esc_url( rest_url( 'ttfw/v1/guestbook/entries' ) )
		);
		$html .= '<h3>' . esc_html__( 'Sign the guestbook', 'tornevall-tools-for-wordpress' ) . '</h3>';
		$html .= '<p><label>' . esc_html__( 'Name', 'tornevall-tools-for-wordpress' ) . '<br><input type="text" name="name" required maxlength="191" autocomplete="name"></label></p>';
		$html .= '<p><label>' . esc_html__( 'E-mail (not public)', 'tornevall-tools-for-wordpress' ) . '<br><input type="email" name="email" maxlength="254" autocomplete="email"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Homepage', 'tornevall-tools-for-wordpress' ) . '<br><input type="url" name="homepage" maxlength="2048" placeholder="https://"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Home city', 'tornevall-tools-for-wordpress' ) . '<br><input type="text" name="homecity" maxlength="191"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Message', 'tornevall-tools-for-wordpress' ) . '<br><textarea name="message" required maxlength="10000" rows="6"></textarea></label></p>';
		$html .= '<p style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>Company<input type="text" name="contact_company" tabindex="-1" autocomplete="off"></label></p>';
		$html .= '<p><button type="submit">' . esc_html__( 'Sign guestbook', 'tornevall-tools-for-wordpress' ) . '</button></p>';
		$html .= '<div data-ttfw-guestbook-status role="status" aria-live="polite"></div>';
		$html .= '</form>';

		return $html;
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
