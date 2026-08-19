<?php
/**
 * Admin settings and module overview.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin options and settings UI.
 */
class TTFW_Settings {
	public const OPTION_NAME = 'ttfw_options';
	public const PAGE_SLUG   = 'tornevall-tools';

	/**
	 * Returns default options.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'dyndns_enabled'  => false,
			'dyndns_hostname' => '',
			'dyndns_token'    => '',
			'dyndns_schedule' => 'hourly',
		);
	}

	/**
	 * Returns merged options.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_options() {
		$options = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $options ) ? $options : array(), self::defaults() );
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TTFW_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds plugin action links.
	 *
	 * @param array<int,string> $links Existing links.
	 * @return array<int,string>
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'tornevall-tools-for-wordpress' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds the top-level Tools admin page.
	 *
	 * @return void
	 */
	public static function add_page() {
		add_menu_page(
			esc_html__( 'Tornevall Tools', 'tornevall-tools-for-wordpress' ),
			esc_html__( 'Tornevall Tools', 'tornevall-tools-for-wordpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-admin-tools',
			80
		);
	}

	/**
	 * Registers plugin settings.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			'ttfw_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Renders the plugin dashboard and settings.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options   = self::get_options();
		$has_token = '' !== (string) $options['dyndns_token'];
		$status    = TTFW_Dynamic_DNS_Module::get_status();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Tornevall Tools for WordPress', 'tornevall-tools-for-wordpress' ) . '</h1>';
		echo '<p>' . esc_html__( 'A modular bridge between WordPress and Tornevall Networks Tools. Features only contact Tornevall Networks services when you enable and configure them.', 'tornevall-tools-for-wordpress' ) . '</p>';
		self::render_notice();

		echo '<h2>' . esc_html__( 'Modules', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Module', 'tornevall-tools-for-wordpress' ) . '</th><th>' . esc_html__( 'Description', 'tornevall-tools-for-wordpress' ) . '</th><th>' . esc_html__( 'Status', 'tornevall-tools-for-wordpress' ) . '</th></tr></thead><tbody>';
		foreach ( TTFW_Module_Registry::all() as $module ) {
			echo '<tr><td><strong>' . esc_html( $module['name'] ) . '</strong></td><td>' . esc_html( $module['description'] ) . '</td><td>' . esc_html( $module['status'] ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<form action="options.php" method="post">';
		settings_fields( 'ttfw_settings_group' );
		echo '<h2>' . esc_html__( 'Dynamic DNS', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<p>' . esc_html__( 'Keep one hostname from the Tornevall Networks Dynamic DNS service updated with the public source address seen by Tools.', 'tornevall-tools-for-wordpress' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::row_checkbox( 'dyndns_enabled', __( 'Enable Dynamic DNS', 'tornevall-tools-for-wordpress' ), ! empty( $options['dyndns_enabled'] ), __( 'When enabled, WordPress schedules updates using WP-Cron.', 'tornevall-tools-for-wordpress' ) );
		self::row_text( 'dyndns_hostname', __( 'Hostname', 'tornevall-tools-for-wordpress' ), $options['dyndns_hostname'], __( 'A Dynamic DNS hostname owned by the configured token, for example home.dyn.tornevall.net.', 'tornevall-tools-for-wordpress' ) );
		self::row_secret( 'dyndns_token', __( 'Dynamic DNS token', 'tornevall-tools-for-wordpress' ), $has_token, __( 'Create or rotate this token in the Tornevall Networks Tools Dynamic DNS service. Leave blank to keep the stored token.', 'tornevall-tools-for-wordpress' ) );
		self::row_select(
			'dyndns_schedule',
			__( 'Update interval', 'tornevall-tools-for-wordpress' ),
			$options['dyndns_schedule'],
			array(
				'hourly'     => __( 'Hourly', 'tornevall-tools-for-wordpress' ),
				'twicedaily' => __( 'Twice daily', 'tornevall-tools-for-wordpress' ),
				'daily'      => __( 'Daily', 'tornevall-tools-for-wordpress' ),
			)
		);
		echo '</tbody></table>';
		submit_button();
		echo '</form>';

		echo '<h3>' . esc_html__( 'Dynamic DNS status', 'tornevall-tools-for-wordpress' ) . '</h3>';
		if ( empty( $status ) ) {
			echo '<p>' . esc_html__( 'No update has been attempted yet.', 'tornevall-tools-for-wordpress' ) . '</p>';
		} else {
			echo '<p><strong>' . esc_html( ! empty( $status['ok'] ) ? __( 'Last update succeeded.', 'tornevall-tools-for-wordpress' ) : __( 'Last update failed.', 'tornevall-tools-for-wordpress' ) ) . '</strong></p>';
		if ( ! empty( $status['message'] ) ) {
			echo '<p>' . esc_html( (string) $status['message'] ) . '</p>';
		}
		if ( ! empty( $status['address'] ) ) {
			echo '<p>' . esc_html__( 'Address:', 'tornevall-tools-for-wordpress' ) . ' <code>' . esc_html( (string) $status['address'] ) . '</code></p>';
		}
		if ( ! empty( $status['updated_at'] ) ) {
			echo '<p>' . esc_html__( 'Recorded:', 'tornevall-tools-for-wordpress' ) . ' ' . esc_html( (string) $status['updated_at'] ) . '</p>';
		}
		}

		if ( ! empty( $options['dyndns_enabled'] ) && '' !== trim( (string) $options['dyndns_hostname'] ) && $has_token ) {
			echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
			echo '<input type="hidden" name="action" value="ttfw_dyndns_update_now" />';
			wp_nonce_field( 'ttfw_dyndns_update_now' );
			submit_button( __( 'Update now', 'tornevall-tools-for-wordpress' ), 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '<hr />';
		echo '<p>' . esc_html__( 'External service:', 'tornevall-tools-for-wordpress' ) . ' <a href="' . esc_url( 'https://tools.tornevall.net/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Tornevall Networks Tools', 'tornevall-tools-for-wordpress' ) . '</a>. ';
		echo '<a href="' . esc_url( 'https://tools.tornevall.net/docs/en/terms-of-service' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Terms', 'tornevall-tools-for-wordpress' ) . '</a> - ';
		echo '<a href="' . esc_url( 'https://tools.tornevall.net/docs/en/privacy-policy' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy', 'tornevall-tools-for-wordpress' ) . '</a>.</p>';
		echo '</div>';
	}

	/**
	 * Sanitizes settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize_options( $input ) {
		$current  = self::get_options();
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$output   = $defaults;

		$output['dyndns_enabled']  = isset( $input['dyndns_enabled'] ) && '1' === (string) $input['dyndns_enabled'];
		$output['dyndns_hostname'] = self::sanitize_hostname( self::scalar( $input, 'dyndns_hostname', '' ) );
		$output['dyndns_token']    = self::sanitize_secret( $input, 'dyndns_token', $current['dyndns_token'] );

		$schedule = sanitize_key( self::scalar( $input, 'dyndns_schedule', $defaults['dyndns_schedule'] ) );
		$output['dyndns_schedule'] = in_array( $schedule, array( 'hourly', 'twicedaily', 'daily' ), true ) ? $schedule : $defaults['dyndns_schedule'];

		return $output;
	}

	/**
	 * Sanitizes a DNS hostname.
	 *
	 * @param mixed $value Raw hostname.
	 * @return string
	 */
	public static function sanitize_hostname( $value ) {
		$value = strtolower( trim( sanitize_text_field( (string) $value ), ". \t\n\r\0\x0B" ) );
		if ( '' === $value ) {
			return '';
		}
		if ( strlen( $value ) > 253 || ! preg_match( '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $value ) ) {
			add_settings_error( self::OPTION_NAME, 'ttfw_invalid_dyndns_hostname', __( 'The Dynamic DNS hostname is not valid.', 'tornevall-tools-for-wordpress' ) );
			return '';
		}
		return $value;
	}

	/**
	 * Limits a string while supporting mbstring when available.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	public static function limit_string( $value, $max_length ) {
		$value = (string) $value;
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_length ) : substr( $value, 0, $max_length );
	}

	/**
	 * Displays a result notice after a manual update.
	 *
	 * @return void
	 */
	private static function render_notice() {
		$notice = isset( $_GET['ttfw_notice'] ) ? sanitize_key( wp_unslash( $_GET['ttfw_notice'] ) ) : '';
		if ( ! in_array( $notice, array( 'dyndns_updated', 'dyndns_error' ), true ) ) {
			return;
		}

		$status  = TTFW_Dynamic_DNS_Module::get_status();
		$is_ok   = 'dyndns_updated' === $notice;
		$message = ! empty( $status['message'] ) ? (string) $status['message'] : ( $is_ok ? __( 'Dynamic DNS was updated.', 'tornevall-tools-for-wordpress' ) : __( 'Dynamic DNS update failed.', 'tornevall-tools-for-wordpress' ) );
		echo '<div class="notice ' . esc_attr( $is_ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Sanitizes a secret while preserving an existing value on blank submission.
	 *
	 * @param array<string,mixed> $input Input array.
	 * @param string              $key Option key.
	 * @param mixed               $current Current value.
	 * @return string
	 */
	private static function sanitize_secret( $input, $key, $current ) {
		if ( ! array_key_exists( $key, $input ) ) {
			return (string) $current;
		}
		$value = trim( sanitize_text_field( self::scalar( $input, $key, '' ) ) );
		return '' === $value ? (string) $current : self::limit_string( $value, 4000 );
	}

	/**
	 * Gets one scalar option input safely.
	 *
	 * @param array<string,mixed> $input Input array.
	 * @param string              $key Option key.
	 * @param mixed               $default Default value.
	 * @return string
	 */
	private static function scalar( $input, $key, $default ) {
		if ( ! is_array( $input ) || ! array_key_exists( $key, $input ) || is_array( $input[ $key ] ) || is_object( $input[ $key ] ) ) {
			return (string) $default;
		}
		return (string) wp_unslash( $input[ $key ] );
	}

	/**
	 * Builds a WordPress settings field name.
	 *
	 * @param string $key Option key.
	 * @return string
	 */
	private static function field_name( $key ) {
		return self::OPTION_NAME . '[' . $key . ']';
	}

	private static function row_text( $key, $label, $value, $description ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $key ) . '" type="text" class="regular-text" autocomplete="off" name="' . esc_attr( self::field_name( $key ) ) . '" value="' . esc_attr( $value ) . '" /><p class="description">' . esc_html( $description ) . '</p></td></tr>';
	}

	private static function row_secret( $key, $label, $has_value, $description ) {
		$placeholder = $has_value ? __( 'Stored - leave blank to keep unchanged', 'tornevall-tools-for-wordpress' ) : __( 'Paste token', 'tornevall-tools-for-wordpress' );
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $key ) . '" type="password" class="regular-text" autocomplete="new-password" placeholder="' . esc_attr( $placeholder ) . '" name="' . esc_attr( self::field_name( $key ) ) . '" value="" /><p class="description">' . esc_html( $description ) . '</p></td></tr>';
	}

	private static function row_checkbox( $key, $label, $checked, $description ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="' . esc_attr( self::field_name( $key ) ) . '" value="1" ' . checked( $checked, true, false ) . ' /> ' . esc_html( $description ) . '</label></td></tr>';
	}

	private static function row_select( $key, $label, $value, $choices ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><select id="' . esc_attr( $key ) . '" name="' . esc_attr( self::field_name( $key ) ) . '">';
		foreach ( $choices as $choice_value => $choice_label ) {
			echo '<option value="' . esc_attr( $choice_value ) . '" ' . selected( $value, $choice_value, false ) . '>' . esc_html( $choice_label ) . '</option>';
		}
		echo '</select></td></tr>';
	}
}
