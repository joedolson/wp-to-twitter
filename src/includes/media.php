<?php
/**
 * Fetch media information for a post.
 *
 * @category Status Updates
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

/**
 * Get image binary for passing to API.
 *
 * @param int    $attachment Attachment ID.
 * @param string $service Which service needs the binary.
 *
 * @return string|object;
 */
function wpt_image_binary( $attachment, $service = 'twitter' ) {
	$image_sizes = get_intermediate_image_sizes();
	if ( in_array( 'large', $image_sizes, true ) ) {
		$size = 'large';
	} else {
		$size = array_pop( $image_sizes );
	}
	/**
	 * Filter the uploaded image size.
	 *
	 * @hook wpt_upload_image_size
	 *
	 * @param string $size Name of size targeted for upload. Default 'large' if exists.
	 *
	 * @return string
	 */
	$size   = apply_filters( 'wpt_upload_image_size', $size );
	$parent = get_post_ancestors( $attachment );
	$parent = ( is_array( $parent ) && isset( $parent[0] ) ) ? $parent[0] : false;
	if ( 'mastodon' === $service ) {
		$path      = wpt_attachment_path( $attachment, $size );
		$mime      = wp_get_image_mime( $path );
		$name      = basename( $path );
		$file      = curl_file_create( $path, $mime, $name );
		$transport = 'curl';
		wpt_mail( 'XPoster: media binary fetched', 'Path: ' . $path . 'Transport: ' . $transport . PHP_EOL . $attachment, $parent );
		if ( ! $file ) {
			return false;
		}

		return $file;

	} elseif ( 'bluesky' === $service ) {
		$path = wpt_attachment_path( $attachment, $size );
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$file = $wp_filesystem->get_contents( $path );

		return $file;
	} else {
		$upload    = wp_get_attachment_image_src( $attachment, $size );
		$image_url = $upload[0];
		$remote    = wp_remote_get( $image_url );
		if ( is_wp_error( $remote ) ) {
			$transport = 'curl';
			$binary    = wp_get_curl( $image_url );
		} else {
			$transport = 'wp_http';
			$binary    = wp_remote_retrieve_body( $remote );
		}
		wpt_mail( 'XPoster: media binary fetched', 'Url: ' . $image_url . 'Transport: ' . $transport . print_r( $remote, 1 ), $parent );
		if ( ! $binary ) {
			return false;
		}
		// TODO: should this be encoded or not?
		return base64_encode( $binary );
	}
}

/**
 * Fetch an attachment's file path. Recurses to fetch full sized path if an invalid size is passed.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size Requested size.
 *
 * @return string|false
 */
function wpt_attachment_path( $attachment_id, $size = '' ) {
	$file = get_attached_file( $attachment_id, true );
	if ( empty( $size ) || 'full' === $size ) {
		// for the original size get_attached_file is fine.
		return realpath( $file );
	}
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false; // the id is not referring to a media.
	}
	$info = image_get_intermediate_size( $attachment_id, $size );
	if ( ! is_array( $info ) || ! isset( $info['file'] ) ) {
		// If this is invalid due to an invalid size, recurse to fetch full size.
		if ( '' !== $size ) {
			$path = wpt_attachment_path( $attachment_id );

			return $path;
		}
		return false; // probably a bad size argument.
	}

	return realpath( str_replace( wp_basename( $file ), $info['file'], $file ) );
}

/**
 * Identify whether a post should be uploading media. Test settings and verify whether post has images that can be uploaded.
 *
 * @param int   $post_ID Post ID.
 * @param array $post_info Array of post data.
 *
 * @return boolean
 */
function wpt_post_with_media( $post_ID, $post_info = array() ) {
	$return = false;
	if ( ! function_exists( 'wpt_pro_exists' ) ) {
		return $return;
	}
	if ( isset( $post_info['wpt_image'] ) && 1 === (int) $post_info['wpt_image'] ) {
		// Post settings win over filters.
		return $return;
	}
	if ( ! get_option( 'wpt_media' ) ) {
		// Don't return immediately, this needs to be overrideable for posts.
		$return = false;
	} else {
		if ( has_post_thumbnail( $post_ID ) || wpt_post_attachment( $post_ID ) ) {
			$return = true;
		}
	}
	/**
	 * Filter whether this post should upload media.
	 *
	 * @hook wpt_upload_media
	 * @param {bool} $upload True to allow this post to upload media.
	 * @param {int}  $post_ID Post ID.
	 *
	 * @return {bool}
	 */
	return apply_filters( 'wpt_upload_media', $return, $post_ID );
}
