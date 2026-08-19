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
		TTFW_Dynamic_DNS_Module::init();

		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
	}

	/**
	 * Runs activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( TTFW_Settings::OPTION_NAME, false ) ) {
			add_option( TTFW_Settings::OPTION_NAME, TTFW_Settings::defaults(), '', false );
		}

		TTFW_Dynamic_DNS_Module::activate();
	}

	/**
	 * Runs deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate() {
		TTFW_Dynamic_DNS_Module::deactivate();
	}

	/**
	 * Loads translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'tornevall-tools-for-wordpress', false, dirname( plugin_basename( TTFW_FILE ) ) . '/languages' );
	}
}
