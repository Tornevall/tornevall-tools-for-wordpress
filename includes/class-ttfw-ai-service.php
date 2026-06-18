<?php
/**
 * Server-side AI connector service.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calls OpenAI and Tornevall Tools AI endpoints from WordPress.
 */
class TTFW_AI_Service {
	/**
	 * Sends a request to the selected provider.
	 *
	 * @param string              $provider Provider key.
	 * @param array<string,mixed> $payload Sanitized request payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function respond( $provider, $payload ) {
		$options  = TTFW_Settings::get_options();
		$provider = in_array( $provider, array( 'tools', 'openai' ), true ) ? $provider : (string) $options['default_provider'];
		return 'openai' === $provider ? $this->respond_openai( $payload, $options ) : $this->respond_tools( $payload, $options );
	}

	/**
	 * Tests a configured provider token from wp-admin.
	 *
	 * @param string $provider Provider key.
	 * @return array<string,string>|WP_Error
	 */
	public function test_provider( $provider ) {
		$provider = in_array( $provider, array( 'tools', 'openai' ), true ) ? $provider : '';
		if ( '' === $provider ) {
			return new WP_Error( 'ttfw_invalid_provider', __( 'Invalid provider.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$result = $this->respond(
			$provider,
			array(
				'prompt'            => 'Reply exactly with: ok',
				'context'           => 'Connection test from Tornevall Tools for WordPress.',
				'custom_text'       => '',
				'persona'           => 'You are a connection test endpoint. Reply exactly with: ok',
				'model'             => '',
				'response_language' => 'en',
				'output_format'     => 'plain',
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'message' => sprintf(
				/* translators: 1: provider name, 2: short response text. */
				__( '%1$s token test succeeded. Provider returned: %2$s', 'tornevall-tools-for-wordpress' ),
				'tools' === $provider ? __( 'Tools AI', 'tornevall-tools-for-wordpress' ) : __( 'OpenAI', 'tornevall-tools-for-wordpress' ),
				TTFW_Settings::limit_string( sanitize_text_field( (string) $result['text'] ), 120 )
			),
		);
	}

	private function respond_openai( $payload, $options ) {
		$token = trim( (string) $options['openai_token'] );
		$model = ! empty( $payload['model'] ) ? (string) $payload['model'] : (string) $options['openai_model'];

		if ( '' === $token ) {
			return new WP_Error( 'ttfw_missing_openai_token', __( 'OpenAI token is not configured.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}
		if ( '' === $model ) {
			return new WP_Error( 'ttfw_missing_openai_model', __( 'OpenAI model is not configured.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$input = array();
		if ( ! empty( $payload['persona'] ) ) {
			$input[] = array(
				'role'    => 'developer',
				'content' => array( array( 'type' => 'input_text', 'text' => (string) $payload['persona'] ) ),
			);
		}
		$input[] = array(
			'role'    => 'user',
			'content' => array( array( 'type' => 'input_text', 'text' => $this->build_user_text( $payload ) ) ),
		);

		return $this->normalize_http_response(
			wp_remote_post(
				'https://api.openai.com/v1/responses',
				array(
					'timeout' => (int) $options['timeout'],
					'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( array( 'model' => $model, 'input' => $input ) ),
				)
			),
			'openai'
		);
	}

	private function respond_tools( $payload, $options ) {
		$token = trim( (string) $options['tools_token'] );
		$url   = trim( (string) $options['tools_api_url'] );

		if ( '' === $token ) {
			return new WP_Error( 'ttfw_missing_tools_token', __( 'Tools AI token is not configured.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}
		if ( '' === $url ) {
			return new WP_Error( 'ttfw_missing_tools_endpoint', __( 'Tools AI endpoint is not configured.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$body = array(
			'client_slug'       => (string) $options['tools_client_slug'],
			'context'           => $this->build_tools_context( $payload ),
			'user_prompt'       => (string) $payload['prompt'],
			'client_name'       => 'Tornevall Tools for WordPress',
			'client_version'    => TTFW_VERSION,
			'client_platform'   => 'wordpress',
			'response_language' => ! empty( $payload['response_language'] ) ? (string) $payload['response_language'] : (string) $options['response_language'],
		);

		if ( ! empty( $payload['persona'] ) ) {
			$body['persona_profile_override']    = (string) $payload['persona'];
			$body['custom_instruction_override'] = (string) $payload['persona'];
		}
		if ( ! empty( $payload['model'] ) ) {
			$body['model'] = (string) $payload['model'];
		} elseif ( ! empty( $options['tools_model'] ) ) {
			$body['model'] = (string) $options['tools_model'];
		}

		return $this->normalize_http_response(
			wp_remote_post(
				$url,
				array(
					'timeout' => (int) $options['timeout'],
					'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $body ),
				)
			),
			'tools'
		);
	}

	private function build_user_text( $payload ) {
		$parts = array();
		if ( ! empty( $payload['context'] ) ) {
			$parts[] = "Selected WordPress block context:\n" . (string) $payload['context'];
		}
		if ( ! empty( $payload['custom_text'] ) ) {
			$parts[] = "Custom text to process:\n" . (string) $payload['custom_text'];
		}
		$parts[] = "Instructions:\n" . (string) $payload['prompt'];
		if ( ! empty( $payload['output_format'] ) && 'wp_markdown' === $payload['output_format'] ) {
			$parts[] = "Output format:\nReturn clean Markdown that can be converted to WordPress/Gutenberg blocks. Use headings, paragraphs, lists, blockquotes, code blocks, links, and tables only when useful. Do not wrap the full answer in a code fence unless the requested content is code.";
		}
		return implode( "\n\n", $parts );
	}

	private function build_tools_context( $payload ) {
		$parts = array();
		if ( ! empty( $payload['persona'] ) ) {
			$parts[] = "Persona:\n" . (string) $payload['persona'];
		}
		if ( ! empty( $payload['context'] ) ) {
			$parts[] = "Selected WordPress block context:\n" . (string) $payload['context'];
		}
		if ( ! empty( $payload['custom_text'] ) ) {
			$parts[] = "Custom text to process:\n" . (string) $payload['custom_text'];
		}
		if ( ! empty( $payload['output_format'] ) && 'wp_markdown' === $payload['output_format'] ) {
			$parts[] = "Output format:\nReturn clean Markdown that can be converted to WordPress/Gutenberg blocks. Do not wrap the full answer in a code fence unless the requested content is code.";
		}
		return implode( "\n\n", $parts );
	}

	private function normalize_http_response( $response, $provider ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ttfw_http_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ttfw_invalid_json', __( 'The AI provider returned invalid JSON.', 'tornevall-tools-for-wordpress' ), array( 'status' => 502 ) );
		}
		if ( $status < 200 || $status >= 300 ) {
			$message = $this->extract_error_message( $data );
			return new WP_Error( 'ttfw_provider_error', '' === $message ? sprintf( __( 'The AI provider returned HTTP %d.', 'tornevall-tools-for-wordpress' ), $status ) : $message, array( 'status' => $this->map_status( $status ) ) );
		}

		$text = $this->extract_text( $data, $provider );
		if ( '' === $text ) {
			return new WP_Error( 'ttfw_empty_response', __( 'The AI provider returned no usable text.', 'tornevall-tools-for-wordpress' ), array( 'status' => 502 ) );
		}

		return array(
			'ok'         => true,
			'provider'   => $provider,
			'text'       => $text,
			'model'      => isset( $data['model'] ) && is_string( $data['model'] ) ? $data['model'] : '',
			'usage'      => isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : null,
			'request_id' => isset( $data['request_id'] ) && is_scalar( $data['request_id'] ) ? (string) $data['request_id'] : '',
		);
	}

	private function extract_text( $data, $provider ) {
		if ( 'tools' === $provider && isset( $data['response'] ) && is_string( $data['response'] ) ) {
			return trim( $data['response'] );
		}
		if ( 'tools' === $provider && isset( $data['text'] ) && is_string( $data['text'] ) ) {
			return trim( $data['text'] );
		}
		if ( isset( $data['output_text'] ) && is_string( $data['output_text'] ) ) {
			return trim( $data['output_text'] );
		}
		return trim( implode( "\n", $this->collect_text_fields( $data ) ) );
	}

	private function collect_text_fields( $value ) {
		$items = array();
		if ( ! is_array( $value ) ) {
			return $items;
		}
		if ( isset( $value['type'], $value['text'] ) && 'output_text' === $value['type'] && is_string( $value['text'] ) ) {
			$items[] = $value['text'];
		}
		foreach ( $value as $child_key => $child_value ) {
			if ( in_array( $child_key, array( 'error', 'usage' ), true ) ) {
				continue;
			}
			if ( is_array( $child_value ) ) {
				$items = array_merge( $items, $this->collect_text_fields( $child_value ) );
			}
		}
		return $items;
	}

	private function extract_error_message( $data ) {
		if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
			return trim( $data['error'] );
		}
		if ( isset( $data['error']['message'] ) && is_string( $data['error']['message'] ) ) {
			return trim( $data['error']['message'] );
		}
		return isset( $data['message'] ) && is_string( $data['message'] ) ? trim( $data['message'] ) : '';
	}

	private function map_status( $status ) {
		return in_array( $status, array( 400, 401, 403, 422, 429, 503 ), true ) ? $status : ( $status >= 500 ? 502 : 400 );
	}
}
