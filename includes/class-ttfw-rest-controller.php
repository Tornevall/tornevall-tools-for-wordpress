<?php
/**
 * REST controller for editor AI requests.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST endpoints used by the block editor.
 */
class TTFW_REST_Controller {
	public const REST_NAMESPACE = 'ttfw/v1';

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/ai/respond',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'respond' ),
				'permission_callback' => array( __CLASS__, 'can_use_editor_ai' ),
				'args'                => array(
					'provider' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'prompt'   => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/document/extract',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'extract_document' ),
				'permission_callback' => array( __CLASS__, 'can_upload_documents' ),
			)
		);
	}

	/**
	 * Checks REST permissions for AI calls.
	 *
	 * @return bool
	 */
	public static function can_use_editor_ai() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks REST permissions for document extraction.
	 *
	 * @return bool
	 */
	public static function can_upload_documents() {
		return current_user_can( 'edit_posts' ) && current_user_can( 'upload_files' );
	}

	/**
	 * Handles an AI response request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function respond( WP_REST_Request $request ) {
		$options  = TTFW_Settings::get_options();
		$provider = sanitize_key( (string) $request->get_param( 'provider' ) );
		$provider = in_array( $provider, array( 'tools', 'openai' ), true ) ? $provider : (string) $options['default_provider'];
		$prompt   = TTFW_Settings::sanitize_long_text( $request->get_param( 'prompt' ), 12000 );

		if ( '' === trim( $prompt ) ) {
			return new WP_Error( 'ttfw_missing_prompt', __( 'Instructions are required.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$persona = TTFW_Settings::sanitize_long_text( $request->get_param( 'persona' ), 6000 );
		if ( '' === trim( $persona ) ) {
			$persona = (string) $options['default_persona'];
		}

		$model = TTFW_Settings::sanitize_identifier( $request->get_param( 'model' ), 80 );
		if ( '' === $model ) {
			$model = 'tools' === $provider ? (string) $options['tools_model'] : (string) $options['openai_model'];
		}

		$language = sanitize_key( (string) $request->get_param( 'response_language' ) );
		if ( ! in_array( $language, array( 'auto', 'sv', 'en', 'da', 'no', 'de', 'fr', 'es' ), true ) ) {
			$language = (string) $options['response_language'];
		}

		$output_format = sanitize_key( (string) $request->get_param( 'output_format' ) );
		if ( ! in_array( $output_format, array( 'wp_markdown', 'plain' ), true ) ) {
			$output_format = 'wp_markdown';
		}

		$payload = array(
			'prompt'            => $prompt,
			'context'           => TTFW_Settings::sanitize_long_text( $request->get_param( 'context' ), 20000 ),
			'custom_text'       => TTFW_Settings::sanitize_long_text( $request->get_param( 'custom_text' ), 100000 ),
			'persona'           => $persona,
			'model'             => $model,
			'response_language' => $language,
			'output_format'     => $output_format,
		);

		$result = ( new TTFW_AI_Service() )->respond( $provider, $payload );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Extracts text from an uploaded document.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function extract_document( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return new WP_Error( 'ttfw_upload_missing', __( 'No document was uploaded.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$result = TTFW_Document_Extractor::extract_from_upload( $files['file'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}
}
