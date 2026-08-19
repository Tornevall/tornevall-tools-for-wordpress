<?php
/**
 * Module registry.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists features shipped by Tornevall Tools for WordPress.
 */
class TTFW_Module_Registry {
	/**
	 * Returns module metadata used by the admin UI.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		$options = TTFW_Settings::get_options();

		return array(
			'dynamic-dns' => array(
				'name'        => __( 'Dynamic DNS', 'tornevall-tools-for-wordpress' ),
				'description' => __( 'Keep a Tornevall Networks Dynamic DNS hostname updated from this WordPress server.', 'tornevall-tools-for-wordpress' ),
				'enabled'     => ! empty( $options['dyndns_enabled'] ),
				'status'      => TTFW_Dynamic_DNS_Module::configuration_status(),
			),
		);
	}
}
