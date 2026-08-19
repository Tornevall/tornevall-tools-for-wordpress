<?php
/**
 * WordPress admin client for the central Tools guestbook.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook_Admin {
	const PAGE_SLUG = 'tornevall-tools-guestbook';
	const DNSBL_PLUGIN_SLUG = 'tornevall-networks-dnsbl-implementation';

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_post_ttfw_guestbook_visibility', array( __CLASS__, 'handle_visibility' ) );
		add_action( 'admin_post_ttfw_guestbook_dnsbl_check', array( __CLASS__, 'handle_dnsbl_check' ) );
		add_action( 'admin_post_ttfw_guestbook_dnsbl_report', array( __CLASS__, 'handle_dnsbl_report' ) );
	}

	/**
	 * @return void
	 */
	public static function add_page() {
		add_management_page(
			esc_html__( 'Tools Guestbook', 'tornevall-tools-for-wordpress' ),
			esc_html__( 'Tools Guestbook', 'tornevall-tools-for-wordpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options      = TTFW_Guestbook_Settings::get_options();
		$has_token    = '' !== trim( (string) $options['token'] );
		$has_turnstile_secret = '' !== trim( (string) $options['turnstile_secret_key'] );
		$dnsbl        = self::dnsbl_status();
		$query        = self::admin_query();
		$remote       = TTFW_Guestbook_API::configured() ? ( new TTFW_Guestbook_API() )->admin_entries( $query ) : null;
		$entries      = is_array( $remote ) && isset( $remote['entries'] ) && is_array( $remote['entries'] ) ? $remote['entries'] : array();
		$pagination   = is_array( $remote ) && isset( $remote['pagination'] ) && is_array( $remote['pagination'] ) ? $remote['pagination'] : array();
		$site_host    = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		echo '<div class="wrap"><h1>' . esc_html__( 'Tools Guestbook', 'tornevall-tools-for-wordpress' ) . '</h1>';
		self::render_notice();

		echo '<h2>' . esc_html__( 'Connection and public signing', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<p>' . esc_html__( 'Tools stores the guestbook centrally. This WordPress installation can only moderate entries owned by its configured guestbook token. Tokens and Turnstile secrets stay server-side.', 'tornevall-tools-for-wordpress' ) . '</p>';
		echo '<form action="options.php" method="post">';
		settings_fields( TTFW_Guestbook_Settings::SETTINGS_GROUP );
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="ttfw-guestbook-api-url">' . esc_html__( 'Tools guestbook API', 'tornevall-tools-for-wordpress' ) . '</label></th><td>';
		echo '<input id="ttfw-guestbook-api-url" type="url" class="regular-text" name="' . esc_attr( TTFW_Guestbook_Settings::OPTION_NAME . '[api_url]' ) . '" value="' . esc_attr( (string) $options['api_url'] ) . '">';
		echo '<p class="description">' . esc_html__( 'HTTPS endpoint only.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="ttfw-guestbook-token">' . esc_html__( 'Guestbook token', 'tornevall-tools-for-wordpress' ) . '</label></th><td>';
		echo '<input id="ttfw-guestbook-token" type="password" class="regular-text" autocomplete="new-password" name="' . esc_attr( TTFW_Guestbook_Settings::OPTION_NAME . '[token]' ) . '" value="" placeholder="' . esc_attr( $has_token ? __( 'Stored - leave blank to keep unchanged', 'tornevall-tools-for-wordpress' ) : __( 'Paste guestbook token', 'tornevall-tools-for-wordpress' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Use a Tools API key with guestbook.write and guestbook.moderate. Remote moderation is scoped to entries created by this exact token.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="ttfw-turnstile-site-key">' . esc_html__( 'Turnstile site key', 'tornevall-tools-for-wordpress' ) . '</label></th><td>';
		echo '<input id="ttfw-turnstile-site-key" type="text" class="regular-text" name="' . esc_attr( TTFW_Guestbook_Settings::OPTION_NAME . '[turnstile_site_key]' ) . '" value="' . esc_attr( (string) $options['turnstile_site_key'] ) . '">';
		echo '<p class="description">' . esc_html( sprintf( __( 'Create or configure a Cloudflare Turnstile widget that allows hostname %s.', 'tornevall-tools-for-wordpress' ), $site_host ) ) . '</p></td></tr>';
		echo '<tr><th scope="row"><label for="ttfw-turnstile-secret-key">' . esc_html__( 'Turnstile secret key', 'tornevall-tools-for-wordpress' ) . '</label></th><td>';
		echo '<input id="ttfw-turnstile-secret-key" type="password" class="regular-text" autocomplete="new-password" name="' . esc_attr( TTFW_Guestbook_Settings::OPTION_NAME . '[turnstile_secret_key]' ) . '" value="" placeholder="' . esc_attr( $has_turnstile_secret ? __( 'Stored - leave blank to keep unchanged', 'tornevall-tools-for-wordpress' ) : __( 'Paste Turnstile secret key', 'tornevall-tools-for-wordpress' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'The secret is used only by WordPress PHP for Siteverify and is never exposed in HTML or JavaScript.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Save guestbook settings', 'tornevall-tools-for-wordpress' ) );
		echo '</form>';

		self::render_addons( $dnsbl );

		echo '<hr><h2>' . esc_html__( 'Entries owned by this guestbook token', 'tornevall-tools-for-wordpress' ) . '</h2>';
		if ( ! $has_token ) {
			echo '<p>' . esc_html__( 'Configure a guestbook token above to load moderation data.', 'tornevall-tools-for-wordpress' ) . '</p></div>';
			return;
		}
		if ( is_wp_error( $remote ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $remote->get_error_message() ) . '</p></div></div>';
			return;
		}

		self::render_filters();
		self::render_entries( $entries, $dnsbl );
		self::render_pagination( $pagination );
		echo '</div>';
	}

	/**
	 * @return void
	 */
	public static function handle_visibility() {
		self::require_admin_action( 'ttfw_guestbook_visibility' );
		$entry_id   = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$is_visible = isset( $_POST['is_visible'] ) && '1' === (string) wp_unslash( $_POST['is_visible'] );
		$result      = ( new TTFW_Guestbook_API() )->set_visibility( $entry_id, $is_visible );
		self::redirect_with_result( $result, $is_visible ? 'Entry restored.' : 'Entry hidden.' );
	}

	/**
	 * @return void
	 */
	public static function handle_dnsbl_check() {
		self::require_admin_action( 'ttfw_guestbook_dnsbl_check' );
		$ip = self::posted_ip();
		$result = apply_filters(
			'tornevall_dnsbl_check_ip',
			null,
			$ip,
			array( 'consumer' => 'tornevall-tools-for-wordpress', 'feature' => 'guestbook-admin' )
		);

		if ( ! is_array( $result ) || empty( $result['available'] ) ) {
			self::redirect_notice( 'error', __( 'The DNSBL addon is not available.', 'tornevall-tools-for-wordpress' ) );
		}

		$message = ! empty( $result['listed'] )
			? sprintf( __( 'DNSBL: %1$s is listed with bitmask %2$d.', 'tornevall-tools-for-wordpress' ), $ip, (int) ( $result['bitmask'] ?? 0 ) )
			: sprintf( __( 'DNSBL: %s is not currently listed.', 'tornevall-tools-for-wordpress' ), $ip );
		self::redirect_notice( ! empty( $result['ok'] ) ? 'success' : 'error', $message );
	}

	/**
	 * @return void
	 */
	public static function handle_dnsbl_report() {
		self::require_admin_action( 'ttfw_guestbook_dnsbl_report' );
		$ip = self::posted_ip();
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$result = apply_filters(
			'tornevall_dnsbl_report_ip',
			null,
			$ip,
			array(
				'bitmask'     => 64,
				'source_type' => 'wordpress_guestbook',
				'source_name' => 'guestbook_admin',
				'source_note' => 'Guestbook abuse reported from WordPress for central entry #' . $entry_id . '.',
			),
			array( 'consumer' => 'tornevall-tools-for-wordpress', 'feature' => 'guestbook-admin' )
		);

		if ( ! is_array( $result ) || empty( $result['available'] ) ) {
			self::redirect_notice( 'error', __( 'The DNSBL addon is not available.', 'tornevall-tools-for-wordpress' ) );
		}

		self::redirect_notice(
			! empty( $result['ok'] ) ? 'success' : 'error',
			! empty( $result['message'] ) ? sanitize_text_field( (string) $result['message'] ) : __( 'DNSBL report completed.', 'tornevall-tools-for-wordpress' )
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function admin_query() {
		return array(
			'q'          => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : '',
			'visibility' => isset( $_GET['visibility'] ) ? sanitize_key( wp_unslash( (string) $_GET['visibility'] ) ) : 'all',
			'source'     => isset( $_GET['source'] ) ? sanitize_key( wp_unslash( (string) $_GET['source'] ) ) : '',
			'dnsbl'      => isset( $_GET['dnsbl'] ) ? sanitize_key( wp_unslash( (string) $_GET['dnsbl'] ) ) : 'all',
			'page'       => isset( $_GET['remote_page'] ) ? max( 1, absint( $_GET['remote_page'] ) ) : 1,
			'limit'      => 30,
		);
	}

	/**
	 * @return void
	 */
	private static function render_filters() {
		$query = self::admin_query();
		echo '<form method="get" action="' . esc_url( admin_url( 'tools.php' ) ) . '" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin:12px 0 18px">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_SLUG ) . '">';
		echo '<label>' . esc_html__( 'Search', 'tornevall-tools-for-wordpress' ) . '<br><input type="search" name="q" value="' . esc_attr( (string) $query['q'] ) . '"></label>';
		echo '<label>' . esc_html__( 'Visibility', 'tornevall-tools-for-wordpress' ) . '<br><select name="visibility">';
		foreach ( array( 'all' => 'All', 'visible' => 'Visible', 'hidden' => 'Hidden' ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $query['visibility'], $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'DNSBL', 'tornevall-tools-for-wordpress' ) . '<br><select name="dnsbl">';
		foreach ( array( 'all' => 'All', 'unchecked' => 'Unchecked', 'listed' => 'Listed', 'clean' => 'Not listed', 'reported' => 'Reported' ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $query['dnsbl'], $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		submit_button( __( 'Filter', 'tornevall-tools-for-wordpress' ), 'secondary', '', false );
		echo '</form>';
	}

	/**
	 * @param array<int,array<string,mixed>> $entries Entries.
	 * @param array<string,mixed>            $dnsbl DNSBL status.
	 * @return void
	 */
	private static function render_entries( $entries, $dnsbl ) {
		echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Visitor</th><th>Message</th><th>Source</th><th>DNSBL</th><th>Actions</th></tr></thead><tbody>';
		if ( empty( $entries ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No entries match the current filter.', 'tornevall-tools-for-wordpress' ) . '</td></tr>';
		}

		foreach ( $entries as $entry ) {
			$id = absint( $entry['id'] ?? 0 );
			$ip = isset( $entry['remote_addr'] ) ? sanitize_text_field( (string) $entry['remote_addr'] ) : '';
			echo '<tr><td>' . esc_html( (string) $id ) . '</td><td><strong>' . esc_html( (string) ( $entry['name'] ?? '' ) ) . '</strong>';
			if ( ! empty( $entry['email'] ) ) {
				echo '<br><small>' . esc_html( (string) $entry['email'] ) . '</small>';
			}
			if ( '' !== $ip ) {
				echo '<br><code>' . esc_html( $ip ) . '</code>';
			}
			echo '</td><td style="max-width:520px;white-space:pre-wrap">' . esc_html( (string) ( $entry['message'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $entry['source'] ?? '' ) );
			if ( ! empty( $entry['source_site_host'] ) ) {
				echo '<br><small>' . esc_html( (string) $entry['source_site_host'] ) . '</small>';
			}
			echo '</td><td>';
			if ( empty( $entry['dnsbl_checked_at'] ) ) {
				echo esc_html__( 'Unchecked', 'tornevall-tools-for-wordpress' );
			} elseif ( ! empty( $entry['dnsbl_listed'] ) ) {
				echo esc_html__( 'Listed', 'tornevall-tools-for-wordpress' ) . ' (' . esc_html( (string) ( $entry['dnsbl_bitmask'] ?? 0 ) ) . ')';
			} else {
				echo esc_html__( 'Not listed', 'tornevall-tools-for-wordpress' );
			}
			echo '</td><td>';
			self::action_form( 'ttfw_guestbook_visibility', 'ttfw_guestbook_visibility', $id, $ip, ! empty( $entry['is_visible'] ) ? '0' : '1', ! empty( $entry['is_visible'] ) ? __( 'Hide', 'tornevall-tools-for-wordpress' ) : __( 'Restore', 'tornevall-tools-for-wordpress' ) );
			if ( '' !== $ip && ! empty( $dnsbl['can_check'] ) ) {
				self::action_form( 'ttfw_guestbook_dnsbl_check', 'ttfw_guestbook_dnsbl_check', $id, $ip, '', __( 'Check DNSBL', 'tornevall-tools-for-wordpress' ) );
			}
			if ( '' !== $ip && ! empty( $dnsbl['can_report'] ) ) {
				self::action_form( 'ttfw_guestbook_dnsbl_report', 'ttfw_guestbook_dnsbl_report', $id, $ip, '', __( 'Report abuse', 'tornevall-tools-for-wordpress' ), true );
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function dnsbl_status() {
		$capabilities = apply_filters(
			'tornevall_dnsbl_capabilities',
			null,
			array( 'consumer' => 'tornevall-tools-for-wordpress', 'feature' => 'guestbook-admin' )
		);
		$active = is_array( $capabilities ) && ! empty( $capabilities['installed'] );
		return array(
			'active'       => $active,
			'can_check'    => $active && ! empty( $capabilities['can_check'] ),
			'can_report'   => $active && ! empty( $capabilities['can_report'] ),
			'capabilities' => is_array( $capabilities ) ? $capabilities : array(),
			'plugin_file'  => self::find_dnsbl_plugin_file(),
		);
	}

	/**
	 * @param array<string,mixed> $dnsbl Status.
	 * @return void
	 */
	private static function render_addons( $dnsbl ) {
		echo '<hr><h2>' . esc_html__( 'Recommended add-ons', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<div style="border:1px solid #c3c4c7;background:#fff;padding:16px;max-width:900px"><h3 style="margin-top:0">Tornevall Networks DNSBL Implementation</h3>';
		echo '<p>' . esc_html__( 'Optional DNSBL/FraudBL protection. DNSBL checks and blacklist publication do not exist in this plugin unless the DNSBL addon is installed, active and permitted by its own token.', 'tornevall-tools-for-wordpress' ) . '</p>';

		if ( ! empty( $dnsbl['active'] ) ) {
			echo '<p><strong>' . esc_html__( 'Active.', 'tornevall-tools-for-wordpress' ) . '</strong> ';
			echo ! empty( $dnsbl['can_report'] )
				? esc_html__( 'The configured DNSBL token can publish abuse reports and their source TXT metadata.', 'tornevall-tools-for-wordpress' )
				: esc_html__( 'DNSBL checks may be available; blacklist reporting requires add permission on the DNSBL token.', 'tornevall-tools-for-wordpress' );
			echo '</p>';
		} elseif ( ! empty( $dnsbl['plugin_file'] ) && current_user_can( 'activate_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( (string) $dnsbl['plugin_file'] ) ), 'activate-plugin_' . $dnsbl['plugin_file'] );
			echo '<p><a class="button button-secondary" href="' . esc_url( $url ) . '">' . esc_html__( 'Activate DNSBL add-on', 'tornevall-tools-for-wordpress' ) . '</a></p>';
		} elseif ( current_user_can( 'install_plugins' ) ) {
			$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . self::DNSBL_PLUGIN_SLUG ), 'install-plugin_' . self::DNSBL_PLUGIN_SLUG );
			echo '<p><a class="button button-secondary" href="' . esc_url( $url ) . '">' . esc_html__( 'Install DNSBL add-on', 'tornevall-tools-for-wordpress' ) . '</a></p>';
		} else {
			echo '<p>' . esc_html__( 'DNSBL is optional and is not active on this site.', 'tornevall-tools-for-wordpress' ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * @return string
	 */
	private static function find_dnsbl_plugin_file() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $file => $data ) {
			$name = isset( $data['Name'] ) ? strtolower( (string) $data['Name'] ) : '';
			if ( false !== strpos( $file, 'tornevall-wp-dnsbl.php' ) || false !== strpos( $name, 'tornevall networks dnsbl' ) ) {
				return (string) $file;
			}
		}
		return '';
	}

	/**
	 * @param string $action Action.
	 * @param string $nonce_action Nonce action.
	 * @param int    $entry_id Entry id.
	 * @param string $ip Source IP.
	 * @param string $visibility Visibility value.
	 * @param string $label Button label.
	 * @param bool   $confirm Whether to add explicit confirmation.
	 * @return void
	 */
	private static function action_form( $action, $nonce_action, $entry_id, $ip, $visibility, $label, $confirm = false ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin:2px"' . ( $confirm ? ' onsubmit="return confirm(\'' . esc_js( __( 'Explicitly publish this source IP as guestbook/web abuse to DNSBL?', 'tornevall-tools-for-wordpress' ) ) . '\');"' : '' ) . '>';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '"><input type="hidden" name="entry_id" value="' . esc_attr( (string) $entry_id ) . '"><input type="hidden" name="ip" value="' . esc_attr( $ip ) . '">';
		if ( '' !== $visibility ) {
			echo '<input type="hidden" name="is_visible" value="' . esc_attr( $visibility ) . '">';
		}
		wp_nonce_field( $nonce_action );
		echo '<button type="submit" class="button button-small">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * @param array<string,mixed> $pagination Pagination data.
	 * @return void
	 */
	private static function render_pagination( $pagination ) {
		$current = max( 1, absint( $pagination['current_page'] ?? 1 ) );
		$last    = max( 1, absint( $pagination['last_page'] ?? 1 ) );
		if ( $last <= 1 ) {
			return;
		}
		echo '<p class="tablenav-pages">';
		if ( $current > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( 'remote_page', $current - 1 ) ) . '">&larr; ' . esc_html__( 'Previous', 'tornevall-tools-for-wordpress' ) . '</a> ';
		}
		echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'tornevall-tools-for-wordpress' ), $current, $last ) );
		if ( $current < $last ) {
			echo ' <a class="button" href="' . esc_url( add_query_arg( 'remote_page', $current + 1 ) ) . '">' . esc_html__( 'Next', 'tornevall-tools-for-wordpress' ) . ' &rarr;</a>';
		}
		echo '</p>';
	}

	/**
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private static function require_admin_action( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage the guestbook.', 'tornevall-tools-for-wordpress' ), 403 );
		}
		check_admin_referer( $nonce_action );
	}

	/**
	 * @return string
	 */
	private static function posted_ip() {
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ip'] ) ) : '';
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			self::redirect_notice( 'error', __( 'The entry does not contain a valid source IP.', 'tornevall-tools-for-wordpress' ) );
		}
		return $ip;
	}

	/**
	 * @param array<string,mixed>|WP_Error $result Result.
	 * @param string                       $success_message Success message.
	 * @return void
	 */
	private static function redirect_with_result( $result, $success_message ) {
		if ( is_wp_error( $result ) ) {
			self::redirect_notice( 'error', $result->get_error_message() );
		}
		self::redirect_notice( 'success', $success_message );
	}

	/**
	 * @param string $type Notice type.
	 * @param string $message Notice message.
	 * @return void
	 */
	private static function redirect_notice( $type, $message ) {
		$url = add_query_arg(
			array(
				'page'                => self::PAGE_SLUG,
				'ttfw_guestbook_type' => sanitize_key( $type ),
				'ttfw_guestbook_note' => rawurlencode( sanitize_text_field( (string) $message ) ),
			),
			admin_url( 'tools.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @return void
	 */
	private static function render_notice() {
		if ( empty( $_GET['ttfw_guestbook_note'] ) ) {
			return;
		}
		$type    = isset( $_GET['ttfw_guestbook_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['ttfw_guestbook_type'] ) ) : 'success';
		$message = sanitize_text_field( rawurldecode( wp_unslash( (string) $_GET['ttfw_guestbook_note'] ) ) );
		$class   = 'error' === $type ? 'notice-error' : 'notice-success';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}
