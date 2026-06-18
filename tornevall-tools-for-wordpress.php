<?php
/**
 * Plugin Name: Tornevall Tools for WordPress
 * Plugin URI: https://github.com/Tornevall/tornevall-tools-for-wordpress
 * Description: Adds Tornevall Networks Tools AI and direct OpenAI connectors to the WordPress block editor.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Tornevall Networks
 * Author URI: https://www.tornevalls.se
 * Text Domain: tornevall-tools-for-wordpress
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TTFW_VERSION', '0.1.0' );
define( 'TTFW_FILE', __FILE__ );
define( 'TTFW_PATH', plugin_dir_path( __FILE__ ) );
define( 'TTFW_URL', plugin_dir_url( __FILE__ ) );

require_once TTFW_PATH . 'includes/class-ttfw-settings.php';
require_once TTFW_PATH . 'includes/class-ttfw-ai-service.php';
require_once TTFW_PATH . 'includes/class-ttfw-rest-controller.php';
require_once TTFW_PATH . 'includes/class-ttfw-plugin.php';

register_activation_hook(
	__FILE__,
	static function () {
		if ( false === get_option( TTFW_Settings::OPTION_NAME, false ) ) {
			add_option( TTFW_Settings::OPTION_NAME, TTFW_Settings::defaults(), '', false );
		}
	}
);

TTFW_Plugin::init();
