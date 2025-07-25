<?php
/**
 * URL Shorteners XPoster
 *
 * @category Core
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wpt_shorten_url' ) ) {
	// prep work for future plug-in replacement.
	add_filter( 'wptt_shorten_link', 'wpt_shorten_url', 10, 4 );

	/**
	 * Given a URL, shorten it.
	 *
	 * @param string      $url URL.
	 * @param string      $post_title Post Title.
	 * @param int         $post_ID Post ID.
	 * @param string|bool $testmode Testing function.
	 * @param bool        $store_urls Whether to store URL after creating.
	 * @param bool        $get_urls Whether to fetch a stored URL from the post.
	 *
	 * @return string shortened URL.
	 */
	function wpt_shorten_url( $url, $post_title, $post_ID, $testmode = false, $store_urls = true, $get_urls = true ) {
		$shortener = (string) get_option( 'jd_shortener' );
		// if the URL already exists & a shortener is enabled, return it without processing.
		if ( '3' === $shortener && wpt_short_url( $post_ID ) && $store_urls ) {
			$shrink = wpt_short_url( $post_ID );

			return $shrink;
		}
		/**
		 * Make modifications to URLs prior to shortening.
		 *
		 * @hook wpt_shorten_link
		 * @param {string} $url Full permalink URL to post.
		 * @param {string} $shortener Shortener selected in settings.
		 * @param {int}    $post_ID Post ID.
		 *
		 * @return {string}
		 */
		$url = apply_filters( 'wpt_shorten_link', $url, $shortener, $post_ID );
		if ( false === $testmode ) {
			if ( '1' === get_option( 'use-twitter-analytics' ) || '1' === get_option( 'use_dynamic_analytics' ) ) {
				if ( '1' === get_option( 'use_dynamic_analytics' ) ) {
					$campaign_type = get_option( 'jd_dynamic_analytics' );
					if ( 'post_category' === $campaign_type && 'link' !== $testmode ) {
						$category = get_the_category( $post_ID );
						$campaign = sanitize_title( $category[0]->cat_name );
					} elseif ( 'post_ID' === $campaign_type ) {
						$campaign = $post_ID;
					} elseif ( 'post_title' === $campaign_type && 'link' !== $testmode ) {
						$post     = get_post( $post_ID );
						$campaign = sanitize_title( $post->post_title );
					} else {
						if ( 'link' !== $testmode ) {
							$post        = get_post( $post_ID );
							$post_author = $post->post_author;
							$campaign    = urlencode( get_the_author_meta( 'user_login', $post_author ) );
						} else {
							$campaign = '';
						}
					}
				} else {
					$campaign = get_option( 'twitter-analytics-campaign' );
				}
				/**
				 * Filter the default utm_medium argument in link analytics.
				 *
				 * @hook wpt_utm_medium
				 *
				 * @param {string} $medium Default 'twitter'.
				 *
				 * @return {string}
				 */
				$medium = urlencode( trim( apply_filters( 'wpt_utm_medium', 'twitter' ) ) );
				/**
				 * Filter the default utm_source argument in link analytics.
				 *
				 * @hook wpt_utm_source
				 *
				 * @param {string} $source Default 'twitter'.
				 *
				 * @return {string}
				 */
				$source   = urlencode( trim( apply_filters( 'wpt_utm_source', 'twitter' ) ) );
				$tracking = apply_filters(
					'wpt_analytics_arguments',
					array(
						'utm_campaign' => $campaign,
						'utm_medium'   => $medium,
						'utm_source'   => $source,
					),
					$post_ID
				);
				$url      = add_query_arg( $tracking, $url );
			}
			$url     = urldecode( trim( $url ) ); // prevent double-encoding.
			$encoded = urlencode( $url );
		} else {
			$url     = urldecode( trim( $url ) ); // prevent double-encoding.
			$encoded = urlencode( $url );
		}

		// custom word setting.
		$keyword_format = ( '1' === get_option( 'jd_keyword_format' ) ) ? $post_ID : '';
		$keyword_format = ( '2' === get_option( 'jd_keyword_format' ) ) ? get_post_meta( $post_ID, '_yourls_keyword', true ) : $keyword_format;
		/**
		 * Apply a custom shortener to your status update. Return false to allow the settings to parse the URL or a URL to shortcircuit plugin settings.
		 *
		 * @hook wpt_do_shortening
		 * @param {bool}   $shrink False prior to shortening.
		 * @param {string} $shortener Shortener selected in settings.
		 * @param {string} $url Full permalink URL to post.
		 * @param {string} $post_title Title of source post.
		 * @param {int}    $post_ID Post ID.
		 * @param {bool}   $testmode True if running a test of XPoster.
		 *
		 * @return {string}
		 */
		$shrink = apply_filters( 'wpt_do_shortening', false, $shortener, $url, $post_title, $post_ID, $testmode );
		if ( $shrink !== $url ) {
			wpt_mail( 'Shortener running: initial link', "Url: $url, Title: $post_title, Post ID: $post_ID, Test mode: $testmode, Shortener: $shortener", $post_ID ); // DEBUG.
		}

		// if an add-on has shortened the link, skip shortening.
		$error = false;
		if ( ! $shrink ) {
			switch ( $shortener ) {
				case 3: // no shortener.
					$shrink = $url;
					break;
				case 2: // updated to v3 3/31/2010.
					// Bitly supported via https://wordpress.org/plugins/codehaveli-bitly-url-shortener/.
					$bitlyurl = get_post_meta( $post_ID, '_wbitly_shorturl', true );
					if ( ! empty( $bitlyurl ) && $get_urls ) {
						$shrink = $bitlyurl;
					} else {
						if ( function_exists( 'wbitly_generate_shorten_url' ) ) {
							$shrink = wbitly_generate_shorten_url( $url );
						}
						if ( function_exists( 'wbitly_shorten_url' ) && ! $shrink ) {
							$shrink = wbitly_shorten_url( $url );
						}
					}
					break;
				case 4:
					if ( function_exists( 'wp_get_shortlink' ) ) {
						// wp_get_shortlink doesn't natively support custom post types; but don't return an error in that case.
						$shrink = ( false !== $post_ID ) ? wp_get_shortlink( $post_ID, 'post' ) : $url;
					}
					if ( ! $shrink ) {
						$shrink = $url;
					}
					break;
				case 5:
					// local YOURLS installation.
					define( 'YOURLS_INSTALLING', true ); // Pretend we're installing YOURLS to bypass test for install or upgrade.
					define( 'YOURLS_FLOOD_DELAY_SECONDS', 0 ); // Disable flood check.
					$opath = get_option( 'yourlspath' );
					$ypath = str_replace( 'user', 'includes', $opath );
					if ( file_exists( dirname( $ypath ) . '/load-yourls.php' ) ) { // YOURLS 1.4+.
						require_once dirname( $ypath ) . '/load-yourls.php';
						global $ydb;
						if ( function_exists( 'yourls_add_new_link' ) ) {
							$yourls_result = yourls_add_new_link( $url, $keyword_format, $post_title );
						} else {
							$yourls_result = $url;
						}
					}
					if ( $yourls_result ) {
						$shrink = $yourls_result['shorturl'];
					} else {
						$shrink = false;
					}
					break;
				case 6:
					// remote YOURLS installation.
					$yourlstoken = trim( get_option( 'yourlstoken' ) );
					$yourlslogin = trim( get_option( 'yourlslogin' ) );
					$yourlsurl   = stripcslashes( get_option( 'yourlsurl' ) );
					if ( $yourlstoken && $yourlsurl ) {
						$token     = stripcslashes( $yourlstoken );
						$yourlsurl = esc_url( $yourlsurl );
						if ( $token ) {
							$args = array(
								'signature' => $token,
								'url'       => $encoded,
								'action'    => 'shorturl',
								'format'    => 'json',
								'title'     => urlencode( $post_title ),
							);
						} else {
							$args = array(
								'username' => $yourlslogin,
								'password' => $yourlsurl,
								'url'      => $encoded,
								'action'   => 'shorturl',
								'format'   => 'json',
								'title'    => urlencode( $post_title ),
							);
						}
						if ( $keyword_format ) {
							$args['keyword'] = $keyword_format;
						}

						$api_url = add_query_arg( $args, $yourlsurl );
						$json    = wpt_remote_json( $api_url, false );

						if ( is_object( $json ) ) {
							$shrink = $json->shorturl;
						} else {
							$error  = 'Error code: YOURLS response is not an object';
							$shrink = false;
						}
					}
					break;
				case 7:
					// Su.pr. Stumbleupon closed doors in June 2018.
				case 8:
					// Goo.gl. Service disabled March 2019.
				case 9:
					// Twitter Friendly Links. This plugin not updated in 8 years.
					$shrink = $url;
					break;
				case 10:
					// jotURL, added: 2013-04-10.
					$joturlapi   = trim( get_option( 'joturlapi' ) );
					$joturllogin = trim( get_option( 'joturllogin' ) );
					if ( ! empty( $joturlapi ) && ! empty( $joturllogin ) ) {
						$joturl_longurl_params = trim( get_option( 'joturl_longurl_params' ) );
						$domain                = trim( get_option( 'joturl_domain', false ) );
						if ( '' !== $joturl_longurl_params ) {
							if ( false === strpos( $url, '%3F' ) && false === strpos( $url, '?' ) ) {
								$ct = '?';
							} else {
								$ct = '&';
							}
							$url    .= $ct . $joturl_longurl_params;
							$encoded = urlencode( urldecode( trim( $url ) ) ); // prevent double-encoding.
						}
						$domain  = ( $domain ) ? '&domain=' . $domain : '';
						$decoded = wpt_fetch_url( 'https://api.joturl.com/a/v1/shorten?url=' . $encoded . '&login=' . $joturllogin . '&key=' . $joturlapi . '&format=plain' . $domain );
						if ( false !== $decoded ) {
							$shrink                 = $decoded;
							$joturl_shorturl_params = trim( get_option( 'joturl_shorturl_params' ) );
							if ( '' !== $joturl_shorturl_params ) {
								if ( false === strpos( $shrink, '%3F' ) && false === strpos( $shrink, '?' ) ) {
									$ct = '?';
								} else {
									$ct = '&';
								}
								$shrink .= $ct . $joturl_shorturl_params;
							}
						} else {
							$error  = $decoded;
							$shrink = false;
						}
						if ( ! wpt_is_valid_url( $shrink ) ) {
							$shrink = false;
						}
					}
					break;
				case 11:
					// Hum URL shortener.
					if ( $testmode ) {
						// Hum does not support shortening links without IDs.
						$shrink = $url;
					} else {
						if ( class_exists( 'Hum' ) && method_exists( 'Hum', 'get_shortlink' ) ) {
							$hum    = new Hum();
							$shrink = $hum->get_shortlink( $url, $post_ID, 'post', true );

						} else {
							$shrink = $url;
						}
					}
					break;
				default:
					$shrink = $url;
			}
		}

		if ( $error ) {
			update_option( 'wpt_shortener_status', "$shrink : $error" );
		}
		if ( ! $testmode ) {
			if ( false === $shrink || ( false === filter_var( $shrink, FILTER_VALIDATE_URL ) ) ) {
				update_option( 'wp_url_failure', '1' );
				$shrink = urldecode( $url );
			} else {
				update_option( 'wp_url_failure', '0' );
			}
		}
		$store_urls = apply_filters( 'wpt_store_url', $store_urls );
		if ( $store_urls ) {
			wpt_store_url( $post_ID, $shrink );
		}

		return $shrink;
	}

	/**
	 * Store shortened URL for re-use.
	 *
	 * @param int    $post_ID Post ID.
	 * @param string $url Shortened URL.
	 */
	function wpt_store_url( $post_ID, $url ) {
		$store_urls = apply_filters( 'wpt_store_urls', true, $post_ID, $url );
		if ( function_exists( 'wpt_shorten_url' ) && $store_urls ) {
			$shortener = get_option( 'jd_shortener' );
			// Don't store URLs if not shortening is selected.
			if ( '3' === $shortener ) {
				return;
			}
			if ( wpt_short_url( $post_ID ) !== $url && wpt_is_valid_url( $url ) ) {
				update_post_meta( $post_ID, '_wpt_short_url', $url );
			}
			switch ( $shortener ) {
				case 5:
				case 6:
					$target = wpt_expand_yourl( $url, $shortener );
					break;
				default:
					$target = $url;
			}
		} else {
			$target = $url;
		}
		update_post_meta( $post_ID, '_wp_jd_target', $target );
	}

	/**
	 * Expand a saved YOURL URl.
	 *
	 * @param string $short_url Shortened URL.
	 * @param int    $remote Remote or local install.
	 *
	 * @return long url.
	 */
	function wpt_expand_yourl( $short_url, $remote ) {
		if ( 6 === (int) $remote ) {
			$short_url = urlencode( $short_url );
			$yourl_api = get_option( 'yourlsurl' );
			$user      = get_option( 'yourlslogin' );
			$pass      = stripslashes( get_option( 'yourlsapi' ) );
			$token     = get_option( 'yourlstoken' );
			if ( $token ) {
				$decoded = wpt_remote_json( $yourl_api . "?action=expand&shorturl=$short_url&format=json&signature=$token" );
				if ( '404' === (string) $decoded['errorCode'] ) {
					$short_url = urldecode( $short_url );
					if ( false === stripos( $short_url, 'https://' ) ) {
						// Yourls will throw an error for mismatched protocol.
						$short_url = str_replace( 'http://', 'https://', $short_url );
					} else {
						$short_url = str_replace( 'https://', 'http://', $short_url );
					}
					$short_url = urlencode( $short_url );
					$decoded   = wpt_remote_json( $yourl_api . "?action=expand&shorturl=$short_url&format=json&signature=$token" );
				}
			} else {
				$decoded = wpt_remote_json( $yourl_api . "?action=expand&shorturl=$short_url&format=json&username=$user&password=$pass" );
			}
			$url = ( isset( $decoded['longurl'] ) ) ? $decoded['longurl'] : $short_url;

			return $url;
		} else {
			define( 'YOURLS_INSTALLING', true ); // Pretend we're installing YOURLS to bypass test for install or upgrade.
			define( 'YOURLS_FLOOD_DELAY_SECONDS', 0 ); // Disable flood check.
			if ( file_exists( dirname( get_option( 'yourlspath' ) ) . '/load-yourls.php' ) ) { // YOURLS 1.4+.
				global $ydb;
				require_once dirname( get_option( 'yourlspath' ) ) . '/load-yourls.php';
				$yourls_result = yourls_api_expand( $short_url );
			}
			if ( $yourls_result ) {
				$url = $yourls_result['longurl'];
			} else {
				$url = $short_url;
			}
			return $url;
		}
	}

	/**
	 * Get shorteners.
	 *
	 * @param int $shortener Selected shortener key.
	 *
	 * @return array
	 */
	function wpt_get_shorteners( $shortener ) {
		$shorteners = array(
			2  => array(
				'label'    => 'Bit.ly',
				'id'       => 'bitly',
				'callback' => 'wpt_bitly_form',
			),
			4  => array(
				'label'    => 'WordPress',
				'id'       => 'wordpress',
				'callback' => 'wpt_no_shortener_settings',
			),
			5  => array(
				'label'    => 'YOURLS (Local)',
				'id'       => 'yourls_local',
				'callback' => 'wpt_local_yourls_form',
			),
			6  => array(
				'label'    => 'YOURLS',
				'id'       => 'yourls_remote',
				'callback' => 'wpt_remote_yourls_form',
			),
			10 => array(
				'label'    => 'jotURL',
				'id'       => 'joturl',
				'callback' => 'wpt_joturl_form',
			),
			11 => array(
				'label'    => 'Hum',
				'id'       => 'hum',
				'callback' => 'wpt_hum_form',
			),
		);
		/**
		 * Filter available shorteners.
		 *
		 * @param {array} $shorteners Array of shorteners by ID.
		 * @param {int}   $shortener Selected shortener.
		 *
		 * @return {array}
		 */
		$shorteners = apply_filters( 'wpt_shorteners', $shorteners, $shortener );

		return $shorteners;
	}

	/**
	 * Fetch a given shortener.
	 *
	 * @param int $shortener Shortener ID.
	 */
	function wpt_show_shortener( $shortener ) {
		$shorteners = wpt_get_shorteners( $shortener );
		if ( isset( $shorteners[ $shortener ] ) && isset( $shorteners[ $shortener ]['callback'] ) ) {
			$callback = $shorteners[ $shortener ]['callback'];
		} else {
			$callback = 'wpt_no_shortener_settings';
		}

		call_user_func( $callback );
	}

	/**
	 * Default shortener no settings.
	 */
	function wpt_no_shortener_settings() {
		?>
		<p><?php esc_html_e( 'Your selected shortener has no settings', 'wp-to-twitter' ); ?></p>
		<?php
	}

	/**
	 * Local YOURLS form.
	 */
	function wpt_local_yourls_form() {
		?>
		<p>
			<label for="yourlspath"><?php esc_html_e( 'Path to your YOURLS config file', 'wp-to-twitter' ); ?></label><br/>
			<input type="text" id="yourlspath" name="yourlspath" class="widefat" value="<?php echo esc_attr( get_option( 'yourlspath' ) ); ?>"/><br/>
			<small><?php esc_html_e( 'Example:', 'wp-to-twitter' ); ?> <code>/home/username/www/www/yourls/user/config.php</code>
			</small>
		</p>
		<p>
			<label for="yourlstoken"><?php esc_html_e( 'YOURLS signature token:', 'wp-to-twitter' ); ?></label>
			<input type="text" name="yourlstoken" id="yourlstoken" size="30" value="<?php echo esc_attr( get_option( 'yourlstoken' ) ); ?>"/>
		</p>
		<?php
		if ( get_option( 'yourlsapi' ) && get_option( 'yourlslogin' ) ) {
			?>
			<p>
				<em><?php esc_html_e( 'Your YOURLS username and password are saved. If you add a signature token, that will be used for API calls and your username and password will be deleted from the database.', 'wp-to-twitter' ); ?></em>
			</p>
			<?php
		}
		?>
		<p>
			<input type="radio" name="jd_keyword_format" id="jd_keyword_id" value="1" <?php checked( get_option( 'jd_keyword_format' ), 1 ); ?> />
			<label for="jd_keyword_id"><?php esc_html_e( 'Post ID for YOURLS url slug.', 'wp-to-twitter' ); ?></label><br/>
			<input type="radio" name="jd_keyword_format" id="jd_keyword" value="2" <?php checked( get_option( 'jd_keyword_format' ), 2 ); ?> />
			<label for="jd_keyword"><?php esc_html_e( 'Custom keyword for YOURLS url slug.', 'wp-to-twitter' ); ?></label><br/>
			<input type="radio" name="jd_keyword_format" id="jd_keyword_default" value="0" <?php checked( get_option( 'jd_keyword_format' ), 0 ); ?> />
			<label for="jd_keyword_default"><?php esc_html_e( 'Default: sequential URL numbering.', 'wp-to-twitter' ); ?></label>
		</p>
		<div>
			<input type="hidden" name="submit-type" value="yourlsapi" />
		</div>
		<p>
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Save URL Shortener Settings', 'wp-to-twitter' ); ?>" class="button-primary" />
		</p>
		<?php
	}

	/**
	 * Remote YOURLS form.
	 */
	function wpt_remote_yourls_form() {
		?>
		<p>
			<label for="yourlsurl"><?php esc_html_e( 'URI to the YOURLS API', 'wp-to-twitter' ); ?></label><br/>
			<input type="text" id="yourlsurl" name="yourlsurl" class="widefat" value="<?php echo esc_attr( get_option( 'yourlsurl' ) ); ?>"/><br/>
			<small><?php esc_html_e( 'Example:', 'wp-to-twitter' ); ?> <code>https://domain.com/yourls-api.php</code>
			</small>
		</p>
		<p>
			<label for="yourlstoken"><?php esc_html_e( 'YOURLS signature token:', 'wp-to-twitter' ); ?></label>
			<input type="text" name="yourlstoken" id="yourlstoken" size="30" value="<?php echo esc_attr( get_option( 'yourlstoken' ) ); ?>"/>
		</p>
		<?php
		if ( get_option( 'yourlsapi' ) && get_option( 'yourlslogin' ) ) {
			?>
			<p>
				<em><?php esc_html_e( 'Your YOURLS username and password are saved. If you add a signature token, that will be used for API calls and your username and password will be deleted from the database.', 'wp-to-twitter' ); ?></em>
			</p>
			<?php
		}
		?>
		<p>
			<input type="radio" name="jd_keyword_format" id="jd_keyword_id" value="1" <?php checked( get_option( 'jd_keyword_format' ), 1 ); ?> />
			<label for="jd_keyword_id"><?php esc_html_e( 'Post ID for YOURLS url slug.', 'wp-to-twitter' ); ?></label><br/>
			<input type="radio" name="jd_keyword_format" id="jd_keyword" value="2" <?php checked( get_option( 'jd_keyword_format' ), 2 ); ?> />
			<label for="jd_keyword"><?php esc_html_e( 'Custom keyword for YOURLS url slug.', 'wp-to-twitter' ); ?></label><br/>
			<input type="radio" name="jd_keyword_format" id="jd_keyword_default" value="0" <?php checked( get_option( 'jd_keyword_format' ), 0 ); ?> />
			<label for="jd_keyword_default"><?php esc_html_e( 'Default: sequential URL numbering.', 'wp-to-twitter' ); ?></label>
		</p>
		<div>
			<input type="hidden" name="submit-type" value="yourlsapi" />
		</div>
		<p>
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Save URL Shortener Settings', 'wp-to-twitter' ); ?>" class="button-primary" />
		</p>
		<?php
	}

	/**
	 * Bitly information.
	 */
	function wpt_bitly_form() {
		if ( function_exists( 'wbitly_shorten_url' ) ) {
			?>
			<p><?php echo wp_kses_post( __( 'XPoster supports Bit.ly shortened links via <a href="https://wordpress.org/plugins/codehaveli-bitly-url-shortener/">Codehaveli Bitly URL Shortener</a>. If you are having issues with Bit.ly URLs, please request support from <a href="https://wordpress.org/support/plugin/codehaveli-bitly-url-shortener/">the plugin support forums</a>.', 'wp-to-twitter' ) ); ?></p>
			<?php
		} else {
			?>
			<p><?php echo wp_kses_post( __( 'XPoster supports Bit.ly shortened links via <a href="https://wordpress.org/plugins/codehaveli-bitly-url-shortener/">Codehaveli Bitly URL Shortener</a>. Install that plug-in to use Bit.ly with XPoster.', 'wp-to-twitter' ) ); ?></p>
			<?php
		}
	}

	/**
	 * Hum information.
	 */
	function wpt_hum_form() {
		if ( class_exists( 'Hum' ) ) {
			?>
			<p><?php echo wp_kses_post( __( 'XPoster supports shortened links via the <a href="https://wordpress.org/plugins/hum/">Hum URL Shortener</a>. If you are having issues with Hum URLs, please request support from <a href="https://wordpress.org/support/plugin/hum/">the plugin support forums</a>.', 'wp-to-twitter' ) ); ?></p>
			<?php
		} else {
			?>
			<p><?php echo wp_kses_post( __( 'XPoster supports shortened links via the <a href="https://wordpress.org/plugins/hum/">Hum URL Shortener</a>. Install that plug-in to use Hum with XPoster.', 'wp-to-twitter' ) ); ?></p>
			<?php
		}
	}

	/**
	 * Shortener: jotURL form.
	 */
	function wpt_joturl_form() {
		?>
		<p>
			<label for="joturllogin"><?php esc_html_e( 'Your jotURL public API key:', 'wp-to-twitter' ); ?></label><br>
			<input type="text" name="joturllogin" id="joturllogin" value="<?php echo esc_attr( get_option( 'joturllogin' ) ); ?>"/>
		</p>
		<p>
			<label for="joturlapi"><?php esc_html_e( 'Your jotURL private API key:', 'wp-to-twitter' ); ?></label><br>
			<input type="text" name="joturlapi" id="joturlapi" size="40" value="<?php echo esc_attr( get_option( 'joturlapi' ) ); ?>"/>
		</p>
		<p>
			<label for="joturl_domain"><?php esc_html_e( 'Your jotURL custom domain:', 'wp-to-twitter' ); ?></label><br>
			<input type="text" name="joturl_domain" id="joturl_domain" size="40" value="<?php echo esc_attr( get_option( 'joturl_domain' ) ); ?>"/>
		</p>
		<p>
			<label for="joturl_longurl_params"><?php esc_html_e( 'Parameters to add to the long URL (before URL shortening):', 'wp-to-twitter' ); ?></label><br>
			<input type="text" name="joturl_longurl_params" id="joturl_longurl_params" size="40" value="<?php echo esc_attr( get_option( 'joturl_longurl_params' ) ); ?>"/>
		</p>

		<p>
			<label for="joturl_shorturl_params"><?php esc_html_e( 'Parameters to add to the short URL (after URL shortening):', 'wp-to-twitter' ); ?></label><br>
			<input type="text" name="joturl_shorturl_params" id="joturl_shorturl_params" size="40" value="<?php echo esc_attr( get_option( 'joturl_shorturl_params' ) ); ?>"/>
		</p>
		<p>
			<a href="https://joturl.com/reserved/settings.html#tools-api"><?php esc_html_e( 'View your jotURL public and private API key', 'wp-to-twitter' ); ?></a>
		</p>
		<div><input type="hidden" name="submit-type" value="joturlapi"/></div>
		<p>
			<input type="submit" name="submit" value="<?php esc_attr_e( 'Save URL Shortener Settings', 'wp-to-twitter' ); ?>" class="button-primary" />
		</p>
		<?php
	}

	/**
	 * Controls for adding shortener relevant data.
	 */
	function wpt_shortener_controls() {
		$shortener = (int) get_option( 'jd_shortener' );
		$admin_url = admin_url( 'admin.php?page=wp-tweets-pro' );
		?>
		<div class="panel">
			<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'shortener', $admin_url ) ); ?>">
				<div><input type="hidden" name="wpt_shortener_update" value="true" /></div>
				<?php wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, true ); ?>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h3>
							<span><?php esc_html_e( 'URL Shortener Account Settings', 'wp-to-twitter' ); ?></span>
						</h3>
						<div class="inside">
							<?php
							wpt_show_shortener( $shortener );
							?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Update settings for shorteners.
	 *
	 * @param array $post POST data.
	 */
	function wpt_shortener_update( $post ) {
		$message = '';
		if ( isset( $post['submit-type'] ) && 'yourlsapi' === $post['submit-type'] ) {
			$message = '';
			if ( '' !== $post['yourlstoken'] && isset( $post['submit'] ) ) {
				update_option( 'yourlstoken', trim( $post['yourlstoken'] ) );
				delete_option( 'yourlsapi' );
				delete_option( 'yourlslogin' );
				$message .= __( 'YOURLS signature token updated.', 'wp-to-twitter' );
			}
			update_option( 'yourlsurl', trim( $post['yourlsurl'] ) );
			// yourls path is deprecated.
			if ( isset( $post['yourlspath'] ) && '' !== $post['yourlspath'] ) {
				update_option( 'yourlspath', trim( $post['yourlspath'] ) );
				if ( file_exists( $post['yourlspath'] ) ) {
					$message .= ' ' . __( 'YOURLS local server path added. ', 'wp-to-twitter' );
				} else {
					$message .= ' ' . __( 'The path to your YOURLS installation is not correct. ', 'wp-to-twitter' );
				}
			}
			if ( '' !== $post['jd_keyword_format'] ) {
				update_option( 'jd_keyword_format', $post['jd_keyword_format'] );
				if ( '1' === $post['jd_keyword_format'] ) {
					$message .= ' ' . __( 'YOURLS will use Post ID for short URL slug.', 'wp-to-twitter' );
				} elseif ( '0' === $post['jd_keyword_format'] ) {
					$message .= ' ' . __( 'YOURLS will use default URL structures.', 'wp-to-twitter' );
				} else {
					$message .= ' ' . __( 'YOURLS will use your custom keyword for short URL slug.', 'wp-to-twitter' );
				}
			}
			if ( isset( $post['clear'] ) ) {
				delete_option( 'yourlsapi' );
				delete_option( 'yourlslogin' );
				delete_option( 'yourlstoken' );
				delete_option( 'jd_keyword_format' );
				delete_option( 'yourlspath' );
				delete_option( 'yourlsurl' );
				$message .= __( 'YOURLS data cleared.', 'wp-to-twitter' );
			}
		}

		if ( isset( $post['submit-type'] ) && 'joturlapi' === $post['submit-type'] ) {
			if ( '' !== $post['joturlapi'] && isset( $post['submit'] ) ) {
				update_option( 'joturlapi', trim( $post['joturlapi'] ) );
				$message = __( 'jotURL private API Key Updated.', 'wp-to-twitter' );
			} elseif ( isset( $post['clear'] ) ) {
				update_option( 'joturlapi', '' );
				$message = __( 'jotURL private API Key deleted. You cannot use the jotURL API without a private API key.', 'wp-to-twitter' );
			} else {
				$message = __( "jotURL private API Key not added - <a href='https://www.joturl.com/reserved/api.html'>get one here</a>! A private API key is required to use the jotURL URL shortening service. ", 'wp-to-twitter' );
			}
			if ( '' !== $post['joturllogin'] && isset( $post['submit'] ) ) {
				update_option( 'joturllogin', trim( $post['joturllogin'] ) );
				$message .= __( 'jotURL public API Key Updated.', 'wp-to-twitter' );
			} elseif ( isset( $post['clear'] ) ) {
				update_option( 'joturllogin', '' );
				$message = __( 'jotURL public API Key deleted. You cannot use the jotURL API without providing your public API Key.', 'wp-to-twitter' );
			} else {
				$message = __( "jotURL public API Key not added - <a href='https://www.joturl.com/reserved/settings.html#tools-api'>get one here</a>! ", 'wp-to-twitter' );
			}
			if ( '' !== $post['joturl_longurl_params'] && isset( $post['submit'] ) ) {
				$v = trim( $post['joturl_longurl_params'] );
				if ( substr( $v, 0, 1 ) === '&' || substr( $v, 0, 1 ) === '?' ) {
					$v = substr( $v, 1 );
				}
				update_option( 'joturl_longurl_params', $v );
				$message .= __( 'Long URL parameters added.', 'wp-to-twitter' );
			} elseif ( isset( $post['clear'] ) ) {
				update_option( 'joturl_longurl_params', '' );
				$message = __( 'Long URL parameters deleted.', 'wp-to-twitter' );
			}
			if ( '' !== $post['joturl_domain'] && isset( $post['submit'] ) ) {
				update_option( 'joturl_domain', $post['joturl_domain'] );
				$message .= __( 'Custom jotURL domain saved.', 'wp-to-twitter' );
			} elseif ( isset( $post['clear'] ) ) {
				update_option( 'joturl_domain', '' );
				$message = __( 'Custom jotURL domain deleted.', 'wp-to-twitter' );
			}
			if ( '' !== $post['joturl_shorturl_params'] && isset( $post['submit'] ) ) {
				$v = trim( $post['joturl_shorturl_params'] );
				if ( substr( $v, 0, 1 ) === '&' || substr( $v, 0, 1 ) === '?' ) {
					$v = substr( $v, 1 );
				}
				update_option( 'joturl_shorturl_params', $v );
				$message .= __( 'Short URL parameters added.', 'wp-to-twitter' );
			} elseif ( isset( $post['clear'] ) ) {
				update_option( 'joturl_shorturl_params', '' );
				$message = __( 'Short URL parameters deleted.', 'wp-to-twitter' );
			}
		}
		$message = apply_filters( 'wpt_save_shortener_settings', $message );

		return $message;
	}

	/**
	 * Select a shortener.
	 *
	 * @param array $post POST data.
	 *
	 * @return message.
	 */
	function wpt_select_shortener( $post ) {
		$message = '';
		// don't return a message if unchanged.
		$stored = ( isset( $_POST['wpt_use_stored_urls'] ) ) ? 'false' : 'true';
		update_option( 'wpt_use_stored_urls', $stored );
		if ( get_option( 'jd_shortener' ) === $post['jd_shortener'] ) {
			return;
		}
		update_option( 'jd_shortener', $post['jd_shortener'] );
		$short     = (string) get_option( 'jd_shortener' );
		$admin_url = admin_url( 'admin.php?page=wp-tweets-pro' );
		$admin_url = add_query_arg( 'tab', 'shortener', $admin_url );

		// these are the URL shorteners which require settings.
		if ( '2' === $short || '10' === $short || '6' === $short ) {
			// Translators: Settings URL for shortener configuration.
			$message .= sprintf( __( 'You must <a href="%s">configure your URL shortener settings</a>.', 'wp-to-twitter' ), $admin_url );
		}

		if ( '' !== $message ) {
			$message .= '<br />';
		}

		return $message;
	}

	/**
	 * Form to select your shortener.
	 */
	function wpt_pick_shortener() {
		$shortener = (string) get_option( 'jd_shortener', false );
		if ( '2' === $shortener && ! function_exists( 'wbitly_shorten_url' ) ) {
			$install_bitly = admin_url( 'plugin-install.php?s=codehaveli+bitly+url+shortener&tab=search&type=term' );
			$bitly_plugin  = 'https://wordpress.org/plugins/codehaveli-bitly-url-shortener/';
			// translators: 1. plugin URL 2. admin plugin search URL.
			$bitly_text = __( 'Bit.ly support is provided via the <a href="%1$s">Codehaveli Bitly URL Shortener</a> (<a href="%2$s">Install</a>) plug-in, available from WordPress.org', 'wp-to-twitter' );
			?>
			<p><?php echo wp_kses_post( sprintf( $bitly_text, $bitly_plugin, $install_bitly ) ); ?></p>
			<?php
		}
		if ( '11' === $shortener && ! class_exists( 'Hum' ) ) {
			$install_hum = admin_url( 'plugin-install.php?s=hum+url+shortener+norris&tab=search&type=term' );
			$hum_plugin  = 'https://wordpress.org/plugins/hum/';
			// translators: 1. plugin URL 2. admin plugin search URL.
			$hum_text = __( 'Hum is a custom shortener plug-in. Support is provided via the <a href="%1$s">Hum URL Shortener</a> (<a href="%2$s">Install</a>) plug-in, available from WordPress.org', 'wp-to-twitter' );
			?>
			<p><?php echo wp_kses_post( sprintf( $hum_text, $hum_plugin, $install_hum ) ); ?></p>
			<?php
		}
		?>
		<p>
			<label for="jd_shortener"><?php esc_html_e( 'Choose a URL shortener', 'wp-to-twitter' ); ?></label>
			<select name="jd_shortener" id="jd_shortener">
				<option value="3" <?php selected( $shortener, '3' ); ?>><?php esc_html_e( "Don't shorten URLs.", 'wp-to-twitter' ); ?></option>
				<?php
				$shorteners = wpt_get_shorteners( $shortener );
				foreach ( $shorteners as $id => $info ) {
					if ( 5 === $id && 5 !== $shortener ) {
						continue;
					}
					?>
					<option value="<?php echo absint( $id ); ?>" <?php selected( $shortener, $id ); ?>><?php echo esc_html( $info['label'] ); ?></option>
					<?php
				}
				?>
			</select>
		<?php
		if ( '3' !== $shortener ) {
			?>
			<input type='checkbox' value='false' name='wpt_use_stored_urls' id='wpt_use_stored_urls' <?php checked( get_option( 'wpt_use_stored_urls' ), 'false' ); ?>> <label for='wpt_use_stored_urls'><?php esc_html_e( 'Always request a new short URL for status updates', 'wp-to-twitter' ); ?></label>
			<?php
		}
		?>
		</p>
		<?php
	}
}
