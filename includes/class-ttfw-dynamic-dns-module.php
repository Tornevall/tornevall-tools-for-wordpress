<?php
/**
 * Dynamic DNS module.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps a configured Tornevall Networks Dynamic DNS hostname updated.
 */
class TTFW_Dynamic_DNS_Module {
	public const CRON_HOOK     = 'ttfw_dyndns_update_event';
	public const STATUS_OPTION = 'ttfw_dyndns_status';

	/**
	 * Registers module hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_update' ) );
		add_action( 'admin_post_ttfw_dyndns_update_now', array( __CLASS__, 'handle_manual_update' ) );
		add_action( 'update_option_' . TTFW_Settings::OPTION_NAME, array( __CLASS__, 'handle_settings_update' ), 10, 2 );
	}

	/**
	 * Schedules the module after plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::reschedule( TTFW_Settings::get_options() );
	}

	/**
	 * Removes scheduled events when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Reschedules updates after settings change.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public static function handle_settings_update( $old_value, $new_value ) {
		unset( $old_value );
		self::reschedule( is_array( $new_value ) ? $new_value : array() );
	}

	/**
	 * Executes a scheduled update.
	 *
	 * @return void
	 */
	public static function run_scheduled_update() {
		self::update_hostname();
	}

	/**
	 * Handles the wp-admin Update now action.
	 *
	 * @return void
	 */
	public static function handle_manual_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to update Dynamic DNS settings.', 'tornevall-tools-for-wordpress' ) );
		}

		check_admin_referer( 'ttfw_dyndns_update_now' );
		$result = self::update_hostname();

		$redirect = add_query_arg(
			array(
				'page'        => TTFW_Settings::PAGE_SLUG,
				'ttfw_notice' => is_wp_error( $result ) ? 'dyndns_error' : 'dyndns_updated',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Updates the configured hostname through ToolsAPI.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function update_hostname() {
		$options  = TTFW_Settings::get_options();
		$enabled  = ! empty( $options['dyndns_enabled'] );
		$hostname = isset( $options['dyndns_hostname'] ) ? trim( (string) $options['dyndns_hostname'] ) : '';
		$token    = isset( $options['dyndns_token'] ) ? trim( (string) $options['dyndns_token'] ) : '';

		if ( ! $enabled ) {
			return self::store_error( 'ttfw_dyndns_disabled', __( 'Dynamic DNS is disabled.', 'tornevall-tools-for-wordpress' ) );
		}
		if ( '' === $hostname ) {
			return self::store_error( 'ttfw_dyndns_missing_hostname', __( 'A Dynamic DNS hostname is required.', 'tornevall-tools-for-wordpress' ) );
		}
		if ( '' === $token ) {
			return self::store_error( 'ttfw_dyndns_missing_token', __( 'A Dynamic DNS token is required.', 'tornevall-tools-for-wordpress' ) );
		}

		$result = ( new TTFW_API_Client() )->request(
			'POST',
			'/api/dyndns/update',
			$token,
			array(
				'hostname' => $hostname,
				'address'  => 'auto',
			)
		);

		if ( is_wp_error( $result ) ) {
			self::store_status(
				array(
					'ok'       => false,
					'hostname' => $hostname,
					'message'  => $result->get_error_message(),
				)
			);
			return $result;
		}

		$message = isset( $result['message'] ) && is_string( $result['message'] ) ? sanitize_text_field( $result['message'] ) : __( 'Dynamic DNS update completed.', 'tornevall-tools-for-wordpress' );
		$address = '';
		foreach ( array( 'address', 'ipv4', 'ipv6' ) as $key ) {
			if ( isset( $result[ $key ] ) && is_scalar( $result[ $key ] ) ) {
				$address = sanitize_text_field( (string) $result[ $key ] );
				break;
			}
		}

		self::store_status(
			array(
				'ok'       => true,
				'hostname' => $hostname,
				'address'  => $address,
				'message'  => $message,
			)
		);

		return $result;
	}

	/**
	 * Returns the latest module status.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_status() {
		$status = get_option( self::STATUS_OPTION, array() );
		return is_array( $status ) ? $status : array();
	}

	/**
	 * Returns a short configuration state for the module list.
	 *
	 * @return string
	 */
	public static function configuration_status() {
		$options = TTFW_Settings::get_options();
		if ( empty( $options['dyndns_enabled'] ) ) {
			return __( 'Disabled', 'tornevall-tools-for-wordpress' );
		}
		if ( empty( $options['dyndns_hostname'] ) || empty( $options['dyndns_token'] ) ) {
			return __( 'Needs configuration', 'tornevall-tools-for-wordpress' );
		}
		return __( 'Enabled', 'tornevall-tools-for-wordpress' );
	}

	/**
	 * Stores an error and returns it.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private static function store_error( $code, $message ) {
		$error = new WP_Error( $code, $message );
		self::store_status(
			array(
				'ok'      => false,
				'message' => $message,
			)
		);
		return $error;
	}

	/**
	 * Stores a sanitized status snapshot without credentials.
	 *
	 * @param array<string,mixed> $status Status data.
	 * @return void
	 */
	private static function store_status( $status ) {
		$status['updated_at'] = current_time( 'mysql' );
		update_option( self::STATUS_OPTION, $status, false );
	}

	/**
	 * Applies the selected WordPress cron interval.
	 *
	 * @param array<string,mixed> $options Plugin options.
	 * @return void
	 */
	private static function reschedule( $options ) {
		wp_clear_scheduled_hook( self::CRON_HOOK );

		if ( empty( $options['dyndns_enabled'] ) ) {
			return;
		}

		$schedule = isset( $options['dyndns_schedule'] ) ? (string) $options['dyndns_schedule'] : 'hourly';
		if ( ! in_array( $schedule, array( 'hourly', 'twicedaily', 'daily' ), true ) ) {
			$schedule = 'hourly';
		}

		wp_schedule_event( time() + MINUTE_IN_SECONDS, $schedule, self::CRON_HOOK );
	}
}
