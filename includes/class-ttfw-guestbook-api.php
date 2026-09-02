<?php
/**
 * Server-side Tools guestbook API client.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook_API {
	const DEFAULT_API_URL = 'https://tools.tornevall.net/api/guestbook';

	/**
	 * @return string
	 */
	public static function api_url() {
		$url = esc_url_raw( TTFW_Guestbook_Settings::api_url() );

		if ( ! wp_http_validate_url( $url ) || 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return self::DEFAULT_API_URL;
		}

		return untrailingslashit( $url );
	}

	/**
	 * @return bool
	 */
	public static function configured() {
		return '' !== trim( TTFW_Guestbook_Settings::token() );
	}

	/**
	 * @return array<string,mixed>|WP_Error
	 */
	public function owned_books() {
		return $this->request( 'GET', '/owned/books' );
	}

	/**
	 * @param array<string,mixed> $payload Guestbook create payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_owned_book( $payload ) {
		return $this->request( 'POST', '/owned/books', is_array( $payload ) ? $payload : array() );
	}

	/**
	 * @param int                 $guestbook_id Guestbook id.
	 * @param array<string,mixed> $payload Guestbook update payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_owned_book( $guestbook_id, $payload ) {
		$guestbook_id = absint( $guestbook_id );
		if ( $guestbook_id < 1 ) {
			return new WP_Error(
				'ttfw_guestbook_invalid_book',
				__( 'A valid Tools guestbook id is required.', 'tornevall-tools-for-wordpress' ),
				array( 'status' => 400 )
			);
		}

		return $this->request(
			'PATCH',
			'/owned/books/' . $guestbook_id,
			is_array( $payload ) ? $payload : array()
		);
	}

	/**
	 * @param array<string,mixed> $query Public listing query.
	 * @return array<string,mixed>|WP_Error
	 */
	public function owned_entries( $query = array() ) {
		$query = array_merge(
			is_array( $query ) ? $query : array(),
			TTFW_Guestbook_Settings::selector()
		);
		$path = '/owned/entries';
		if ( ! empty( $query ) ) {
			$path = add_query_arg( $query, $path );
		}
		return $this->request( 'GET', $path );
	}

	/**
	 * @param array<string,mixed> $payload Guestbook form data.
	 * @return array<string,mixed>|WP_Error
	 */
	public function submit( $payload ) {
		$payload = array_merge(
			is_array( $payload ) ? $payload : array(),
			TTFW_Guestbook_Settings::selector()
		);

		return $this->request( 'POST', '/entries', $payload );
	}

	/**
	 * @param array<string,mixed> $query Admin listing query.
	 * @return array<string,mixed>|WP_Error
	 */
	public function admin_entries( $query = array() ) {
		$query = array_merge(
			is_array( $query ) ? $query : array(),
			TTFW_Guestbook_Settings::selector()
		);
		$path = '/admin/entries';
		if ( ! empty( $query ) ) {
			$path = add_query_arg( $query, $path );
		}
		return $this->request( 'GET', $path );
	}

	/**
	 * @param int  $entry_id Entry id.
	 * @param bool $is_visible Target visibility.
	 * @return array<string,mixed>|WP_Error
	 */
	public function set_visibility( $entry_id, $is_visible ) {
		$payload = array_merge(
			array( 'is_visible' => (bool) $is_visible ),
			TTFW_Guestbook_Settings::selector()
		);

		return $this->request(
			'PATCH',
			'/admin/entries/' . absint( $entry_id ) . '/visibility',
			$payload
		);
	}

	/**
	 * @param string              $method HTTP method.
	 * @param string              $path Relative API path.
	 * @param array<string,mixed> $payload Payload.
	 * @return array<string,mixed>|WP_Error
	 */
	private function request( $method, $path, $payload = array() ) {
		$token = trim( TTFW_Guestbook_Settings::token() );
		if ( '' === $token ) {
			return new WP_Error(
				'ttfw_guestbook_not_configured',
				__( 'No Tools guestbook token is configured.', 'tornevall-tools-for-wordpress' ),
				array( 'status' => 503 )
			);
		}

		$url  = self::api_url() . '/' . ltrim( $path, '/' );
		$args = array(
			'method'  => strtoupper( (string) $method ),
			'timeout' => 60,
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			),
		);

		if ( 'GET' !== strtoupper( (string) $method ) ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $payload );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$body   = is_array( $body ) ? $body : array();

		if ( $status < 200 || $status >= 300 ) {
			$message = isset( $body['message'] ) ? sanitize_text_field( (string) $body['message'] ) : __( 'Tools guestbook request failed.', 'tornevall-tools-for-wordpress' );
			return new WP_Error(
				'ttfw_guestbook_remote_error',
				$message,
				array( 'status' => $status > 0 ? $status : 502 )
			);
		}

		return $body;
	}
}
