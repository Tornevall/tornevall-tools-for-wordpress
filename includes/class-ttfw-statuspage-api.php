<?php
/**
 * Public Status Platform API client.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and validates public status snapshots from Tornevall Tools.
 */
class TTFW_Statuspage_API {
	const API_PREFIX = '/api/statuspage/';

	/**
	 * @param string $slug Status page slug.
	 * @return array<string,mixed>|WP_Error
	 */
	public function fetch( $slug ) {
		$slug = TTFW_Statuspage_Settings::sanitize_slug( $slug );
		if ( '' === $slug ) {
			return new WP_Error( 'ttfw_statuspage_invalid_slug', __( 'A valid Statuspage slug is required.', 'tornevall-tools-for-wordpress' ) );
		}

		$response = ( new TTFW_API_Client() )->public_request( 'GET', self::API_PREFIX . rawurlencode( $slug ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->normalize_payload( $response, $slug );
	}

	/**
	 * Normalize the canonical unversioned ToolsAPI response into the plugin's
	 * renderer/cache shape. Keeping this adapter server-side preserves the
	 * existing shortcode/block renderer while Tools remains authoritative.
	 *
	 * @param array<string,mixed> $data Raw payload.
	 * @param string              $expected_slug Expected page slug.
	 * @return array<string,mixed>|WP_Error
	 */
	private function normalize_payload( $data, $expected_slug ) {
		$slug = isset( $data['slug'] ) ? TTFW_Statuspage_Settings::sanitize_slug( $data['slug'] ) : '';
		if ( '' === $slug || $expected_slug !== $slug ) {
			return new WP_Error( 'ttfw_statuspage_invalid_payload', __( 'Tools returned an unsupported Statuspage response.', 'tornevall-tools-for-wordpress' ) );
		}

		$status = self::normalize_status( $data['status'] ?? 'unknown' );
		$incidents = $this->normalize_incidents( $data['incidents'] ?? array() );
		$active_incidents = array();
		$incident_history = array();
		foreach ( $incidents as $incident ) {
			if ( 'resolved' === ( $incident['status'] ?? '' ) || '' !== ( $incident['resolved_at'] ?? '' ) ) {
				$incident_history[] = $incident;
			} else {
				$active_incidents[] = $incident;
			}
		}

		return array(
			'page' => array(
				'slug'        => $slug,
				'name'        => sanitize_text_field( (string) ( $data['name'] ?? $slug ) ),
				'description' => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
				'homepage_url'=> '',
			),
			'overall' => array(
				'status'  => $status,
				'label'   => self::status_label( $status ),
				'message' => '',
			),
			'components'       => $this->normalize_components( $data['components'] ?? array() ),
			'active_incidents' => $active_incidents,
			'incident_history' => $incident_history,
			'generated_at'     => sanitize_text_field( (string) ( $data['published_at'] ?? '' ) ),
		);
	}

	/**
	 * @param mixed $components Raw components.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_components( $components ) {
		$output = array();
		if ( ! is_array( $components ) ) {
			return $output;
		}
		foreach ( $components as $component ) {
			if ( ! is_array( $component ) ) {
				continue;
			}
			$status = self::normalize_status( $component['status'] ?? 'unknown' );
			$output[] = array(
				'id'           => absint( $component['id'] ?? 0 ),
				'key'          => '',
				'name'         => sanitize_text_field( (string) ( $component['name'] ?? '' ) ),
				'description'  => sanitize_textarea_field( (string) ( $component['description'] ?? '' ) ),
				'status'       => $status,
				'status_label' => self::status_label( $status ),
			);
		}
		return $output;
	}

	/**
	 * @param mixed $incidents Raw incidents.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_incidents( $incidents ) {
		$output = array();
		if ( ! is_array( $incidents ) ) {
			return $output;
		}
		foreach ( $incidents as $incident ) {
			if ( ! is_array( $incident ) ) {
				continue;
			}
			$updates = array();
			foreach ( is_array( $incident['updates'] ?? null ) ? $incident['updates'] : array() as $update ) {
				if ( ! is_array( $update ) ) {
					continue;
				}
				$updates[] = array(
					'id'         => absint( $update['id'] ?? 0 ),
					'status'     => sanitize_key( (string) ( $update['status'] ?? 'unknown' ) ),
					'message'    => sanitize_textarea_field( (string) ( $update['message'] ?? '' ) ),
					'created_at' => sanitize_text_field( (string) ( $update['published_at'] ?? '' ) ),
				);
			}
			$output[] = array(
				'id'             => absint( $incident['id'] ?? 0 ),
				'slug'           => '',
				'title'          => sanitize_text_field( (string) ( $incident['title'] ?? '' ) ),
				'severity'       => sanitize_key( (string) ( $incident['impact'] ?? 'unknown' ) ),
				'status'         => sanitize_key( (string) ( $incident['status'] ?? 'unknown' ) ),
				'public_summary' => sanitize_textarea_field( (string) ( $incident['summary'] ?? '' ) ),
				'started_at'     => sanitize_text_field( (string) ( $incident['opened_at'] ?? '' ) ),
				'updated_at'     => '',
				'resolved_at'    => sanitize_text_field( (string) ( $incident['resolved_at'] ?? '' ) ),
				'updates'        => $updates,
			);
		}
		return $output;
	}

	/**
	 * @param mixed $status Raw status.
	 * @return string
	 */
	public static function normalize_status( $status ) {
		$status = sanitize_key( (string) $status );
		return in_array( $status, array( 'operational', 'degraded', 'partial_outage', 'major_outage', 'maintenance', 'unknown' ), true ) ? $status : 'unknown';
	}

	/**
	 * @param string $status Status key.
	 * @return string
	 */
	public static function status_label( $status ) {
		switch ( self::normalize_status( $status ) ) {
			case 'operational':
				return __( 'All systems operational', 'tornevall-tools-for-wordpress' );
			case 'degraded':
				return __( 'Degraded performance', 'tornevall-tools-for-wordpress' );
			case 'partial_outage':
				return __( 'Partial outage', 'tornevall-tools-for-wordpress' );
			case 'major_outage':
				return __( 'Major outage', 'tornevall-tools-for-wordpress' );
			case 'maintenance':
				return __( 'Maintenance in progress', 'tornevall-tools-for-wordpress' );
			default:
				return __( 'Status not yet verified', 'tornevall-tools-for-wordpress' );
		}
	}
}
