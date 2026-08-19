<?php
/**
 * Shared Tornevall Tools API client.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Makes server-side requests to documented Tornevall Networks Tools endpoints.
 */
class TTFW_API_Client {
	public const BASE_URL = 'https://tools.tornevall.net';

	/**
	 * Sends a JSON request to a Tools API endpoint.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $path API path beginning with /api/.
	 * @param string              $token Bearer token.
	 * @param array<string,mixed> $body Optional JSON body.
	 * @return array<string,mixed>|WP_Error
	 */
	public function request( $method, $path, $token, $body = array() ) {
		$method = strtoupper( sanitize_key( (string) $method ) );
		$path   = '/' . ltrim( (string) $path, '/' );
		$token  = trim( (string) $token );

		if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
			return new WP_Error( 'ttfw_invalid_method', __( 'Unsupported Tools API request method.', 'tornevall-tools-for-wordpress' ) );
		}

		if ( 0 !== strpos( $path, '/api/' ) ) {
			return new WP_Error( 'ttfw_invalid_path', __( 'Invalid Tools API path.', 'tornevall-tools-for-wordpress' ) );
		}

		if ( '' === $token ) {
			return new WP_Error( 'ttfw_missing_token', __( 'A Tools service token is required.', 'tornevall-tools-for-wordpress' ) );
		}

		$args = array(
			'method'      => $method,
			'timeout'     => 20,
			'redirection' => 2,
			'headers'     => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'User-Agent'    => 'Tornevall-Tools-for-WordPress/' . TTFW_VERSION . '; ' . home_url( '/' ),
			),
		);

		if ( 'POST' === $method ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE_URL . $path, $args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ttfw_tools_http_error', $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$raw    = (string) wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'ttfw_tools_invalid_json',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'Tornevall Tools returned an invalid JSON response (HTTP %d).', 'tornevall-tools-for-wordpress' ),
					$status
				)
			);
		}

		if ( $status < 200 || $status >= 300 ) {
			$message = self::error_message( $data );
			if ( '' === $message ) {
				$message = sprintf(
					/* translators: %d: HTTP status code. */
					__( 'Tornevall Tools returned HTTP %d.', 'tornevall-tools-for-wordpress' ),
					$status
				);
			}
			return new WP_Error( 'ttfw_tools_api_error', $message, array( 'status' => $status ) );
		}

		return $data;
	}

	/**
	 * Extracts a safe error message from a Tools response.
	 *
	 * @param array<string,mixed> $data Response data.
	 * @return string
	 */
	private static function error_message( $data ) {
		foreach ( array( 'message', 'error' ) as $key ) {
			if ( isset( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
				return sanitize_text_field( $data[ $key ] );
			}
		}

		if ( isset( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
			return sanitize_text_field( $data['error']['message'] );
		}

		return '';
	}
}
