<?php
/**
 * Statuspage frontend integration and resilient cache.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the public Statuspage shortcode, Gutenberg block and snapshot health semantics.
 */
class TTFW_Statuspage {
	const CACHE_TRANSIENT_PREFIX = 'ttfw_statuspage_live_';
	const LAST_GOOD_OPTION       = 'ttfw_statuspage_last_good';
	const SHORTCODE              = 'tornevall_statuspage';
	const BLOCK_NAME             = 'tornevall-tools/statuspage';
	const BLOCK_CATEGORY         = 'tornevall-tools';
	const BLOCK_SCRIPT_HANDLE    = 'ttfw-statuspage-block-editor';

	/** @return void */
	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'register_block_category' ), 10, 2 );
	}

	/** @return void */
	public static function register_block() {
		$script_path = TTFW_PATH . 'blocks/statuspage/index.js';
		wp_register_script(
			self::BLOCK_SCRIPT_HANDLE,
			TTFW_URL . 'blocks/statuspage/index.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : TTFW_VERSION,
			true
		);

		register_block_type(
			TTFW_PATH . 'blocks/statuspage',
			array(
				'editor_script'   => self::BLOCK_SCRIPT_HANDLE,
				'render_callback' => array( __CLASS__, 'render_block' ),
			)
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $categories Existing categories.
	 * @return array<int,array<string,mixed>>
	 */
	public static function register_block_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( self::BLOCK_CATEGORY === ( $category['slug'] ?? '' ) ) {
				return $categories;
			}
		}
		$categories[] = array(
			'slug'  => self::BLOCK_CATEGORY,
			'title' => __( 'Tornevall Tools', 'tornevall-tools-for-wordpress' ),
		);
		return $categories;
	}

	/**
	 * @param bool $force Force a remote refresh.
	 * @return array<string,mixed>
	 */
	public static function snapshot( $force = false ) {
		$slug = TTFW_Statuspage_Settings::slug();
		if ( '' === $slug ) {
			return array(
				'health'     => 'not_configured',
				'is_stale'   => false,
				'payload'    => array(),
				'error'      => '',
				'fetched_at' => '',
			);
		}

		$cache_key = self::cache_key( $slug );
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) && ! empty( $cached['payload'] ) ) {
				return $cached;
			}
		}

		$result = ( new TTFW_Statuspage_API() )->fetch( $slug );
		if ( ! is_wp_error( $result ) ) {
			$snapshot = array(
				'health'     => self::health_from_remote_status( $result['overall']['status'] ?? 'unknown' ),
				'is_stale'   => false,
				'payload'    => $result,
				'error'      => '',
				'fetched_at' => gmdate( 'c' ),
			);
			set_transient( $cache_key, $snapshot, TTFW_Statuspage_Settings::cache_ttl() );
			update_option( self::LAST_GOOD_OPTION, array( 'slug' => $slug, 'snapshot' => $snapshot ), false );
			return $snapshot;
		}

		$last_good = self::last_good_snapshot( $slug );
		if ( ! empty( $last_good['payload'] ) ) {
			$last_good['health'] = 'stale';
			$last_good['is_stale'] = true;
			$last_good['error'] = sanitize_text_field( $result->get_error_message() );
			return $last_good;
		}

		return array(
			'health'     => 'unavailable',
			'is_stale'   => false,
			'payload'    => array(),
			'error'      => sanitize_text_field( $result->get_error_message() ),
			'fetched_at' => '',
		);
	}

	/**
	 * @param string $status Remote status key.
	 * @return string
	 */
	public static function health_from_remote_status( $status ) {
		return TTFW_Statuspage_API::normalize_status( $status );
	}

	/**
	 * Returns overview status without causing a remote request.
	 *
	 * @return string
	 */
	public static function configuration_status() {
		$slug = TTFW_Statuspage_Settings::slug();
		if ( '' === $slug ) {
			return __( 'Not configured', 'tornevall-tools-for-wordpress' );
		}

		$snapshot = get_transient( self::cache_key( $slug ) );
		if ( ! is_array( $snapshot ) || empty( $snapshot['payload'] ) ) {
			$snapshot = self::last_good_snapshot( $slug );
			if ( empty( $snapshot['payload'] ) ) {
				return __( 'Configured; status not checked', 'tornevall-tools-for-wordpress' );
			}
		}

		switch ( $snapshot['health'] ?? 'unknown' ) {
			case 'operational':
				return __( 'Operational', 'tornevall-tools-for-wordpress' );
			case 'degraded':
				return __( 'Degraded performance', 'tornevall-tools-for-wordpress' );
			case 'partial_outage':
				return __( 'Partial outage', 'tornevall-tools-for-wordpress' );
			case 'major_outage':
				return __( 'Major outage', 'tornevall-tools-for-wordpress' );
			case 'maintenance':
				return __( 'Maintenance', 'tornevall-tools-for-wordpress' );
			case 'unknown':
			default:
				return __( 'Status not yet verified', 'tornevall-tools-for-wordpress' );
		}
	}

	/**
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'history' => '0',
			),
			$atts,
			self::SHORTCODE
		);

		return self::render(
			array(
				'history' => '1' === (string) $atts['history'],
			)
		);
	}

	/**
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $attributes = array() ) {
		$content = self::render(
			array(
				'history' => ! empty( $attributes['history'] ),
			)
		);
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'ttfw-statuspage-block' ) );
		return '<div ' . $wrapper . '>' . $content . '</div>';
	}

	/**
	 * Canonical Statuspage renderer shared by shortcode and Gutenberg block.
	 *
	 * @param array<string,mixed> $attributes Render attributes.
	 * @return string
	 */
	public static function render( $attributes = array() ) {
		$show_history = ! empty( $attributes['history'] );
		$snapshot = self::snapshot();
		$health = sanitize_key( (string) ( $snapshot['health'] ?? 'unavailable' ) );

		if ( 'not_configured' === $health ) {
			return '<div class="ttfw-statuspage ttfw-statuspage--neutral"><p>' . esc_html__( 'Statuspage is not configured.', 'tornevall-tools-for-wordpress' ) . '</p></div>';
		}
		if ( empty( $snapshot['payload'] ) ) {
			return '<div class="ttfw-statuspage ttfw-statuspage--unknown"><p>' . esc_html__( 'Current service status is temporarily unavailable.', 'tornevall-tools-for-wordpress' ) . '</p></div>';
		}

		$payload = $snapshot['payload'];
		$page = is_array( $payload['page'] ?? null ) ? $payload['page'] : array();
		$overall = is_array( $payload['overall'] ?? null ) ? $payload['overall'] : array();
		$components = is_array( $payload['components'] ?? null ) ? $payload['components'] : array();
		$incidents = is_array( $payload['active_incidents'] ?? null ) ? $payload['active_incidents'] : array();

		ob_start();
		echo '<section class="ttfw-statuspage ttfw-statuspage--' . esc_attr( self::css_state( $health ) ) . '">';
		echo '<header class="ttfw-statuspage__header"><h2>' . esc_html( (string) ( $page['name'] ?? __( 'Service status', 'tornevall-tools-for-wordpress' ) ) ) . '</h2>';
		if ( ! empty( $page['description'] ) ) {
			echo '<p>' . esc_html( (string) $page['description'] ) . '</p>';
		}
		echo '<p class="ttfw-statuspage__overall"><strong>' . esc_html( (string) ( $overall['label'] ?? TTFW_Statuspage_API::status_label( 'unknown' ) ) ) . '</strong></p>';
		if ( ! empty( $overall['message'] ) ) {
			echo '<p>' . esc_html( (string) $overall['message'] ) . '</p>';
		}
		if ( ! empty( $snapshot['is_stale'] ) ) {
			echo '<p class="ttfw-statuspage__stale"><em>' . esc_html__( 'Showing the last known status because the live status service could not be reached.', 'tornevall-tools-for-wordpress' ) . '</em></p>';
		}
		echo '</header>';

		if ( ! empty( $components ) ) {
			echo '<div class="ttfw-statuspage__components"><h3>' . esc_html__( 'Components', 'tornevall-tools-for-wordpress' ) . '</h3><ul>';
			foreach ( $components as $component ) {
				if ( ! is_array( $component ) ) {
					continue;
				}
				echo '<li class="ttfw-statuspage__component ttfw-statuspage__component--' . esc_attr( self::css_state( $component['status'] ?? 'unknown' ) ) . '"><span>' . esc_html( (string) ( $component['name'] ?? '' ) ) . '</span> <strong>' . esc_html( (string) ( $component['status_label'] ?? TTFW_Statuspage_API::status_label( $component['status'] ?? 'unknown' ) ) ) . '</strong></li>';
			}
			echo '</ul></div>';
		}

		if ( ! empty( $incidents ) ) {
			echo '<div class="ttfw-statuspage__incidents"><h3>' . esc_html__( 'Active incidents', 'tornevall-tools-for-wordpress' ) . '</h3>';
			foreach ( $incidents as $incident ) {
				self::render_incident( $incident );
			}
			echo '</div>';
		}

		if ( $show_history && ! empty( $payload['incident_history'] ) && is_array( $payload['incident_history'] ) ) {
			echo '<div class="ttfw-statuspage__history"><h3>' . esc_html__( 'Recent incidents', 'tornevall-tools-for-wordpress' ) . '</h3>';
			foreach ( $payload['incident_history'] as $incident ) {
				self::render_incident( $incident );
			}
			echo '</div>';
		}

		$last_updated = (string) ( $payload['generated_at'] ?? $snapshot['fetched_at'] ?? '' );
		if ( '' !== $last_updated ) {
			echo '<footer><small>' . esc_html__( 'Last updated:', 'tornevall-tools-for-wordpress' ) . ' ' . esc_html( $last_updated ) . '</small></footer>';
		}
		echo '</section>';
		return (string) ob_get_clean();
	}

	/**
	 * @param mixed $incident Incident payload.
	 * @return void
	 */
	private static function render_incident( $incident ) {
		if ( ! is_array( $incident ) ) {
			return;
		}
		echo '<article class="ttfw-statuspage__incident"><h4>' . esc_html( (string) ( $incident['title'] ?? __( 'Incident', 'tornevall-tools-for-wordpress' ) ) ) . '</h4>';
		if ( ! empty( $incident['public_summary'] ) ) {
			echo '<p>' . esc_html( (string) $incident['public_summary'] ) . '</p>';
		}
		if ( ! empty( $incident['updates'] ) && is_array( $incident['updates'] ) ) {
			echo '<ol class="ttfw-statuspage__timeline">';
			foreach ( $incident['updates'] as $update ) {
				if ( ! is_array( $update ) ) {
					continue;
				}
				echo '<li><strong>' . esc_html( ucwords( str_replace( '_', ' ', (string) ( $update['status'] ?? 'update' ) ) ) ) . '</strong> ' . esc_html( (string) ( $update['message'] ?? '' ) );
				if ( ! empty( $update['created_at'] ) ) {
					echo ' <small>' . esc_html( (string) $update['created_at'] ) . '</small>';
				}
				echo '</li>';
			}
			echo '</ol>';
		}
		echo '</article>';
	}

	/**
	 * @param string $slug Statuspage slug.
	 * @return string
	 */
	private static function cache_key( $slug ) {
		return self::CACHE_TRANSIENT_PREFIX . md5( (string) $slug );
	}

	/**
	 * @param string $slug Statuspage slug.
	 * @return array<string,mixed>
	 */
	private static function last_good_snapshot( $slug ) {
		$last_good = get_option( self::LAST_GOOD_OPTION, array() );
		if ( ! is_array( $last_good ) || $slug !== (string) ( $last_good['slug'] ?? '' ) || ! is_array( $last_good['snapshot'] ?? null ) ) {
			return array();
		}
		return $last_good['snapshot'];
	}

	/**
	 * @param mixed $health Health key.
	 * @return string
	 */
	private static function css_state( $health ) {
		$health = sanitize_key( (string) $health );
		if ( 'major_outage' === $health ) {
			return 'critical';
		}
		if ( in_array( $health, array( 'degraded', 'partial_outage', 'maintenance', 'stale' ), true ) ) {
			return 'warning';
		}
		if ( 'operational' === $health ) {
			return 'ok';
		}
		return 'unknown';
	}
}
