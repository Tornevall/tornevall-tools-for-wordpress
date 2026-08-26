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

$ttfw_statuspage_settings = get_option( 'ttfw_statuspage_settings', array() );
if ( is_array( $ttfw_statuspage_settings ) && ! empty( $ttfw_statuspage_settings['slug'] ) ) {
	delete_transient( 'ttfw_statuspage_live_' . md5( (string) $ttfw_statuspage_settings['slug'] ) );
}

delete_option( 'ttfw_statuspage_settings' );
delete_option( 'ttfw_statuspage_last_good' );
delete_option( 'ttfw_options' );
delete_option( 'ttfw_dyndns_status' );
