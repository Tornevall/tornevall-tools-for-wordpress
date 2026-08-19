<?php
/**
 * Uninstall cleanup.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

wp_clear_scheduled_hook( 'ttfw_dyndns_update_event' );
delete_option( 'ttfw_options' );
delete_option( 'ttfw_dyndns_status' );
