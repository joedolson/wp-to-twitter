<?php
/**
 * Bluesky access class.
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
 * OAuth / Wpt_Bluesky_Api
 *
 * A simple library to send status updates to Bluesky instances.
 *
 * @author eleirbag89
 * @version 0.1
 * @link https://github.com/Eleirbag89/BlueskyBotPHP
 */
class Wpt_Bluesky_Api {
	/**
	 * Username/handle for Bluesky instance.
	 *
	 * @var string
	 */
	private $username;

	/**
	 * App password for Bluesky instance.
	 *
	 * @var string
	 */
	private $app_password;

	/**
	 * Construct.
	 *
	 * @param string $username Access token for Bluesky instance.
	 */
	public function __construct( $username, $app_password ) {
		$this->username     = $username;
		$this->app_password = $app_password;
	}

	/**
	 * Post a status to the bluesky status endpoint.
	 *
	 * @param array $status Array posted to Bluesky. [status,visibility,language,media_ids="[]"].
	 *
	 * @return array Bluesky response.
	 */
	public function post_status( $status ) {
		$post = array(
			'collection' => 'app.bsky.feed.post',
			'repo'       => $this->username,
			'record'     => $status,
		);
		$regex = '/(https?:\/\/[^\s]+)/';
    	preg_match_all( $regex, $status['text'], $matches, PREG_OFFSET_CAPTURE );
		$links = array();

		foreach ( $matches[0] as $match ) {
			$urlstring = $match[0];
			$start     = $match[1];
			$end        = $start + strlen( $urlstring );

			$links[] = array(
				'start' => $start,
				'end'   => $end,
				'url'   => $urlstring
			);
		}
	
		if ( ! empty( $links ) ) {
			$facets = array();
			foreach ( $links as $link ) {
				$facets[] = array(
					'index' => array(
						'byteStart' => $link['start'],
						'byteEnd'   => $link['end'],
					),
					'features' => array(
						array(
							'$type' => 'app.bsky.richtext.facet#link',
							'uri'   => $link['url'], 
						),
					)
				);
			}
			$fields['record']['facets'] =  $facets;
		}

		return $this->call_api( 'https://bsky.social/xrpc/com.atproto.repo.createRecord', 'POST', $post );
	}

	/**
	 * Get a Bluesky token.
	 *
	 * @return array Bluesky response.
	 */
	public function verify() {
		$args = array(
			'identifier' => $this->username,
			'password'   => $this->app_password,
		);

		return $this->call_api( 'https://bsky.social/xrpc/com.atproto.server.createSession', 'POST', $args );
	}
	
	/**
	 * Post to the API endpoint.
	 *
	 * @param string $endpoint REST API path.
	 * @param string $method query method. GET, POST, etc.
	 * @param array  $data Data being posted.
	 *
	 * @return array Bluesky response or error.
	 */
	public function call_api( $endpoint, $method, $data ) {
		$headers = array(
			'Authorization: Bearer ' . $this->token,
			'Content-Type: application/json',
			'Accept: application/json',
			'Accept-Charset: utf-8',
		);

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
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
