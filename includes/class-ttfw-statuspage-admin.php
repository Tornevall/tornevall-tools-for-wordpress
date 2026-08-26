<?php
/**
 * Statuspage admin configuration.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the Statuspage setup and diagnostics surface.
 */
class TTFW_Statuspage_Admin {
	const PAGE_SLUG = 'tornevall-tools-statuspage';

	/** @return void */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_post_ttfw_statuspage_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_ttfw_statuspage_refresh', array( __CLASS__, 'refresh' ) );
	}

	/** @return void */
	public static function add_page() {
		add_submenu_page(
			TTFW_Settings::PAGE_SLUG,
			esc_html__( 'Statuspage', 'tornevall-tools-for-wordpress' ),
			esc_html__( 'Statuspage', 'tornevall-tools-for-wordpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/** @return void */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = TTFW_Statuspage_Settings::get();
		$snapshot = TTFW_Statuspage::snapshot();
		echo '<div class="wrap"><h1>' . esc_html__( 'Statuspage', 'tornevall-tools-for-wordpress' ) . '</h1>';
		echo '<p>' . esc_html__( 'Display one public Tornevall Tools status page inside WordPress. Tools remains the authoritative source for components, incidents and incident updates.', 'tornevall-tools-for-wordpress' ) . '</p>';
		self::render_notice();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="ttfw_statuspage_save">';
		wp_nonce_field( 'ttfw_statuspage_save' );
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="ttfw-statuspage-slug">' . esc_html__( 'Status page slug', 'tornevall-tools-for-wordpress' ) . '</label></th><td><input id="ttfw-statuspage-slug" class="regular-text" type="text" name="slug" value="' . esc_attr( (string) $settings['slug'] ) . '" maxlength="191"><p class="description">' . esc_html__( 'The public Tools status page identifier, for example tools. Leave blank to disable the WordPress Statuspage integration.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="ttfw-statuspage-cache-ttl">' . esc_html__( 'Live cache', 'tornevall-tools-for-wordpress' ) . '</label></th><td><input id="ttfw-statuspage-cache-ttl" type="number" min="60" max="3600" step="60" name="cache_ttl" value="' . esc_attr( (string) $settings['cache_ttl'] ) . '"> ' . esc_html__( 'seconds', 'tornevall-tools-for-wordpress' ) . '<p class="description">' . esc_html__( 'Successful responses are cached locally. If Tools cannot be reached, the last successful status is shown as stale instead of being reported as an outage.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Save Statuspage settings', 'tornevall-tools-for-wordpress' ) );
		echo '</form>';

		echo '<hr><h2>' . esc_html__( 'Status and diagnostics', 'tornevall-tools-for-wordpress' ) . '</h2>';
		self::render_diagnostics( $snapshot );
		if ( '' !== TTFW_Statuspage_Settings::slug() ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ttfw_statuspage_refresh">';
			wp_nonce_field( 'ttfw_statuspage_refresh' );
			submit_button( __( 'Refresh status now', 'tornevall-tools-for-wordpress' ), 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '<hr><h2>' . esc_html__( 'Public rendering', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<p>' . esc_html__( 'Add this shortcode to any WordPress page:', 'tornevall-tools-for-wordpress' ) . ' <code>[' . esc_html( TTFW_Statuspage::SHORTCODE ) . ']</code></p>';
		echo '<p>' . esc_html__( 'Include recent resolved incidents with:', 'tornevall-tools-for-wordpress' ) . ' <code>[' . esc_html( TTFW_Statuspage::SHORTCODE ) . ' history="1"]</code></p>';
		echo '</div>';
	}

	/** @return void */
	public static function save() {
		self::require_admin_capability();
		check_admin_referer( 'ttfw_statuspage_save' );

		$raw_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$slug = TTFW_Statuspage_Settings::sanitize_slug( $raw_slug );
		$ttl = isset( $_POST['cache_ttl'] ) ? absint( wp_unslash( $_POST['cache_ttl'] ) ) : 300;
		if ( '' !== trim( $raw_slug ) && '' === $slug ) {
			self::redirect( 'invalid_slug' );
		}

		TTFW_Statuspage_Settings::save( $slug, $ttl );
		if ( '' === $slug ) {
			delete_option( TTFW_Statuspage::LAST_GOOD_OPTION );
			self::redirect( 'disabled' );
		}

		$snapshot = TTFW_Statuspage::snapshot( true );
		if ( empty( $snapshot['payload'] ) ) {
			self::redirect( 'verify_failed' );
		}
		self::redirect( 'verified' );
	}

	/** @return void */
	public static function refresh() {
		self::require_admin_capability();
		check_admin_referer( 'ttfw_statuspage_refresh' );

		$snapshot = TTFW_Statuspage::snapshot( true );
		if ( 'stale' === ( $snapshot['health'] ?? '' ) ) {
			self::redirect( 'stale' );
		}
		if ( empty( $snapshot['payload'] ) ) {
			self::redirect( 'unavailable' );
		}
		self::redirect( 'refreshed' );
	}

	/**
	 * @param array<string,mixed> $snapshot Snapshot.
	 * @return void
	 */
	private static function render_diagnostics( $snapshot ) {
		$health = sanitize_key( (string) ( $snapshot['health'] ?? 'unavailable' ) );
		$payload = is_array( $snapshot['payload'] ?? null ) ? $snapshot['payload'] : array();
		$overall = is_array( $payload['overall'] ?? null ) ? $payload['overall'] : array();
		echo '<table class="widefat striped"><tbody>';
		echo '<tr><th>' . esc_html__( 'Integration', 'tornevall-tools-for-wordpress' ) . '</th><td>' . esc_html( TTFW_Statuspage::configuration_status() ) . '</td></tr>';
		if ( ! empty( $overall['status'] ) ) {
			echo '<tr><th>' . esc_html__( 'Remote platform state', 'tornevall-tools-for-wordpress' ) . '</th><td><code>' . esc_html( (string) $overall['status'] ) . '</code></td></tr>';
		}
		if ( ! empty( $snapshot['fetched_at'] ) ) {
			echo '<tr><th>' . esc_html__( 'Last successful fetch', 'tornevall-tools-for-wordpress' ) . '</th><td>' . esc_html( (string) $snapshot['fetched_at'] ) . '</td></tr>';
		}
		if ( 'stale' === $health ) {
			echo '<tr><th>' . esc_html__( 'Cache', 'tornevall-tools-for-wordpress' ) . '</th><td>' . esc_html__( 'Stale fallback in use. This is a communication state, not a confirmed service outage.', 'tornevall-tools-for-wordpress' ) . '</td></tr>';
		}
		if ( ! empty( $snapshot['error'] ) ) {
			echo '<tr><th>' . esc_html__( 'Last request', 'tornevall-tools-for-wordpress' ) . '</th><td>' . esc_html__( 'Failed. See the status above; credentials and raw provider details are never exposed here.', 'tornevall-tools-for-wordpress' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/** @return void */
	private static function require_admin_capability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage Statuspage settings.', 'tornevall-tools-for-wordpress' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * @param string $notice Notice code.
	 * @return void
	 */
	private static function redirect( $notice ) {
		$url = add_query_arg(
			array(
				'page'                   => self::PAGE_SLUG,
				'ttfw_statuspage_notice' => sanitize_key( $notice ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/** @return void */
	private static function render_notice() {
		$notice = isset( $_GET['ttfw_statuspage_notice'] ) ? sanitize_key( wp_unslash( $_GET['ttfw_statuspage_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only feedback code generated by this plugin.
		$notices = array(
			'invalid_slug'  => array( 'error', __( 'The Statuspage slug is invalid.', 'tornevall-tools-for-wordpress' ) ),
			'disabled'      => array( 'success', __( 'Statuspage integration was disabled.', 'tornevall-tools-for-wordpress' ) ),
			'verify_failed' => array( 'warning', __( 'Settings were saved, but the public status page could not be loaded yet.', 'tornevall-tools-for-wordpress' ) ),
			'verified'      => array( 'success', __( 'Statuspage settings were saved and the public status page was verified.', 'tornevall-tools-for-wordpress' ) ),
			'stale'         => array( 'warning', __( 'The live status request failed. WordPress is retaining the last successful status as stale.', 'tornevall-tools-for-wordpress' ) ),
			'unavailable'   => array( 'warning', __( 'The live status request failed and there is no previous successful status to display.', 'tornevall-tools-for-wordpress' ) ),
			'refreshed'     => array( 'success', __( 'Statuspage data was refreshed.', 'tornevall-tools-for-wordpress' ) ),
		);
		if ( ! isset( $notices[ $notice ] ) ) {
			return;
		}
		$class = 'error' === $notices[ $notice ][0] ? 'notice-error' : ( 'warning' === $notices[ $notice ][0] ? 'notice-warning' : 'notice-success' );
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $notices[ $notice ][1] ) . '</p></div>';
	}
}
