<?php
/**
 * Uninstall cleanup.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ttfw_options' );
