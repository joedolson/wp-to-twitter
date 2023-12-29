<?php
/**
 * Mastodon access class.
 *
 * @category OAuth
 * @package  XPoster
 * @author   https://github.com/Eleirbag89, documented and adapted to WP code style.
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth / MastodonAPI
 *
 * A library to send status updates to Mastodon instances.
 *
 * @author eleirbag89
 * @version 0.1
 * @link https://github.com/Eleirbag89/MastodonBotPHP
 */
class MastodonAPI {
	private $token;
	private $instance_url;

	public function __construct( $token, $instance_url ) {
		$this->token        = $token;
		$this->instance_url = $instance_url;
	}

	/**
	 * Post a status to the mastodon status endpoint.
	 *
	 * @param array $status Array posted to Mastodon. [status,visibility,language,media_ids="[]"]
	 *
	 * @return array Mastodon response.
	 */
	public function postStatus( $status ) {
		return $this->callAPI( '/api/v1/statuses', 'POST', $status );
	}

	/**
	 * Post a media attachment to the mastodon status endpoint.
	 *
	 * @param array $media Array of media data posted to Mastodon. [file,description]
	 *
	 * @return array Mastodon response.
	 */
	public function uploadMedia( $media ) {
		return $this->callAPI( '/api/v1/media', 'POST', $media );
	}

	/**
	 * Post to the API endpoint.
	 *
	 * @param string $endpoint REST API path.
	 * @param string $method query method. GET, POST, etc.
	 * @param array  $data Data being posted.
	 *
	 * @return array Mastodon response or error.
	 */
	public function callAPI( $endpoint, $method, $data ) {
		$headers = array(
			'Authorization: Bearer '.$this->token,
			'Content-Type: multipart/form-data',
		);

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->instance_url . $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$reply = curl_exec( $ch );

		if ( ! $reply ) {
			$error = array(
				'ok'              => false,
				'curl_error_code' => curl_errno( $ch ),
				'curl_error'      => curl_error( $ch ),
			);
			return json_encode( $error );
		}
		curl_close( $ch );

		return json_decode( $reply, true );
	}
}