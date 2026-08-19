<?php
/**
 * Admin settings.
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
	public const PAGE_SLUG   = 'tornevall-tools-ai';

	/**
	 * Returns default options.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'default_provider'  => 'tools',
			'default_persona'   => 'You are a precise editorial assistant inside WordPress. Help the editor write, rewrite, summarize, clean up, and improve post content. Keep factual uncertainty visible. Return clean Markdown that can be converted to WordPress blocks unless the user asks for another format.',
			'openai_token'      => '',
			'openai_model'      => 'gpt-4o-mini',
			'tools_token'       => '',
			'tools_api_url'     => 'https://tools.tornevall.net/api/ai/internal/respond',
			'tools_client_slug' => 'tornevall_tools_wordpress',
			'tools_model'       => '',
			'response_language' => 'auto',
			'timeout'           => 60,
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
		add_action( 'admin_post_ttfw_test_provider', array( __CLASS__, 'handle_provider_test' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TTFW_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Adds plugin action links.
	 *
	 * @param array<int,string> $links Existing links.
	 * @return array<int,string>
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'tornevall-tools-for-wordpress' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds admin page.
	 *
	 * @return void
	 */
	public static function add_page() {
		add_options_page(
			esc_html__( 'Tornevall Tools AI', 'tornevall-tools-for-wordpress' ),
			esc_html__( 'Tornevall Tools AI', 'tornevall-tools-for-wordpress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Registers settings.
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
	 * Handles token test requests from wp-admin.
	 *
	 * @return void
	 */
	public static function handle_provider_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to test provider tokens.', 'tornevall-tools-for-wordpress' ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		if ( ! in_array( $provider, array( 'tools', 'openai' ), true ) ) {
			wp_die( esc_html__( 'Invalid provider.', 'tornevall-tools-for-wordpress' ) );
		}

		check_admin_referer( 'ttfw_test_provider_' . $provider );

		$result  = ( new TTFW_AI_Service() )->test_provider( $provider );
		$message = array(
			'ok'       => ! is_wp_error( $result ),
			'provider' => $provider,
			'message'  => is_wp_error( $result ) ? $result->get_error_message() : (string) $result['message'],
		);

		set_transient( self::test_transient_key( $provider ), $message, 60 );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'ttfw_test' => $provider ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Renders settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options          = self::get_options();
		$has_openai_token = '' !== (string) $options['openai_token'];
		$has_tools_token  = '' !== (string) $options['tools_token'];

		echo '<div class="wrap"><h1>' . esc_html__( 'Tornevall Tools AI', 'tornevall-tools-for-wordpress' ) . '</h1>';
		self::render_test_notice();
		echo '<p>' . esc_html__( 'Configure server-side credentials and defaults for the block editor AI assistant. Tokens are never sent to the browser.', 'tornevall-tools-for-wordpress' ) . '</p>';
		echo '<form action="options.php" method="post">';
		settings_fields( 'ttfw_settings_group' );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::row_select( 'default_provider', __( 'Default provider', 'tornevall-tools-for-wordpress' ), $options['default_provider'], array( 'tools' => __( 'Tornevall Tools AI', 'tornevall-tools-for-wordpress' ), 'openai' => __( 'OpenAI direct', 'tornevall-tools-for-wordpress' ) ) );
		self::row_textarea( 'default_persona', __( 'Default persona', 'tornevall-tools-for-wordpress' ), $options['default_persona'], __( 'Used as server-side default persona for both providers. The editor can still provide an override per request.', 'tornevall-tools-for-wordpress' ) );
		self::row_secret( 'openai_token', __( 'OpenAI API token', 'tornevall-tools-for-wordpress' ), $has_openai_token, __( 'Direct OpenAI API key. Leave blank to keep the stored token.', 'tornevall-tools-for-wordpress' ) );
		self::row_text( 'openai_model', __( 'OpenAI model', 'tornevall-tools-for-wordpress' ), $options['openai_model'], __( 'Model used for direct OpenAI requests.', 'tornevall-tools-for-wordpress' ) );
		self::row_secret( 'tools_token', __( 'Tools AI token', 'tornevall-tools-for-wordpress' ), $has_tools_token, __( 'Bearer token for Tools AI. The internal endpoint requires the correct AI scope.', 'tornevall-tools-for-wordpress' ) );
		self::row_text( 'tools_api_url', __( 'Tools AI endpoint', 'tornevall-tools-for-wordpress' ), $options['tools_api_url'], __( 'Default: https://tools.tornevall.net/api/ai/internal/respond', 'tornevall-tools-for-wordpress' ) );
		self::row_text( 'tools_client_slug', __( 'Tools client slug', 'tornevall-tools-for-wordpress' ), $options['tools_client_slug'], __( 'Stable Tools-side client identifier for auditing and defaults.', 'tornevall-tools-for-wordpress' ) );
		self::row_text( 'tools_model', __( 'Tools model override', 'tornevall-tools-for-wordpress' ), $options['tools_model'], __( 'Optional. Leave blank to use the Tools-side default for the client slug.', 'tornevall-tools-for-wordpress' ) );
		self::row_select( 'response_language', __( 'Response language', 'tornevall-tools-for-wordpress' ), $options['response_language'], array( 'auto' => 'Auto', 'sv' => 'Swedish', 'en' => 'English', 'da' => 'Danish', 'no' => 'Norwegian', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish' ) );
		self::row_number( 'timeout', __( 'HTTP timeout', 'tornevall-tools-for-wordpress' ), (int) $options['timeout'] );
		echo '</tbody></table>';
		submit_button();
		echo '</form>';
		self::render_provider_test_panel();
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
		$output   = $current;
		$input    = is_array( $input ) ? $input : array();

		$provider = sanitize_key( self::scalar( $input, 'default_provider', $defaults['default_provider'] ) );
		$output['default_provider'] = in_array( $provider, array( 'tools', 'openai' ), true ) ? $provider : $defaults['default_provider'];
		$output['default_persona']  = self::sanitize_long_text( self::scalar( $input, 'default_persona', $defaults['default_persona'] ), 6000 );
		$output['openai_token']     = self::sanitize_secret( $input, 'openai_token', $current['openai_token'] );
		$output['tools_token']      = self::sanitize_secret( $input, 'tools_token', $current['tools_token'] );
		$output['openai_model']     = self::sanitize_identifier( self::scalar( $input, 'openai_model', $defaults['openai_model'] ), 80 );
		$output['tools_model']      = self::sanitize_identifier( self::scalar( $input, 'tools_model', '' ), 80 );

		$url = esc_url_raw( self::scalar( $input, 'tools_api_url', $defaults['tools_api_url'] ) );
		$output['tools_api_url']    = self::is_https_url( $url ) ? $url : $defaults['tools_api_url'];
		$output['tools_client_slug'] = self::sanitize_slug( self::scalar( $input, 'tools_client_slug', $defaults['tools_client_slug'] ) );

		$language = sanitize_key( self::scalar( $input, 'response_language', $defaults['response_language'] ) );
		$output['response_language'] = in_array( $language, array( 'auto', 'sv', 'en', 'da', 'no', 'de', 'fr', 'es' ), true ) ? $language : $defaults['response_language'];
		$output['timeout']           = max( 5, min( 120, absint( self::scalar( $input, 'timeout', $defaults['timeout'] ) ) ) );

		return $output;
	}

	/**
	 * Sanitizes longer textarea content.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	public static function sanitize_long_text( $value, $max_length ) {
		return self::limit_string( sanitize_textarea_field( (string) $value ), $max_length );
	}

	/**
	 * Sanitizes provider model identifiers.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max_length Maximum length.
	 * @return string
	 */
	public static function sanitize_identifier( $value, $max_length = 120 ) {
		$value = preg_replace( '/[^A-Za-z0-9_.:\/-]/', '', sanitize_text_field( (string) $value ) );
		return self::limit_string( (string) $value, $max_length );
	}

	/**
	 * Sanitizes a Tools client slug.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_slug( $value ) {
		$value = strtolower( self::sanitize_identifier( $value, 80 ) );
		$value = trim( (string) preg_replace( '/[^a-z0-9_\-]/', '_', $value ), '_-' );
		return '' === $value ? self::defaults()['tools_client_slug'] : $value;
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

	private static function render_test_notice() {
		$provider = isset( $_GET['ttfw_test'] ) ? sanitize_key( wp_unslash( $_GET['ttfw_test'] ) ) : '';
		if ( ! in_array( $provider, array( 'tools', 'openai' ), true ) ) {
			return;
		}

		$result = get_transient( self::test_transient_key( $provider ) );
		delete_transient( self::test_transient_key( $provider ) );

		if ( ! is_array( $result ) || ! isset( $result['message'] ) ) {
			return;
		}

		$class = ! empty( $result['ok'] ) ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( (string) $result['message'] ) . '</p></div>';
	}

	private static function render_provider_test_panel() {
		echo '<hr />';
		echo '<h2>' . esc_html__( 'Test provider tokens', 'tornevall-tools-for-wordpress' ) . '</h2>';
		echo '<p>' . esc_html__( 'These tests use the currently saved settings. Save changes before testing new tokens.', 'tornevall-tools-for-wordpress' ) . '</p>';
		echo '<div class="ttfw-admin-test-actions" style="display:flex;gap:12px;flex-wrap:wrap;">';
		self::render_provider_test_form( 'tools', __( 'Test Tools AI token', 'tornevall-tools-for-wordpress' ) );
		self::render_provider_test_form( 'openai', __( 'Test OpenAI token', 'tornevall-tools-for-wordpress' ) );
		echo '</div>';
	}

	private static function render_provider_test_form( $provider, $label ) {
		echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
		echo '<input type="hidden" name="action" value="ttfw_test_provider" />';
		echo '<input type="hidden" name="provider" value="' . esc_attr( $provider ) . '" />';
		wp_nonce_field( 'ttfw_test_provider_' . $provider );
		submit_button( $label, 'secondary', 'submit', false );
		echo '</form>';
	}

	private static function test_transient_key( $provider ) {
		return 'ttfw_test_' . get_current_user_id() . '_' . sanitize_key( $provider );
	}

	private static function sanitize_secret( $input, $key, $current ) {
		if ( ! array_key_exists( $key, $input ) ) {
			return (string) $current;
		}
		$value = trim( sanitize_text_field( self::scalar( $input, $key, '' ) ) );
		return '' === $value ? (string) $current : self::limit_string( $value, 4000 );
	}

	private static function scalar( $input, $key, $default ) {
		if ( ! is_array( $input ) || ! array_key_exists( $key, $input ) || is_array( $input[ $key ] ) || is_object( $input[ $key ] ) ) {
			return (string) $default;
		}
		return (string) wp_unslash( $input[ $key ] );
	}

	private static function is_https_url( $url ) {
		$parts = wp_parse_url( $url );
		return is_array( $parts ) && isset( $parts['scheme'], $parts['host'] ) && 'https' === strtolower( (string) $parts['scheme'] );
	}

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

	private static function row_textarea( $key, $label, $value, $description ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><textarea id="' . esc_attr( $key ) . '" rows="8" class="large-text code" name="' . esc_attr( self::field_name( $key ) ) . '">' . esc_textarea( $value ) . '</textarea><p class="description">' . esc_html( $description ) . '</p></td></tr>';
	}

	private static function row_select( $key, $label, $value, $choices ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><select id="' . esc_attr( $key ) . '" name="' . esc_attr( self::field_name( $key ) ) . '">';
		foreach ( $choices as $choice_value => $choice_label ) {
			echo '<option value="' . esc_attr( $choice_value ) . '" ' . selected( $value, $choice_value, false ) . '>' . esc_html( $choice_label ) . '</option>';
		}
		echo '</select></td></tr>';
	}

	private static function row_number( $key, $label, $value ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $key ) . '" type="number" min="5" max="120" class="small-text" name="' . esc_attr( self::field_name( $key ) ) . '" value="' . esc_attr( (string) $value ) . '" /> ' . esc_html__( 'seconds', 'tornevall-tools-for-wordpress' ) . '</td></tr>';
	}
}
