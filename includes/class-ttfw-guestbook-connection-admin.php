<?php
/**
 * Admin connection manager for selecting or creating a Tools guestbook.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTFW_Guestbook_Connection_Admin {
	const PAGE_SLUG = 'tornevall-tools-guestbook-connection';
	const NOTICE_PREFIX = 'ttfw_guestbook_connection_notice_';

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_post_ttfw_guestbook_select_book', array( __CLASS__, 'handle_select_book' ) );
		add_action( 'admin_post_ttfw_guestbook_create_book', array( __CLASS__, 'handle_create_book' ) );
	}

	/**
	 * @return void
	 */
	public static function add_page() {
		add_submenu_page(
			TTFW_Settings::PAGE_SLUG,
			esc_html__( 'Guestbook connection', 'tornevall-tools-for-wordpress' ),
			esc_html__( 'Guestbook connection', 'tornevall-tools-for-wordpress' ),
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

		echo '<div class="wrap"><h1>' . esc_html__( 'Guestbook connection', 'tornevall-tools-for-wordpress' ) . '</h1>';
		echo '<p>' . esc_html__( 'Bind this WordPress site to one guestbook owned by the Tools user behind the configured server-side token.', 'tornevall-tools-for-wordpress' ) . '</p>';
		self::render_notice();

		if ( ! TTFW_Guestbook_API::configured() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Configure a Tools guestbook token before selecting a guestbook.', 'tornevall-tools-for-wordpress' ) . '</p></div>';
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'tools.php?page=' . TTFW_Guestbook_Admin::PAGE_SLUG ) ) . '">' . esc_html__( 'Configure guestbook token', 'tornevall-tools-for-wordpress' ) . '</a></p></div>';
			return;
		}

		$catalog = ( new TTFW_Guestbook_API() )->owned_books();
		if ( is_wp_error( $catalog ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $catalog->get_error_message() ) . '</p></div></div>';
			return;
		}

		$books = isset( $catalog['books'] ) && is_array( $catalog['books'] ) ? $catalog['books'] : array();
		$can_create = ! empty( $catalog['can_create'] );
		$selected_id = TTFW_Guestbook_Settings::guestbook_id();

		echo '<h2>' . esc_html__( 'Select guestbook', 'tornevall-tools-for-wordpress' ) . '</h2>';
		if ( empty( $books ) ) {
			echo '<p>' . esc_html__( 'This Tools user does not own any guestbooks yet.', 'tornevall-tools-for-wordpress' ) . '</p>';
		} else {
			echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
		echo '<input type="hidden" name="action" value="ttfw_guestbook_select_book">';
		wp_nonce_field( 'ttfw_guestbook_select_book' );
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Use', 'tornevall-tools-for-wordpress' ) . '</th><th>' . esc_html__( 'Guestbook', 'tornevall-tools-for-wordpress' ) . '</th><th>' . esc_html__( 'Site context', 'tornevall-tools-for-wordpress' ) . '</th><th>' . esc_html__( 'Status', 'tornevall-tools-for-wordpress' ) . '</th></tr></thead><tbody>';

		foreach ( $books as $book ) {
			$id = absint( $book['id'] ?? 0 );
			if ( $id < 1 ) {
				continue;
			}
			$name = sanitize_text_field( (string) ( $book['name'] ?? '' ) );
			$slug = sanitize_key( (string) ( $book['slug'] ?? '' ) );
			$site_url = esc_url( (string) ( $book['site_url'] ?? '' ) );
			$site_language = sanitize_text_field( (string) ( $book['site_language'] ?? '' ) );
			$site_description = sanitize_textarea_field( (string) ( $book['site_description'] ?? '' ) );
			$is_selected = $selected_id === $id;

			echo '<tr><td><label><input type="radio" name="guestbook_id" value="' . esc_attr( (string) $id ) . '" ' . checked( $is_selected, true, false ) . '> ' . esc_html__( 'Select', 'tornevall-tools-for-wordpress' ) . '</label></td>';
			echo '<td><strong>' . esc_html( $name ) . '</strong><br><code>' . esc_html( $slug ) . '</code><br><small>#' . esc_html( (string) $id ) . '</small></td>';
			echo '<td>';
			if ( '' !== $site_url ) {
				echo '<a href="' . esc_url( $site_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $site_url ) . '</a><br>';
			}
			if ( '' !== $site_language ) {
				echo '<small>' . esc_html__( 'Language:', 'tornevall-tools-for-wordpress' ) . ' ' . esc_html( $site_language ) . '</small><br>';
			}
			if ( '' !== $site_description ) {
				echo '<small>' . esc_html( TTFW_Settings::limit_string( $site_description, 240 ) ) . '</small>';
			}
			echo '</td>';
			echo '<td>' . esc_html( ! empty( $book['is_active'] ) ? __( 'Active', 'tornevall-tools-for-wordpress' ) : __( 'Inactive', 'tornevall-tools-for-wordpress' ) ) . ' / ' . esc_html( ! empty( $book['is_hosted'] ) ? __( 'Hosted', 'tornevall-tools-for-wordpress' ) : __( 'Not hosted', 'tornevall-tools-for-wordpress' ) ) . '</td></tr>';
		}

			echo '</tbody></table>';
			submit_button( __( 'Use selected guestbook', 'tornevall-tools-for-wordpress' ) );
			echo '</form>';
		}

		echo '<hr><h2>' . esc_html__( 'Create guestbook in Tools', 'tornevall-tools-for-wordpress' ) . '</h2>';
		if ( ! $can_create ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'This token can list guestbooks, but remote creation requires both guestbook.write and guestbook.moderate.', 'tornevall-tools-for-wordpress' ) . '</p></div>';
			echo '</div>';
			return;
		}

		$site_name = sanitize_text_field( (string) get_bloginfo( 'name' ) );
		$site_tagline = sanitize_text_field( (string) get_bloginfo( 'description' ) );
		$context = trim( $site_name . ( '' !== $site_tagline ? ' - ' . $site_tagline : '' ) );
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$default_slug = sanitize_title( str_replace( '.', '-', $host ) . '-guestbook' );
		if ( '' === $default_slug ) {
			$default_slug = 'wordpress-guestbook';
		}

		echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
		echo '<input type="hidden" name="action" value="ttfw_guestbook_create_book">';
		wp_nonce_field( 'ttfw_guestbook_create_book' );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text_row( 'name', __( 'Name', 'tornevall-tools-for-wordpress' ), '' !== $site_name ? $site_name . ' Guestbook' : 'WordPress Guestbook', 191 );
		self::text_row( 'slug', __( 'Slug', 'tornevall-tools-for-wordpress' ), $default_slug, 191 );
		echo '<tr><th scope="row"><label for="ttfw-create-guestbook-theme">' . esc_html__( 'Theme', 'tornevall-tools-for-wordpress' ) . '</label></th><td><select id="ttfw-create-guestbook-theme" name="theme">';
		foreach ( array( 'tools', 'miazma', 'terminal' ) as $theme ) {
			echo '<option value="' . esc_attr( $theme ) . '">' . esc_html( ucfirst( $theme ) ) . '</option>';
		}
		echo '</select></td></tr>';
		self::text_row( 'site_url', __( 'Site URL', 'tornevall-tools-for-wordpress' ), home_url( '/' ), 2048, 'url' );
		self::text_row( 'site_language', __( 'Site language', 'tornevall-tools-for-wordpress' ), self::normalized_locale(), 35 );
		echo '<tr><th scope="row"><label for="ttfw-create-guestbook-description">' . esc_html__( 'Site description', 'tornevall-tools-for-wordpress' ) . '</label></th><td><textarea id="ttfw-create-guestbook-description" class="large-text" rows="4" maxlength="5000" name="site_description">' . esc_textarea( $context ) . '</textarea><p class="description">' . esc_html__( 'Describe the site and the kind of guestbook comments that are normal. This is context only and does not enable AI or automatic moderation.', 'tornevall-tools-for-wordpress' ) . '</p></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Publishing', 'tornevall-tools-for-wordpress' ) . '</th><td><label><input type="checkbox" name="is_active" value="1" checked> ' . esc_html__( 'Active', 'tornevall-tools-for-wordpress' ) . '</label><br><label><input type="checkbox" name="is_hosted" value="1" checked> ' . esc_html__( 'Publicly hosted by Tools', 'tornevall-tools-for-wordpress' ) . '</label></td></tr>';
		echo '</tbody></table>';
		submit_button( __( 'Create and select guestbook', 'tornevall-tools-for-wordpress' ) );
		echo '</form></div>';
	}

	/**
	 * @return void
	 */
	public static function handle_select_book() {
		self::require_admin_action( 'ttfw_guestbook_select_book' );
		$requested_id = isset( $_POST['guestbook_id'] ) ? absint( $_POST['guestbook_id'] ) : 0;
		if ( $requested_id < 1 ) {
			self::redirect_notice( 'error', __( 'Select a guestbook first.', 'tornevall-tools-for-wordpress' ) );
		}

		$catalog = ( new TTFW_Guestbook_API() )->owned_books();
		if ( is_wp_error( $catalog ) ) {
			self::redirect_notice( 'error', $catalog->get_error_message() );
		}

		$books = isset( $catalog['books'] ) && is_array( $catalog['books'] ) ? $catalog['books'] : array();
		foreach ( $books as $book ) {
			if ( absint( $book['id'] ?? 0 ) !== $requested_id ) {
				continue;
			}

			TTFW_Guestbook_Settings::set_selected_guestbook(
				$requested_id,
				sanitize_key( (string) ( $book['slug'] ?? '' ) )
			);
			self::redirect_notice( 'success', __( 'The WordPress site is now bound to the selected Tools guestbook.', 'tornevall-tools-for-wordpress' ) );
		}

		self::redirect_notice( 'error', __( 'The selected guestbook is not owned by the configured Tools user.', 'tornevall-tools-for-wordpress' ) );
	}

	/**
	 * @return void
	 */
	public static function handle_create_book() {
		self::require_admin_action( 'ttfw_guestbook_create_book' );

		$name = TTFW_Settings::limit_string( sanitize_text_field( self::post_value( 'name' ) ), 191 );
		$slug = sanitize_title( self::post_value( 'slug' ) );
		$theme = sanitize_key( self::post_value( 'theme' ) );
		$theme = in_array( $theme, array( 'tools', 'miazma', 'terminal' ), true ) ? $theme : 'tools';
		$site_url = esc_url_raw( self::post_value( 'site_url' ) );
		$site_description = TTFW_Settings::sanitize_long_text( self::post_value( 'site_description' ), 5000 );
		$site_language = self::normalize_language( self::post_value( 'site_language' ) );

		if ( strlen( trim( $name ) ) < 2 || '' === $slug ) {
			self::redirect_notice( 'error', __( 'Guestbook name and slug are required.', 'tornevall-tools-for-wordpress' ) );
		}
		if ( '' !== $site_url && ( ! wp_http_validate_url( $site_url ) || ! in_array( strtolower( (string) wp_parse_url( $site_url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) ) {
			self::redirect_notice( 'error', __( 'The site URL must use HTTP or HTTPS.', 'tornevall-tools-for-wordpress' ) );
		}

		$result = ( new TTFW_Guestbook_API() )->create_owned_book(
			array(
				'name'             => $name,
				'slug'             => $slug,
				'theme'            => $theme,
				'site_url'         => $site_url,
				'site_description' => $site_description,
				'site_language'    => $site_language,
				'is_active'        => isset( $_POST['is_active'] ) && '1' === (string) wp_unslash( $_POST['is_active'] ),
				'is_hosted'        => isset( $_POST['is_hosted'] ) && '1' === (string) wp_unslash( $_POST['is_hosted'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			self::redirect_notice( 'error', $result->get_error_message() );
		}

		$book = isset( $result['book'] ) && is_array( $result['book'] ) ? $result['book'] : array();
		$id = absint( $book['id'] ?? 0 );
		$created_slug = sanitize_key( (string) ( $book['slug'] ?? '' ) );
		if ( $id < 1 || '' === $created_slug ) {
			self::redirect_notice( 'error', __( 'Tools created the guestbook but did not return a usable guestbook identity.', 'tornevall-tools-for-wordpress' ) );
		}

		TTFW_Guestbook_Settings::set_selected_guestbook( $id, $created_slug );
		self::redirect_notice( 'success', __( 'The guestbook was created in Tools and selected for this WordPress site.', 'tornevall-tools-for-wordpress' ) );
	}

	/**
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private static function require_admin_action( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this guestbook connection.', 'tornevall-tools-for-wordpress' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * @param string $type Notice type.
	 * @param string $message Notice text.
	 * @return void
	 */
	private static function redirect_notice( $type, $message ) {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			set_transient(
				self::NOTICE_PREFIX . $user_id,
				array(
					'type'    => 'success' === $type ? 'success' : 'error',
					'message' => TTFW_Settings::limit_string( sanitize_text_field( (string) $message ), 500 ),
				),
				MINUTE_IN_SECONDS
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * @return void
	 */
	private static function render_notice() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return;
		}

		$key = self::NOTICE_PREFIX . $user_id;
		$notice = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}

		$class = 'success' === ( $notice['type'] ?? '' ) ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . esc_attr( $class ) . ' inline"><p>' . esc_html( (string) $notice['message'] ) . '</p></div>';
	}

	/**
	 * @param string $name Input name.
	 * @param string $label Input label.
	 * @param string $value Input value.
	 * @param int    $max_length Maximum length.
	 * @param string $type Input type.
	 * @return void
	 */
	private static function text_row( $name, $label, $value, $max_length, $type = 'text' ) {
		$id = 'ttfw-create-guestbook-' . sanitize_key( $name );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" class="regular-text" name="' . esc_attr( $name ) . '" maxlength="' . esc_attr( (string) $max_length ) . '" value="' . esc_attr( (string) $value ) . '"></td></tr>';
	}

	/**
	 * @param string $name POST field.
	 * @return string
	 */
	private static function post_value( $name ) {
		return isset( $_POST[ $name ] ) ? wp_unslash( (string) $_POST[ $name ] ) : '';
	}

	/**
	 * @return string
	 */
	private static function normalized_locale() {
		return self::normalize_language( (string) get_locale() );
	}

	/**
	 * @param string $value Language value.
	 * @return string
	 */
	private static function normalize_language( $value ) {
		$value = strtolower( str_replace( '_', '-', trim( sanitize_text_field( (string) $value ) ) ) );
		return preg_match( '/^[a-z]{2,8}(?:-[a-z0-9]{1,8})*$/', $value ) ? $value : '';
	}
}
