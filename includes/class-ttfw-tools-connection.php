<?php
/**
 * Tornevall Tools account pairing and managed service credentials.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles explicit administrator pairing with a logged-in Tools account.
 */
class TTFW_Tools_Connection {
	const OPTION_NAME = 'ttfw_tools_connection';
	const PAIRING_TRANSIENT_PREFIX = 'ttfw_tools_pairing_';

	/**
	 * Registers connection hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_ttfw_tools_connect', array( __CLASS__, 'start_connection' ) );
		add_action( 'admin_post_ttfw_tools_connection_complete', array( __CLASS__, 'complete_connection' ) );
		add_action( 'admin_post_ttfw_tools_disconnect', array( __CLASS__, 'disconnect' ) );
		add_filter( 'tornevall_dnsbl_managed_api_token', array( __CLASS__, 'filter_dnsbl_token' ) );
	}

	/**
	 * Starts a short-lived Tools pairing and redirects to the Tools approval page.
	 *
	 * @return void
	 */
	public static function start_connection() {
		self::require_admin();
		check_admin_referer( 'ttfw_tools_connect' );

		$client = new TTFW_API_Client();
		$callback_url = admin_url( 'admin-post.php?action=ttfw_tools_connection_complete' );
		$response = $client->public_request(
			'POST',
			'/api/integrations/wordpress/device',
			array(
				'site_name'          => get_bloginfo( 'name' ),
				'site_url'           => home_url( '/' ),
				'callback_url'       => $callback_url,
				'requested_services' => array( 'dnsbl', 'guestbook' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::redirect_with_notice( 'connection_error', $response->get_error_message() );
		}

		$device_code = isset( $response['device_code'] ) ? trim( (string) $response['device_code'] ) : '';
		$user_code = isset( $response['user_code'] ) ? sanitize_text_field( (string) $response['user_code'] ) : '';
		$verification_url = isset( $response['verification_uri_complete'] ) ? esc_url_raw( (string) $response['verification_uri_complete'] ) : '';
		$expires_in = isset( $response['expires_in'] ) ? absint( $response['expires_in'] ) : 600;

		if ( '' === $device_code || '' === $user_code || ! self::is_tools_authorization_url( $verification_url ) ) {
			self::redirect_with_notice( 'connection_error', __( 'Tools returned an invalid WordPress pairing response.', 'tornevall-tools-for-wordpress' ) );
		}

		set_transient(
			self::pairing_transient_key(),
			array(
				'device_code' => $device_code,
				'user_code'   => $user_code,
			),
			max( 60, min( 600, $expires_in ) )
		);

		wp_redirect( $verification_url );
		exit;
	}

	/**
	 * Completes the server-side one-time credential exchange after Tools approval.
	 *
	 * @return void
	 */
	public static function complete_connection() {
		self::require_admin();

		$pairing = get_transient( self::pairing_transient_key() );
		$pairing = is_array( $pairing ) ? $pairing : array();
		$returned_code = isset( $_GET['user_code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['user_code'] ) ) : '';
		$state = isset( $_GET['ttfw_connection'] ) ? sanitize_key( wp_unslash( (string) $_GET['ttfw_connection'] ) ) : '';

		if ( empty( $pairing['device_code'] ) || empty( $pairing['user_code'] ) || '' === $returned_code || ! hash_equals( (string) $pairing['user_code'], $returned_code ) ) {
			delete_transient( self::pairing_transient_key() );
			self::redirect_with_notice( 'connection_error', __( 'The WordPress pairing session is missing, expired, or does not match this callback.', 'tornevall-tools-for-wordpress' ) );
		}

		if ( 'denied' === $state ) {
			delete_transient( self::pairing_transient_key() );
			self::redirect_with_notice( 'connection_denied', __( 'The Tools account connection was denied.', 'tornevall-tools-for-wordpress' ) );
		}

		if ( 'complete' !== $state ) {
			self::redirect_with_notice( 'connection_error', __( 'Tools returned an unknown WordPress pairing state.', 'tornevall-tools-for-wordpress' ) );
		}

		$client = new TTFW_API_Client();
		$response = $client->public_request(
			'POST',
			'/api/integrations/wordpress/token',
			array( 'device_code' => (string) $pairing['device_code'] )
		);

		delete_transient( self::pairing_transient_key() );

		if ( is_wp_error( $response ) ) {
			self::redirect_with_notice( 'connection_error', $response->get_error_message() );
		}

		$connection = self::sanitize_connection_payload( $response );
		if ( empty( $connection['connected'] ) ) {
			self::redirect_with_notice( 'connection_error', __( 'Tools approved the connection but returned no usable connection metadata.', 'tornevall-tools-for-wordpress' ) );
		}

		update_option( self::OPTION_NAME, $connection, false );
		self::redirect_with_notice( 'connected', __( 'This WordPress site is now connected to your Tornevall Tools account.', 'tornevall-tools-for-wordpress' ) );
	}

	/**
	 * Clears locally managed credentials.
	 *
	 * @return void
	 */
	public static function disconnect() {
		self::require_admin();
		check_admin_referer( 'ttfw_tools_disconnect' );

		delete_option( self::OPTION_NAME );
		delete_transient( self::pairing_transient_key() );
		self::redirect_with_notice( 'disconnected', __( 'The local Tools account connection and managed credentials were removed.', 'tornevall-tools-for-wordpress' ) );
	}

	/**
	 * Returns sanitized connection metadata and server-side credentials.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_connection() {
		$value = get_option( self::OPTION_NAME, array() );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * @return bool
	 */
	public static function is_connected() {
		$connection = self::get_connection();
		return ! empty( $connection['connected'] );
	}

	/**
	 * Returns metadata for one managed service.
	 *
	 * @param string $service Service key.
	 * @return array<string,mixed>
	 */
	public static function service( $service ) {
		$connection = self::get_connection();
		$key = sanitize_key( (string) $service );
		$value = isset( $connection['services'][ $key ] ) ? $connection['services'][ $key ] : array();
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Returns a managed service token for server-side use only.
	 *
	 * @param string $service Service key.
	 * @return string
	 */
	public static function managed_token( $service ) {
		$service_data = self::service( $service );
		if ( empty( $service_data['available'] ) || empty( $service_data['token'] ) ) {
			return '';
		}

		return trim( (string) $service_data['token'] );
	}

	/**
	 * Optional bridge consumed by the standalone DNSBL plugin.
	 *
	 * @param mixed $token Existing managed-token candidate.
	 * @return string
	 */
	public static function filter_dnsbl_token( $token ) {
		$token = is_scalar( $token ) ? trim( (string) $token ) : '';
		return '' !== $token ? $token : self::managed_token( 'dnsbl' );
	}

	/**
	 * Returns public-safe service status for admin rendering.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function service_statuses() {
		$status = array();
		foreach ( array( 'dnsbl', 'guestbook' ) as $service ) {
			$data = self::service( $service );
			$status[ $service ] = array(
				'available'       => ! empty( $data['available'] ),
				'reason'          => isset( $data['reason'] ) ? sanitize_key( (string) $data['reason'] ) : '',
				'permissions'     => isset( $data['permissions'] ) && is_array( $data['permissions'] ) ? $data['permissions'] : array(),
				'guestbook_count' => isset( $data['guestbook_count'] ) ? absint( $data['guestbook_count'] ) : 0,
			);
		}
		return $status;
	}

	/**
	 * Sanitizes the one-time Tools exchange response before persistence.
	 *
	 * @param array<string,mixed> $response Tools response.
	 * @return array<string,mixed>
	 */
	private static function sanitize_connection_payload( $response ) {
		$remote_connection = isset( $response['connection'] ) && is_array( $response['connection'] ) ? $response['connection'] : array();
		$remote_services = isset( $response['services'] ) && is_array( $response['services'] ) ? $response['services'] : array();
		$services = array();

		foreach ( array( 'dnsbl', 'guestbook' ) as $service ) {
			$remote = isset( $remote_services[ $service ] ) && is_array( $remote_services[ $service ] ) ? $remote_services[ $service ] : array();
			$entry = array(
				'available' => ! empty( $remote['available'] ),
				'reason'    => isset( $remote['reason'] ) ? sanitize_key( (string) $remote['reason'] ) : '',
			);

			if ( ! empty( $entry['available'] ) && isset( $remote['token'] ) && is_scalar( $remote['token'] ) ) {
				$entry['token'] = substr( trim( (string) $remote['token'] ), 0, 4000 );
			}
			if ( isset( $remote['credential_id'] ) ) {
				$entry['credential_id'] = absint( $remote['credential_id'] );
			}
			if ( isset( $remote['permissions'] ) && is_array( $remote['permissions'] ) ) {
				$entry['permissions'] = self::sanitize_permissions( $remote['permissions'] );
			}
			if ( isset( $remote['delete_guardrails'] ) && is_array( $remote['delete_guardrails'] ) ) {
				$entry['delete_guardrails'] = array_map( 'absint', array_filter( $remote['delete_guardrails'], 'is_numeric' ) );
			}
			if ( isset( $remote['guestbook_count'] ) ) {
				$entry['guestbook_count'] = absint( $remote['guestbook_count'] );
			}

			$services[ $service ] = $entry;
		}

		return array(
			'connected'   => ! empty( $response['ok'] ),
			'user_id'     => isset( $remote_connection['user_id'] ) ? absint( $remote_connection['user_id'] ) : 0,
			'site_url'    => isset( $remote_connection['site_url'] ) ? esc_url_raw( (string) $remote_connection['site_url'] ) : '',
			'approved_at' => isset( $remote_connection['approved_at'] ) ? sanitize_text_field( (string) $remote_connection['approved_at'] ) : '',
			'services'    => $services,
		);
	}

	/**
	 * @param array<string|int,mixed> $permissions Raw permissions.
	 * @return array<string|int,bool|string>
	 */
	private static function sanitize_permissions( $permissions ) {
		$output = array();
		foreach ( $permissions as $key => $value ) {
			$clean_key = is_int( $key ) ? $key : sanitize_key( (string) $key );
			if ( is_bool( $value ) ) {
				$output[ $clean_key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$output[ $clean_key ] = sanitize_text_field( (string) $value );
			}
		}
		return $output;
	}

	/**
	 * @return string
	 */
	private static function pairing_transient_key() {
		return self::PAIRING_TRANSIENT_PREFIX . get_current_user_id();
	}

	/**
	 * Validates that a browser redirect stays on the fixed Tools origin.
	 *
	 * @param string $url Authorization URL.
	 * @return bool
	 */
	private static function is_tools_authorization_url( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}

		return 'https' === strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) )
			&& 'tools.tornevall.net' === strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	}

	/**
	 * @return void
	 */
	private static function require_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage the Tornevall Tools connection.', 'tornevall-tools-for-wordpress' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirects back to the plugin page with a safe status message.
	 *
	 * @param string $notice Notice key.
	 * @param string $message Human-readable message.
	 * @return void
	 */
	private static function redirect_with_notice( $notice, $message ) {
		$url = add_query_arg(
			array(
				'page'         => TTFW_Settings::PAGE_SLUG,
				'ttfw_notice'  => sanitize_key( (string) $notice ),
				'ttfw_message' => rawurlencode( sanitize_text_field( (string) $message ) ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
