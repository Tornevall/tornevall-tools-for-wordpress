<?php
/**
 * Plugin Name: Tornevall Tools for WordPress
 * Plugin URI: https://github.com/Tornevall/tornevall-tools-for-wordpress
 * Description: Connects WordPress to selected Tornevall Networks Tools services, including Guestbook and Dynamic DNS.
 * Version: 0.2.0
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

define( 'TTFW_VERSION', '0.2.0' );
define( 'TTFW_FILE', __FILE__ );
define( 'TTFW_PATH', plugin_dir_path( __FILE__ ) );

require_once TTFW_PATH . 'includes/class-ttfw-settings.php';
require_once TTFW_PATH . 'includes/class-ttfw-api-client.php';
require_once TTFW_PATH . 'includes/class-ttfw-dynamic-dns-module.php';
require_once TTFW_PATH . 'includes/class-ttfw-guestbook.php';
require_once TTFW_PATH . 'includes/class-ttfw-module-registry.php';
require_once TTFW_PATH . 'includes/class-ttfw-plugin.php';

register_activation_hook( __FILE__, array( 'TTFW_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TTFW_Plugin', 'deactivate' ) );

TTFW_Plugin::init();
