<?php
/**
 * Plugin Name: Multi-MIME Uploads
 * Description: Adds support for multiple MIME types per file extension during file uploads.
 * Version: 0.1.1
 * Author: Davide Milani
 * Author URI: https://www.milanidavide.com/
 * License: BSD-3-Clause license
 * License URI: https://opensource.org/licenses/BSD-3-Clause
 * Text Domain: multi-mime-uploads
 * Domain Path: /languages
 * Update URI: false
 *
 * @package multi-mime-uploads
 */

/**
 * Filters the file type and extension during the upload process, enabling support for multiple MIME types
 * associated with a single file extension.
 *
 * By default, WordPress associates only one MIME type with each file extension. This function extends that
 * behavior by checking additional MIME types for a file extension.
 *
 * The function avoids interference if:
 * - The file type is already determined (`type` is not empty).
 * - The allowed MIME types (`$mimes`) are explicitly provided (e.g., via `wp_handle_upload`).
 * - The `Fileinfo` PHP extension is unavailable.
 *
 * @param array    $filetype_and_ext Array containing file type, extension, and proper filename.
 * @param string   $file             Full path to the file.
 * @param string   $filename         The name of the file (may differ from $file due to $file being
 *                                   in a tmp directory).
 * @param string[] $mimes            Optional. Array of allowed mime types keyed by their file extension regex.
 *                                   Defaults to the result of get_allowed_mime_types().
 * @param string   $real_mime        The MIME type determined by PHP's Fileinfo.
 *
 * @return array {
 *     Values for the extension, mime type, and corrected filename.
 *
 *     @type string|false $ext             File extension, or false if the file doesn't match a mime type.
 *     @type string|false $type            File mime type, or false if the file doesn't match a mime type.
 *     @type string|false $proper_filename File name with its correct extension, or false if it cannot be determined.
 * }
 */
function multimimeup_check_filetype_and_ext( $filetype_and_ext, $file, $filename, $mimes, $real_mime ) {
	if (
		empty( $filetype_and_ext['type'] ) &&
		extension_loaded( 'fileinfo' ) &&
		empty( $mimes )
	) {
		$wp_filetype = wp_check_filetype( $filename );
		$ext         = $wp_filetype['ext'];

		if ( empty( $ext ) ) {
			return $filetype_and_ext;
		}

		$mimes = apply_filters( 'multimimeup_add_mimes', array(), null );

		if ( empty( $mimes ) || ! isset( $mimes[ $ext ] ) ) {
			return $filetype_and_ext;
		}

		if ( is_string( $mimes[ $ext ] ) ) {
			$mimes[ $ext ] = array( $mimes[ $ext ] );
		}

		if ( in_array( $real_mime, $mimes[ $ext ], true ) ) {
			$filetype_and_ext['ext']             = $ext;
			$filetype_and_ext['type']            = $real_mime;
			$filetype_and_ext['proper_filename'] = false;
		}
	}

	return $filetype_and_ext;
}
add_filter( 'wp_check_filetype_and_ext', 'multimimeup_check_filetype_and_ext', 10, 5 );

/**
 * Adds allowed MIME types and file extensions, supporting multiple mime types per extension.
 *
 * @param array            $mimes  Mime types keyed by the file extension regex corresponding to those types.
 * @param int|WP_User|null $user   User ID, User object or null if not provided (indicates current user).
 *
 * @return string[] Array of mime types keyed by the file extension regex corresponding to those types.
 */
function multimimeup_extend_upload_mimes( $mimes, $user ) {
	/**
	 * Adds allowed MIME types and file extensions, supporting multiple MIME types per extension.
	 *
	 * This function allows the addition of custom MIME types for specific file extensions,
	 * enabling support for multiple MIME types per extension (e.g., for files with multiple valid formats).
	 * The result is a list of MIME types that can be used for validating uploads, with each extension potentially
	 * having more than one MIME type associated with it.
	 *
	 * @param array            $mimes   An associative array where the key is the file extension,
	 *                                  and the value is one or more MIME types.
	 * @param int|WP_User|null  $user   User ID, User object or null if not provided (indicates current user).
	 *
	 * @return array {
	 *      An associative array where the key is the file extension, and the value is one or more MIME types.
	 *
	 *      @type string           $ext    File extension, or false if the file doesn't match a mime type.
	 *      @type string|string[]  $mimes  Single or multiple mime types associated with the extension
	 * }
	 */
	$additional_mimes = apply_filters( 'multimimeup_add_mimes', array(), $user );

	// WordPress requires a single MIME type per extension.
	// Normalize additional MIME types to the first MIME type only.
	// The remaining MIME types will be checked by `multimimeup_check_filetype_and_ext`.
	foreach ( $additional_mimes as $ext => $mime ) {
		if ( is_array( $mime ) ) {
			$mimes[ $ext ] = $mime[0];
		} else {
			$mimes[ $ext ] = $mime;
		}
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'multimimeup_extend_upload_mimes', 10, 2 );
