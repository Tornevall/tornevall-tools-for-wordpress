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
 * Provides the public Statuspage shortcode and snapshot health semantics.
 */
class TTFW_Statuspage {
	const CACHE_TRANSIENT_PREFIX = 'ttfw_statuspage_live_';
	const LAST_GOOD_OPTION       = 'ttfw_statuspage_last_good';
	const SHORTCODE              = 'tornevall_statuspage';

	/** @return void */
	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * @param bool $force Force a remote refresh.
	 * @return array<string,mixed>
	 */
	public static function snapshot( $force = false ) {
		$slug = TTFW_Statuspage_Settings::slug();
		if ( '' === $slug ) {
			return array(
				'health'    => 'not_configured',
				'is_stale'  => false,
				'payload'   => array(),
				'error'     => '',
				'fetched_at'=> '',
			);
		}

		$cache_key = self::CACHE_TRANSIENT_PREFIX . md5( $slug );
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

		$last_good = get_option( self::LAST_GOOD_OPTION, array() );
		if ( is_array( $last_good ) && $slug === (string) ( $last_good['slug'] ?? '' ) && is_array( $last_good['snapshot'] ?? null ) && ! empty( $last_good['snapshot']['payload'] ) ) {
			$snapshot = $last_good['snapshot'];
			$snapshot['health'] = 'stale';
			$snapshot['is_stale'] = true;
			$snapshot['error'] = sanitize_text_field( $result->get_error_message() );
			return $snapshot;
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
		$status = TTFW_Statuspage_API::normalize_status( $status );
		return $status;
	}

	/**
	 * Overview status. Communication failures are never reported as major outage.
	 *
	 * @return string
	 */
	public static function configuration_status() {
		if ( '' === TTFW_Statuspage_Settings::slug() ) {
			return __( 'Not configured', 'tornevall-tools-for-wordpress' );
		}
		$snapshot = self::snapshot();
		switch ( $snapshot['health'] ?? 'unavailable' ) {
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
			case 'stale':
				return __( 'Last known status (stale)', 'tornevall-tools-for-wordpress' );
			case 'unknown':
				return __( 'Status not yet verified', 'tornevall-tools-for-wordpress' );
			default:
				return __( 'Temporarily unavailable', 'tornevall-tools-for-wordpress' );
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

		if ( '1' === (string) $atts['history'] && ! empty( $payload['incident_history'] ) && is_array( $payload['incident_history'] ) ) {
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
