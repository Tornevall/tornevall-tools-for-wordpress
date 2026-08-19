<?php
/**
 * Public WordPress REST proxy for guestbook submissions.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook_REST {
	const REST_NAMESPACE = 'ttfw/v1';

	/**
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/guestbook/entries',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'store' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function store( WP_REST_Request $request ) {
		if ( ! TTFW_Guestbook_API::configured() ) {
			return new WP_Error(
				'ttfw_guestbook_not_configured',
				__( 'Guestbook signing is not configured on this site.', 'tornevall-tools-for-wordpress' ),
				array( 'status' => 503 )
			);
		}

		$ip = self::visitor_ip();
		if ( self::rate_limited( $ip ) ) {
			return new WP_Error(
				'ttfw_guestbook_rate_limited',
				__( 'Too many guestbook submissions were sent in a short time. Please try again later.', 'tornevall-tools-for-wordpress' ),
				array( 'status' => 429 )
			);
		}

		if ( '' !== trim( sanitize_text_field( (string) $request->get_param( 'contact_company' ) ) ) ) {
			return rest_ensure_response( array( 'ok' => true, 'message' => __( 'Guestbook entry accepted.', 'tornevall-tools-for-wordpress' ) ) );
		}

		$name     = TTFW_Settings::limit_string( sanitize_text_field( (string) $request->get_param( 'name' ) ), 191 );
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$homepage = esc_url_raw( (string) $request->get_param( 'homepage' ) );
		$homecity = TTFW_Settings::limit_string( sanitize_text_field( (string) $request->get_param( 'homecity' ) ), 191 );
		$message  = TTFW_Settings::sanitize_long_text( $request->get_param( 'message' ), 10000 );

		if ( strlen( $name ) < 2 || strlen( trim( $message ) ) < 2 ) {
			return new WP_Error(
				'ttfw_guestbook_validation',
				__( 'Name and message are required.', 'tornevall-tools-for-wordpress' ),
				array( 'status' => 422 )
			);
		}

		$dnsbl = apply_filters(
			'tornevall_dnsbl_capabilities',
			null,
			array( 'consumer' => 'tornevall-tools-for-wordpress', 'feature' => 'guestbook' )
		);
		if ( is_array( $dnsbl ) && ! empty( $dnsbl['can_check'] ) && '' !== $ip ) {
			$check = apply_filters(
				'tornevall_dnsbl_check_ip',
				null,
				$ip,
				array( 'consumer' => 'tornevall-tools-for-wordpress', 'feature' => 'guestbook' )
			);
			if ( is_array( $check ) && ! empty( $check['ok'] ) && true === ( $check['listed'] ?? null ) ) {
				return new WP_Error(
					'ttfw_guestbook_dnsbl_blocked',
					__( 'This guestbook submission cannot be accepted from the current network address.', 'tornevall-tools-for-wordpress' ),
					array( 'status' => 403 )
				);
			}
		}

		$payload = array(
			'name'             => $name,
			'email'            => $email,
			'homepage'         => $homepage,
			'homecity'         => $homecity,
			'message'          => $message,
			'visitor_ip'       => $ip,
			'source_site_url'  => home_url( '/' ),
			'source_site_host' => (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
		);

		$result = ( new TTFW_Guestbook_API() )->submit( $payload );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * @return string
	 */
	public static function visitor_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * @param string $ip Visitor IP.
	 * @return bool
	 */
	private static function rate_limited( $ip ) {
		$key   = 'ttfw_guestbook_rate_' . md5( '' !== $ip ? $ip : 'unknown' );
		$count = (int) get_transient( $key );
		if ( $count >= 4 ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return false;
	}
}
