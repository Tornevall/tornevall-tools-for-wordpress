<?php
/**
 * Main plugin bootstrap.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrap class.
 */
class TTFW_Plugin {
	/**
	 * Boots the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		TTFW_Settings::init();

		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'rest_api_init', array( 'TTFW_REST_Controller', 'register_routes' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
	}

	/**
	 * Loads translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'tornevall-tools-for-wordpress', false, dirname( plugin_basename( TTFW_FILE ) ) . '/languages' );
	}

	/**
	 * Enqueues block editor assets.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		$options = TTFW_Settings::get_options();

		wp_enqueue_script(
			'ttfw-editor',
			TTFW_URL . 'assets/editor.js',
			array( 'wp-api-fetch', 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
			TTFW_VERSION,
			true
		);

		wp_enqueue_style( 'ttfw-editor', TTFW_URL . 'assets/editor.css', array(), TTFW_VERSION );

		wp_localize_script(
			'ttfw-editor',
			'TTFWAI',
			array(
				'endpoint'         => '/ttfw/v1/ai/respond',
				'settingsUrl'      => admin_url( 'options-general.php?page=' . TTFW_Settings::PAGE_SLUG ),
				'defaultProvider'  => (string) $options['default_provider'],
				'defaultPersona'   => (string) $options['default_persona'],
				'openaiModel'      => (string) $options['openai_model'],
				'toolsModel'       => (string) $options['tools_model'],
				'responseLanguage' => (string) $options['response_language'],
			)
		);
	}
}
