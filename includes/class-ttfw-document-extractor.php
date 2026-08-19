<?php
/**
 * Uploaded document text extraction.
 *
 * @package TornevallToolsForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts text from editor uploads without storing provider tokens or files.
 */
class TTFW_Document_Extractor {
	public const MAX_UPLOAD_BYTES          = 10485760;
	public const MAX_EXTRACTED_ENTRY_BYTES = 2097152;

	/**
	 * Returns accepted file extensions and mime types.
	 *
	 * @return array<string,string>
	 */
	public static function allowed_mime_types() {
		return array(
			'txt'  => 'text/plain',
			'md'   => 'text/markdown',
			'html' => 'text/html',
			'htm'  => 'text/html',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'doc'  => 'application/msword',
			'pdf'  => 'application/pdf',
		);
	}

	/**
	 * Extracts text from a PHP uploaded file array.
	 *
	 * @param array<string,mixed> $file Uploaded file array.
	 * @return array<string,string>|WP_Error
	 */
	public static function extract_from_upload( $file ) {
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
			return new WP_Error( 'ttfw_upload_missing', __( 'No document was uploaded.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$error = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_OK;
		if ( UPLOAD_ERR_OK !== $error ) {
			return new WP_Error( 'ttfw_upload_failed', self::upload_error_message( $error ), array( 'status' => 400 ) );
		}

		$size = isset( $file['size'] ) ? (int) $file['size'] : 0;
		if ( $size <= 0 || $size > self::MAX_UPLOAD_BYTES ) {
			return new WP_Error( 'ttfw_upload_size', __( 'The uploaded document is empty or too large.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$tmp_name = (string) $file['tmp_name'];
		$name     = sanitize_file_name( wp_basename( (string) $file['name'] ) );
		$ext      = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$allowed  = self::allowed_mime_types();

		if ( ! isset( $allowed[ $ext ] ) ) {
			return new WP_Error( 'ttfw_upload_type', __( 'This file type is not supported for text extraction.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$checked = wp_check_filetype_and_ext( $tmp_name, $name, $allowed );
		if ( empty( $checked['ext'] ) && ! in_array( $ext, array( 'txt', 'md', 'html', 'htm', 'doc' ), true ) ) {
			return new WP_Error( 'ttfw_upload_validation', __( 'WordPress could not validate the uploaded document type.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
		}

		$result = self::extract_by_extension( $tmp_name, $ext );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$text = self::normalize_text( (string) $result['text'] );
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'ttfw_upload_empty_text', __( 'No usable text could be extracted from the uploaded document.', 'tornevall-tools-for-wordpress' ), array( 'status' => 422 ) );
		}

		return array(
			'filename'  => $name,
			'extension' => $ext,
			'text'      => $text,
			'warning'   => isset( $result['warning'] ) ? (string) $result['warning'] : '',
		);
	}

	private static function extract_by_extension( $path, $ext ) {
		switch ( $ext ) {
			case 'txt':
			case 'md':
				return array( 'text' => (string) file_get_contents( $path ) );
			case 'html':
			case 'htm':
				return array( 'text' => wp_strip_all_tags( (string) file_get_contents( $path ), true ) );
			case 'docx':
				return self::extract_docx( $path );
			case 'pdf':
				return self::extract_pdf_best_effort( $path );
			case 'doc':
				return array(
					'text'    => self::extract_printable_strings( (string) file_get_contents( $path ) ),
					'warning' => __( 'Legacy .doc extraction is best-effort. Review the text before sending it to AI.', 'tornevall-tools-for-wordpress' ),
				);
		}

		return new WP_Error( 'ttfw_upload_type', __( 'This file type is not supported for text extraction.', 'tornevall-tools-for-wordpress' ), array( 'status' => 400 ) );
	}

	private static function extract_docx( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'ttfw_docx_zip_missing', __( 'DOCX extraction requires the PHP Zip extension.', 'tornevall-tools-for-wordpress' ), array( 'status' => 500 ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return new WP_Error( 'ttfw_docx_open_failed', __( 'The DOCX file could not be opened.', 'tornevall-tools-for-wordpress' ), array( 'status' => 422 ) );
		}

		$parts = array( 'word/document.xml', 'word/footnotes.xml', 'word/endnotes.xml' );
		$text  = '';
		foreach ( $parts as $part ) {
			$stat = $zip->statName( $part );
			if ( is_array( $stat ) && isset( $stat['size'] ) && (int) $stat['size'] > self::MAX_EXTRACTED_ENTRY_BYTES ) {
				$zip->close();
				return new WP_Error( 'ttfw_docx_entry_too_large', __( 'The DOCX document contains an extracted XML part that is too large to process safely.', 'tornevall-tools-for-wordpress' ), array( 'status' => 422 ) );
			}

			$xml = $zip->getFromName( $part, self::MAX_EXTRACTED_ENTRY_BYTES + 1 );
			if ( false !== $xml && strlen( $xml ) > self::MAX_EXTRACTED_ENTRY_BYTES ) {
				$zip->close();
				return new WP_Error( 'ttfw_docx_entry_too_large', __( 'The DOCX document contains an extracted XML part that is too large to process safely.', 'tornevall-tools-for-wordpress' ), array( 'status' => 422 ) );
			}
			if ( false !== $xml ) {
				$text .= "\n\n" . self::extract_docx_xml_text( $xml );
			}
		}
		$zip->close();

		return array( 'text' => $text );
	}

	private static function extract_docx_xml_text( $xml ) {
		$xml = preg_replace( '/<w:tab\s*\/?>/i', "\t", (string) $xml );
		$xml = preg_replace( '/<w:br\s*\/?>/i', "\n", (string) $xml );
		$xml = preg_replace( '/<\/w:p>/i', "\n\n", (string) $xml );
		$xml = preg_replace( '/<\/w:tr>/i', "\n", (string) $xml );
		$xml = preg_replace( '/<\/w:tc>/i', "\t", (string) $xml );
		$text = html_entity_decode( wp_strip_all_tags( $xml, true ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
		return self::normalize_text( $text );
	}

	private static function extract_pdf_best_effort( $path ) {
		$raw = (string) file_get_contents( $path );
		if ( '' === $raw ) {
			return array( 'text' => '' );
		}

		$text = self::extract_pdf_text_operators( $raw );
		if ( '' === trim( $text ) ) {
			$text = self::extract_printable_strings( $raw );
		}

		return array(
			'text'    => $text,
			'warning' => __( 'PDF extraction is best-effort. Scanned PDFs and complex encodings may need OCR or manual cleanup.', 'tornevall-tools-for-wordpress' ),
		);
	}

	private static function extract_pdf_text_operators( $raw ) {
		$streams = array( $raw );
		if ( preg_match_all( '/stream\r?\n(.*?)\r?\nendstream/s', $raw, $matches ) ) {
			foreach ( $matches[1] as $stream ) {
				$streams[] = $stream;
				$inflated = @gzuncompress( $stream );
				if ( false !== $inflated ) {
					$streams[] = $inflated;
					continue;
				}
				$inflated = @gzinflate( $stream );
				if ( false !== $inflated ) {
					$streams[] = $inflated;
				}
			}
		}

		$parts = array();
		foreach ( $streams as $stream ) {
			if ( preg_match_all( '/\((?:\\\\.|[^\\\\()])*\)\s*Tj/s', $stream, $literal_matches ) ) {
				foreach ( $literal_matches[0] as $match ) {
					$parts[] = self::decode_pdf_literal( preg_replace( '/\)\s*Tj\s*$/s', ')', $match ) );
				}
			}
			if ( preg_match_all( '/\[(.*?)\]\s*TJ/s', $stream, $array_matches ) ) {
				foreach ( $array_matches[1] as $array_body ) {
					if ( preg_match_all( '/\((?:\\\\.|[^\\\\()])*\)/s', $array_body, $array_literals ) ) {
						foreach ( $array_literals[0] as $literal ) {
							$parts[] = self::decode_pdf_literal( $literal );
						}
					}
					if ( preg_match_all( '/<([0-9A-Fa-f]{4,})>/', $array_body, $hex_literals ) ) {
						foreach ( $hex_literals[1] as $hex ) {
							$parts[] = self::decode_pdf_hex( $hex );
						}
					}
				}
			}
		}

		return implode( ' ', array_filter( $parts ) );
	}

	private static function decode_pdf_literal( $literal ) {
		$literal = trim( (string) $literal );
		if ( '(' === substr( $literal, 0, 1 ) && ')' === substr( $literal, -1 ) ) {
			$literal = substr( $literal, 1, -1 );
		}
		$literal = str_replace( array( '\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\' ), array( "\n", "\r", "\t", '', '', '(', ')', '\\' ), $literal );
		return $literal;
	}

	private static function decode_pdf_hex( $hex ) {
		$hex = preg_replace( '/[^0-9A-Fa-f]/', '', (string) $hex );
		if ( '' === $hex ) {
			return '';
		}
		$bin = @hex2bin( 0 !== strlen( $hex ) % 2 ? $hex . '0' : $hex );
		if ( false === $bin ) {
			return '';
		}
		if ( 0 === strpos( $bin, "\xFE\xFF" ) && function_exists( 'mb_convert_encoding' ) ) {
			return (string) mb_convert_encoding( substr( $bin, 2 ), 'UTF-8', 'UTF-16BE' );
		}
		return preg_replace( '/[^\P{C}\r\n\t]+/u', '', $bin );
	}

	private static function extract_printable_strings( $raw ) {
		if ( ! preg_match_all( '/[\x09\x0A\x0D\x20-\x7E\xC2-\xF4][\x09\x0A\x0D\x20-\x7E\x80-\xBF]{3,}/', $raw, $matches ) ) {
			return '';
		}
		return implode( "\n", $matches[0] );
	}

	private static function normalize_text( $text ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		$text = preg_replace( '/[^\P{C}\n\t]+/u', '', $text );
		$text = preg_replace( "/[ \t]+\n/", "\n", (string) $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", (string) $text );
		$text = trim( (string) $text );
		return TTFW_Settings::limit_string( $text, 100000 );
	}

	private static function upload_error_message( $error ) {
		switch ( $error ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'The uploaded document is too large.', 'tornevall-tools-for-wordpress' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The document upload was incomplete.', 'tornevall-tools-for-wordpress' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'No document was uploaded.', 'tornevall-tools-for-wordpress' );
			default:
				return __( 'The document upload failed.', 'tornevall-tools-for-wordpress' );
		}
	}
}
