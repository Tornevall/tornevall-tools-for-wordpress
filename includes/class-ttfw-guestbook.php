<?php
/**
 * Public Tools guestbook integration.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook {
	const TURNSTILE_SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

	/** @var int */
	private static $instance = 0;

	/**
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'tornevall_guestbook', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
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

		$limit = max( 1, min( 50, absint( $atts['limit'] ) ) );
		self::$instance++;

		$target_id = sprintf( 'ttfw-guestbook-%d-%d', self::$instance, wp_rand( 1000, 999999 ) );
		$form_id   = $target_id . '-form';
		$endpoint  = rest_url( 'ttfw/v1/guestbook/entries' );
		$api_configured = TTFW_Guestbook_API::configured();
		$turnstile_configured = TTFW_Guestbook_Settings::turnstile_configured();

		if ( $api_configured ) {
			$dependencies = array();
			if ( $turnstile_configured ) {
				wp_enqueue_script(
					'cloudflare-turnstile',
					self::TURNSTILE_SCRIPT_URL,
					array(),
					TTFW_VERSION,
					true
				);
				$dependencies[] = 'cloudflare-turnstile';
			}

			wp_enqueue_script(
				'ttfw-guestbook',
				TTFW_URL . 'assets/guestbook.js',
				$dependencies,
				TTFW_VERSION,
				true
			);
		}

		if ( ! $api_configured ) {
			return '<p class="ttfw-guestbook-not-configured">' . esc_html__( 'This guestbook has not been connected to Tools yet.', 'tornevall-tools-for-wordpress' ) . '</p>';
		}

		$html = sprintf(
			'<div id="%1$s" class="ttfw-guestbook-embed" data-ttfw-guestbook-list data-endpoint="%2$s" data-theme="%3$s" data-limit="%4$d"></div>',
			esc_attr( $target_id ),
			esc_url( $endpoint ),
			esc_attr( $theme ),
			$limit
		);

		if ( ! $turnstile_configured ) {
			$html .= '<p class="ttfw-guestbook-signing-disabled">' . esc_html__( 'Guestbook reading is available, but signing is disabled until Cloudflare Turnstile is configured for this WordPress site.', 'tornevall-tools-for-wordpress' ) . '</p>';
			return $html;
		}

		$html .= sprintf(
			'<form id="%1$s" class="ttfw-guestbook-form" data-ttfw-guestbook-form data-endpoint="%2$s" data-list-target="%3$s">',
			esc_attr( $form_id ),
			esc_url( $endpoint ),
			esc_attr( $target_id )
		);
		$html .= '<h3>' . esc_html__( 'Sign the guestbook', 'tornevall-tools-for-wordpress' ) . '</h3>';
		$html .= '<p><label>' . esc_html__( 'Name', 'tornevall-tools-for-wordpress' ) . '<br><input type="text" name="name" required maxlength="191" autocomplete="name"></label></p>';
		$html .= '<p><label>' . esc_html__( 'E-mail (not public)', 'tornevall-tools-for-wordpress' ) . '<br><input type="email" name="email" maxlength="254" autocomplete="email"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Homepage', 'tornevall-tools-for-wordpress' ) . '<br><input type="url" name="homepage" maxlength="2048" placeholder="https://"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Home city', 'tornevall-tools-for-wordpress' ) . '<br><input type="text" name="homecity" maxlength="191"></label></p>';
		$html .= '<p><label>' . esc_html__( 'Message', 'tornevall-tools-for-wordpress' ) . '<br><textarea name="message" required maxlength="10000" rows="6"></textarea></label></p>';
		$html .= '<p style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>Company<input type="text" name="contact_company" tabindex="-1" autocomplete="off"></label></p>';
		$html .= '<div class="cf-turnstile" data-sitekey="' . esc_attr( TTFW_Guestbook_Settings::turnstile_site_key() ) . '" data-action="guestbook"></div>';
		$html .= '<p><button type="submit">' . esc_html__( 'Sign guestbook', 'tornevall-tools-for-wordpress' ) . '</button></p>';
		$html .= '<div data-ttfw-guestbook-status role="status" aria-live="polite"></div>';
		$html .= '</form>';

		return $html;
	}
}
