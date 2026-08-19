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
 * Lists integrations shipped by Tornevall Tools for WordPress.
 */
class TTFW_Module_Registry {
	/**
	 * Returns integration metadata used by the admin UI.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		$options = TTFW_Settings::get_options();
		$guestbook_configured = TTFW_Guestbook_API::configured();
		$guestbook_status = __( 'Needs configuration', 'tornevall-tools-for-wordpress' );

		if ( $guestbook_configured ) {
			$guestbook_status = TTFW_Guestbook_Settings::turnstile_configured()
				? __( 'Configured', 'tornevall-tools-for-wordpress' )
				: __( 'Configured for reading; signing disabled', 'tornevall-tools-for-wordpress' );
		}

		return array(
			'guestbook' => array(
				'name'        => __( 'Guestbook', 'tornevall-tools-for-wordpress' ),
				'description' => __( 'Owner-scoped Tornevall Networks Tools guestbook with public display, signing and moderation.', 'tornevall-tools-for-wordpress' ),
				'enabled'     => $guestbook_configured,
				'status'      => $guestbook_status,
			),
			'dynamic-dns' => array(
				'name'        => __( 'Dynamic DNS', 'tornevall-tools-for-wordpress' ),
				'description' => __( 'Keep a Tornevall Networks Dynamic DNS hostname updated from this WordPress server.', 'tornevall-tools-for-wordpress' ),
				'enabled'     => ! empty( $options['dyndns_enabled'] ),
				'status'      => TTFW_Dynamic_DNS_Module::configuration_status(),
			),
		);
	}
}
