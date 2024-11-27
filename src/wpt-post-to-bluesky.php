<?php
/**
 * Send API queries for a post to Bluesky.
 *
 * @category Post from WordPress.
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.xposter.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

require_once plugin_dir_path( __FILE__ ) . 'classes/class-wpt-bluesky-api.php';

/**
 * Upload media to Bluesky API.
 *
 * @param object   $connection Bluesky connection.
 * @param int|bool $auth Connection context.
 * @param int      $attachment Attachment ID.
 * @param array    $status Array of posting information.
 * @param int      $id Post ID.
 *
 * @return array
 */
function wpt_upload_bluesky_media( $connection, $auth, $attachment, $status, $id ) {
	$request = array();
	if ( $connection ) {
		if ( $attachment ) {
			$alt_text = get_post_meta( $attachment, '_wp_attachment_image_alt', true );
			/**
			 * Add alt attributes to uploaded images.
			 *
			 * @hook wpt_uploaded_image_alt
			 *
			 * @param {string} $alt_text Text stored in media library as alt.
			 * @param {int}    $attachment Attachment ID.
			 *
			 * @return {string}
			 */
			$alt_text        = apply_filters( 'wpt_uploaded_image_alt', $alt_text, $attachment );
			$attachment_data = wpt_image_binary( $attachment, 'bluesky' );
			// Return without attempting if fails to fetch image object.
			if ( ! $attachment_data ) {
				return $status;
			}
			$request = array(
				'image' => $attachment_data,
				'alt'   => $alt_text,
			);
		}
	}

	return $request;
}

/**
 * Post status to Bluesky.
 *
 * @param object $connection Connection to Bluesky.
 * @param mixed  $auth Main site or specific author ID.
 * @param int    $id Post ID.
 * @param array  $status Array of information sent to Bluesky.
 * @param array  $image Array of image data to add to Bluesky post.
 *
 * @return array
 */
function wpt_send_post_to_bluesky( $connection, $auth, $id, $status, $image ) {
	$notice = '';
	/**
	 * Turn on staging mode. Staging mode is automatically turned on if WPT_STAGING_MODE constant is defined.
	 *
	 * @hook wpt_staging_mode
	 * @param {bool}     $staging_mode True to enable staging mode.
	 * @param {int|bool} $auth Current author.
	 * @param {int}      $id Post ID.
	 *
	 * @return {bool}
	 */
	$staging_mode = apply_filters( 'wpt_staging_mode', false, $auth, $id );
	if ( ( defined( 'WPT_STAGING_MODE' ) && true === WPT_STAGING_MODE ) || $staging_mode ) {
		// if in staging mode, we'll behave as if the Tweet succeeded, but not send it.
		$connection = true;
		$http_code  = 200;
		$notice     = __( 'In Staging Mode:', 'wp-to-twitter' ) . ' ' . $status['text'];
		$status_id  = false;
	} else {
		/**
		 * Filter the approval to send a Bluesky Skeet.
		 *
		 * @hook wpt_do_skeet
		 * @param {bool}     $do_skeet Return false to cancel this Skeet.
		 * @param {int|bool} $auth Author.
		 * @param {int}      $id Post ID.
		 * @param {string}   $text Status update text.
		 *
		 * @return {bool}
		 */
		$do_post   = apply_filters( 'wpt_do_skeet', true, $auth, $id, $status['text'] );
		$status_id = false;
		$success   = false;
		// Change status array to Bluesky expectation.
		$status = array(
			'type'      => 'app.bsky.feed.post',
			'text'      => $status['text'],
			'createdAt' => gmdate( DATE_ATOM ),
		);
		if ( ! empty( $image ) ) {
			$status['embed'] = array(
				'$type'  => 'app.bsky.embed.images',
				'images' => $image,
			);
		}
		/**
		 * Filter status array for Bluesky.
		 *
		 * @hook wpt_filter_bluesky_status
		 *
		 * @param {array}    $status Array of parameters sent to Bluesky.
		 * @param {int}      $post Post ID being tweeted.
		 * @param {int|bool} $auth Authoring context.
		 *
		 * @return {array}
		 */
		$status = apply_filters( 'wpt_filter_bluesky_status', $status, $id, $auth );
		if ( $do_post ) {
			$return = $connection->post_status( $status );
			if ( isset( $return['id'] ) ) {
				$success   = true;
				$http_code = 200;
				$status_id = $return['id'];
				$notice   .= __( 'Sent to Bluesky.', 'wp-to-twitter' );
			} else {
				$http_code = 401;
				$notice   .= __( 'Bluesky status update failed.', 'wp-to-twitter' );
			}
		} else {
			$http_code = '000';
			$notice   .= __( 'Bluesky status update cancelled by custom filter.', 'wp-to-twitter' );
		}
	}

	return array(
		'return'    => $success,
		'http'      => $http_code,
		'notice'    => $notice,
		'status_id' => $status_id,
	);
}

/**
 * Establish a client to Bluesky.
 *
 * @param mixed int|boolean $auth Current author context.
 * @param array             $verify Array of credentials to validate.
 *
 * @return mixed $bluesky or false
 */
function wpt_bluesky_connection( $auth = false, $verify = false ) {
	if ( ! empty( $verify ) ) {
		$password = $verify['token']; // TODO: Fetching a token needs to happen every time? Need username here.
	} else {
		if ( ! $auth ) {
			$password = get_option( 'wpt_bluesky_token' );
		} else {
			$password = get_user_meta( $auth, 'wpt_bluesky_token', true );
		}
	}
	$bluesky = false;
	if ( $password ) {
		$bluesky = new Wpt_Bluesky_Api( $password, $username );
		if ( $verify ) {
			$verify = $bluesky->verify();

			return $verify;
		}
	}

	return $bluesky;
}
