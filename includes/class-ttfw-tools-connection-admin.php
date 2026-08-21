<?php
/**
 * Admin UI for the Tornevall Tools account connection.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Tools connection controls on the plugin overview page.
 */
class TTFW_Tools_Connection_Admin {
	/**
	 * Registers admin rendering hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render' ) );
	}

	/**
	 * Renders connection feedback and the account card only on the plugin page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_tools_page() ) {
			return;
		}

		self::render_feedback();
		self::render_connection_card();
	}

	/**
	 * @return void
	 */
	private static function render_feedback() {
		$notice = isset( $_GET['ttfw_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['ttfw_notice'] ) ) : '';
		if ( ! in_array( $notice, array( 'connected', 'disconnected', 'connection_denied', 'connection_error' ), true ) ) {
			return;
		}

		$message = isset( $_GET['ttfw_message'] )
			? sanitize_text_field( rawurldecode( wp_unslash( (string) $_GET['ttfw_message'] ) ) )
			: '';
		if ( '' === $message ) {
			$message = 'connection_error' === $notice
				? __( 'The Tools account connection could not be completed.', 'tornevall-tools-for-wordpress' )
				: __( 'The Tools account connection was updated.', 'tornevall-tools-for-wordpress' );
		}

		$class = 'connection_error' === $notice ? 'notice-error' : ( 'connection_denied' === $notice ? 'notice-warning' : 'notice-success' );
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * @return void
	 */
	private static function render_connection_card() {
		$connected = TTFW_Tools_Connection::is_connected();
		$statuses = TTFW_Tools_Connection::service_statuses();

		echo '<div class="notice notice-info" style="padding:16px 18px; border-left-width:4px;">';
		echo '<h2 style="margin-top:0;">' . esc_html__( 'Tornevall Tools account', 'tornevall-tools-for-wordpress' ) . '</h2>';

		if ( ! $connected ) {
			echo '<p>' . esc_html__( 'Connect this WordPress installation to a logged-in Tornevall Tools account. Tools can then create dedicated site credentials for services you already have permission to use, instead of requiring you to copy existing tokens manually.', 'tornevall-tools-for-wordpress' ) . '</p>';
			echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" style="margin:12px 0 4px;">';
			echo '<input type="hidden" name="action" value="ttfw_tools_connect" />';
			wp_nonce_field( 'ttfw_tools_connect' );
			submit_button( __( 'Connect to Tornevall Tools', 'tornevall-tools-for-wordpress' ), 'primary', 'submit', false );
			echo '</form>';
			echo '</div>';
			return;
		}

		echo '<p><strong>' . esc_html__( 'Connected.', 'tornevall-tools-for-wordpress' ) . '</strong> ';
		echo esc_html__( 'Managed credentials are stored server-side in WordPress and are never displayed on this page.', 'tornevall-tools-for-wordpress' ) . '</p>';

		echo '<table class="widefat striped" style="max-width:900px; margin:12px 0;"><thead><tr>';
		echo '<th>' . esc_html__( 'Service', 'tornevall-tools-for-wordpress' ) . '</th>';
		echo '<th>' . esc_html__( 'Managed access', 'tornevall-tools-for-wordpress' ) . '</th>';
		echo '<th>' . esc_html__( 'Details', 'tornevall-tools-for-wordpress' ) . '</th>';
		echo '</tr></thead><tbody>';
		self::render_dnsbl_status( isset( $statuses['dnsbl'] ) ? $statuses['dnsbl'] : array() );
		self::render_guestbook_status( isset( $statuses['guestbook'] ) ? $statuses['guestbook'] : array() );
		echo '</tbody></table>';

		echo '<p><em>' . esc_html__( 'A manually configured service token remains the explicit override. Managed credentials are used only when the corresponding manual token is empty.', 'tornevall-tools-for-wordpress' ) . '</em></p>';

		echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" style="margin-top:12px;">';
		echo '<input type="hidden" name="action" value="ttfw_tools_disconnect" />';
		wp_nonce_field( 'ttfw_tools_disconnect' );
		submit_button( __( 'Disconnect this WordPress site', 'tornevall-tools-for-wordpress' ), 'secondary', 'submit', false );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * @param array<string,mixed> $status DNSBL status.
	 * @return void
	 */
	private static function render_dnsbl_status( $status ) {
		$available = ! empty( $status['available'] );
		$details = array();
		$permissions = isset( $status['permissions'] ) && is_array( $status['permissions'] ) ? $status['permissions'] : array();

		if ( $available ) {
			$labels = array(
				'allow_add'        => 'add',
				'allow_delete'     => 'delete',
				'allow_custom_txt' => 'custom TXT',
				'allow_spam_check' => 'spam check',
			);
			foreach ( $labels as $key => $label ) {
				if ( ! empty( $permissions[ $key ] ) ) {
					$details[] = $label;
				}
			}
		}

		self::render_status_row(
			'DNSBL / FraudBL',
			$available,
			$available ? implode( ', ', $details ) : self::reason_label( isset( $status['reason'] ) ? $status['reason'] : '' )
		);
	}

	/**
	 * @param array<string,mixed> $status Guestbook status.
	 * @return void
	 */
	private static function render_guestbook_status( $status ) {
		$available = ! empty( $status['available'] );
		$count = isset( $status['guestbook_count'] ) ? absint( $status['guestbook_count'] ) : 0;
		$details = $available
			? sprintf(
				/* translators: %d: number of guestbooks. */
				_n( '%d active guestbook', '%d active guestbooks', $count, 'tornevall-tools-for-wordpress' ),
				$count
			)
			: self::reason_label( isset( $status['reason'] ) ? $status['reason'] : '' );

		self::render_status_row( __( 'Guestbook', 'tornevall-tools-for-wordpress' ), $available, $details );
	}

	/**
	 * @param string $service Service name.
	 * @param bool   $available Whether managed access is available.
	 * @param string $details Public-safe details.
	 * @return void
	 */
	private static function render_status_row( $service, $available, $details ) {
		echo '<tr><td><strong>' . esc_html( $service ) . '</strong></td><td>';
		echo esc_html( $available ? __( 'Available', 'tornevall-tools-for-wordpress' ) : __( 'Not granted', 'tornevall-tools-for-wordpress' ) );
		echo '</td><td>' . esc_html( (string) $details ) . '</td></tr>';
	}

	/**
	 * @param mixed $reason Machine reason.
	 * @return string
	 */
	private static function reason_label( $reason ) {
		return match ( sanitize_key( (string) $reason ) ) {
			'no_active_dnsbl_token' => __( 'No active DNSBL token was available on the connected Tools account.', 'tornevall-tools-for-wordpress' ),
			'no_owned_guestbook' => __( 'The connected Tools account does not own an active guestbook.', 'tornevall-tools-for-wordpress' ),
			default => __( 'No managed credential was granted.', 'tornevall-tools-for-wordpress' ),
		};
	}

	/**
	 * @return bool
	 */
	private static function is_tools_page() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		return TTFW_Settings::PAGE_SLUG === $page;
	}
}
