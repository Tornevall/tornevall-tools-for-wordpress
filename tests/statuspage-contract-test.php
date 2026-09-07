<?php
/**
 * Focused deterministic tests for Statuspage contract normalization.
 */

define( 'ABSPATH', __DIR__ );

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_message() { return $this->message; }
}

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

$assert_same( '/api/statuspage/', TTFW_Statuspage_API::API_PREFIX, 'canonical unversioned endpoint' );
$assert_same( 'tools', TTFW_Statuspage_Settings::sanitize_slug( 'Tools' ), 'slug normalization' );
$assert_same( 'tools-prod_1', TTFW_Statuspage_Settings::sanitize_slug( 'tools-prod_1' ), 'slug accepted characters' );
$assert_same( '', TTFW_Statuspage_Settings::sanitize_slug( '../tools' ), 'path traversal rejected' );
$assert_same( '', TTFW_Statuspage_Settings::sanitize_slug( 'tools/status' ), 'slash rejected' );

foreach ( array( 'operational', 'degraded', 'partial_outage', 'major_outage', 'maintenance', 'unknown' ) as $status ) {
	$assert_same( $status, TTFW_Statuspage_API::normalize_status( $status ), 'accepted status ' . $status );
}
$assert_same( 'unknown', TTFW_Statuspage_API::normalize_status( 'offline' ), 'unknown remote state normalized safely' );

$api = new TTFW_Statuspage_API();
$normalize = new ReflectionMethod( TTFW_Statuspage_API::class, 'normalize_payload' );
$normalize->setAccessible( true );
$normalized = $normalize->invoke(
	$api,
	array(
		'slug' => 'tools',
		'name' => 'ToolsAPI',
		'description' => 'Public status',
		'status' => 'degraded',
		'published_at' => '2026-09-06T09:28:38+00:00',
		'components' => array(
			array( 'id' => 1, 'name' => 'API', 'description' => 'Public API', 'status' => 'operational', 'sort_order' => 0 ),
		),
		'incidents' => array(
			array(
				'id' => 10,
				'title' => 'Latency',
				'status' => 'monitoring',
				'impact' => 'minor',
				'summary' => 'Requests are slower.',
				'opened_at' => '2026-09-06T08:00:00+00:00',
				'resolved_at' => null,
				'updates' => array( array( 'id' => 11, 'status' => 'monitoring', 'message' => 'Recovering', 'published_at' => '2026-09-06T08:30:00+00:00' ) ),
			),
			array(
				'id' => 20,
				'title' => 'Resolved issue',
				'status' => 'resolved',
				'impact' => 'minor',
				'summary' => 'Resolved.',
				'opened_at' => '2026-09-05T08:00:00+00:00',
				'resolved_at' => '2026-09-05T09:00:00+00:00',
				'updates' => array(),
			),
		),
	),
	'tools'
);

$assert_same( 'tools', $normalized['page']['slug'], 'canonical payload slug' );
$assert_same( 'degraded', $normalized['overall']['status'], 'canonical payload overall status' );
$assert_same( 'API', $normalized['components'][0]['name'], 'canonical component mapping' );
$assert_same( 1, count( $normalized['active_incidents'] ), 'active incident split' );
$assert_same( 1, count( $normalized['incident_history'] ), 'resolved incident split' );
$assert_same( 'Requests are slower.', $normalized['active_incidents'][0]['public_summary'], 'incident summary mapping' );
$assert_same( '2026-09-06T08:30:00+00:00', $normalized['active_incidents'][0]['updates'][0]['created_at'], 'published update timestamp mapping' );
$assert_same( false, array_key_exists( 'schema_version', $normalized ), 'no schema-version gate/output' );

$assert_same( 'major_outage', TTFW_Statuspage::health_from_remote_status( 'major_outage' ), 'confirmed major outage remains critical' );
$assert_same( 'unknown', TTFW_Statuspage::health_from_remote_status( 'network_error' ), 'unrecognized state is never promoted to outage' );

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Statuspage contract tests passed.\n";
