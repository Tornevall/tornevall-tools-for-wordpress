<?php
/**
 * Focused deterministic tests for Statuspage contract normalization.
 */

define( 'ABSPATH', __DIR__ );

function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function esc_url_raw( $value ) { return (string) $value; }
function __( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }

require_once dirname( __DIR__ ) . '/includes/class-ttfw-statuspage-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-ttfw-statuspage-api.php';
require_once dirname( __DIR__ ) . '/includes/class-ttfw-statuspage.php';

$failures = array();

$assert_same = static function ( $expected, $actual, $label ) use ( &$failures ) {
	if ( $expected !== $actual ) {
		$failures[] = $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true );
	}
};

$assert_same( 'tools', TTFW_Statuspage_Settings::sanitize_slug( 'Tools' ), 'slug normalization' );
$assert_same( 'tools-prod_1', TTFW_Statuspage_Settings::sanitize_slug( 'tools-prod_1' ), 'slug accepted characters' );
$assert_same( '', TTFW_Statuspage_Settings::sanitize_slug( '../tools' ), 'path traversal rejected' );
$assert_same( '', TTFW_Statuspage_Settings::sanitize_slug( 'tools/status' ), 'slash rejected' );

foreach ( array( 'operational', 'degraded', 'partial_outage', 'major_outage', 'maintenance', 'unknown' ) as $status ) {
	$assert_same( $status, TTFW_Statuspage_API::normalize_status( $status ), 'accepted status ' . $status );
}
$assert_same( 'unknown', TTFW_Statuspage_API::normalize_status( 'offline' ), 'unknown remote state normalized safely' );

$assert_same( 'major_outage', TTFW_Statuspage::health_from_remote_status( 'major_outage' ), 'confirmed major outage remains critical' );
$assert_same( 'unknown', TTFW_Statuspage::health_from_remote_status( 'network_error' ), 'unrecognized state is never promoted to outage' );

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Statuspage contract tests passed.\n";
