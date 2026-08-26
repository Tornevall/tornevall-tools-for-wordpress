<?php
/**
 * Focused deterministic tests for Statuspage Gutenberg block metadata and renderer wiring.
 */

define( 'ABSPATH', __DIR__ );

$root = dirname( __DIR__ );
$metadata = json_decode( file_get_contents( $root . '/blocks/statuspage/block.json' ), true );
$script = file_get_contents( $root . '/blocks/statuspage/index.js' );
$renderer = file_get_contents( $root . '/includes/class-ttfw-statuspage.php' );
$failures = array();

$assert_true = static function ( $condition, $label ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $label;
	}
};

$assert_true( is_array( $metadata ), 'block.json must contain valid JSON' );
$assert_true( 'tornevall-tools/statuspage' === ( $metadata['name'] ?? '' ), 'block name must remain canonical' );
$assert_true( 3 === ( $metadata['apiVersion'] ?? null ), 'block must use Block API v3' );
$assert_true( false === ( $metadata['attributes']['history']['default'] ?? null ), 'history must default to disabled' );
$assert_true( false === ( $metadata['supports']['html'] ?? null ), 'raw HTML editing must stay disabled' );
$assert_true( false !== strpos( $script, "registerBlockType( 'tornevall-tools/statuspage'" ), 'editor script must register the canonical block' );
$assert_true( false !== strpos( $script, 'Show recent resolved incidents' ), 'editor must expose the history control' );
$assert_true( false === strpos( $script, 'api/status/' ), 'editor script must not call the Status Platform API directly' );
$assert_true( false !== strpos( $renderer, "add_shortcode( self::SHORTCODE" ), 'legacy shortcode must remain registered' );
$assert_true( false !== strpos( $renderer, "'render_callback' => array( __CLASS__, 'render_block' )" ), 'block must be server rendered' );
$assert_true( false !== strpos( $renderer, 'return self::render(' ), 'shortcode must use the shared renderer' );
$assert_true( false !== strpos( $renderer, '$content = self::render(' ), 'block must use the shared renderer' );

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Statuspage block tests passed.\n";
