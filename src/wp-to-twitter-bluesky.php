<?php
/**
 * Connect Bluesky for XPoster
 *
 * @category Bluesky
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.xposterpro.com
 */

/**
 * Update Bluesky settings.
 *
 * @param mixed int/boolean   $auth Author.
 * @param mixed array/boolean $post POST data.
 */
function wpt_update_bluesky_settings( $auth = false, $post = false ) {
	if ( isset( $post['bluesky_settings'] ) ) {
		switch ( $post['bluesky_settings'] ) {
			case 'wtt_oauth_test':
				if ( ! wp_verify_nonce( $post['_wpnonce'], 'wp-to-twitter-nonce' ) && ! $auth ) {
					wp_die( 'Oops, please try again.' );
				}

				if ( ! empty( $post['wpt_bluesky_token'] ) ) {
					$ack  = sanitize_text_field( trim( $post['wpt_bluesky_token'] ) );
					$user = sanitize_text_field( trim( $post['wpt_bluesky_username'] ) );

					if ( ! $auth ) {
						// If values are filled with asterisks, do not update; these are masked values.
						if ( stripos( $ack, '***' ) === false ) {
							update_option( 'wpt_bluesky_token', $ack );
							update_option( 'wpt_bluesky_username', $user );
						}
					} else {
						if ( stripos( $ack, '***' ) === false ) {
							update_user_meta( $auth, 'wpt_bluesky_token', $ack );
							update_user_meta( $auth, 'wpt_bluesky_username', $user );
						}
					}
					$message  = 'failed';
					$validate = array(
						'password'   => $ack,
						'identifier' => $user,
					);
					$verify   = wpt_bluesky_connection( $auth, $validate );
					if ( '1' === get_option( 'wp_debug_oauth' ) ) {
						echo '<br /><strong>Account Verification Data:</strong><br /><pre>';
						print_r( $verify );
						echo '</pre>';
					}
					if ( isset( $verify['active'] ) && $verify['active'] ) {
						$message = 'success';
						delete_option( 'wpt_curl_error' );

					} else {
						$message = 'noconnection';
					}
				} else {
					$message = 'nodata';
				}

				return $message;
				break;
			case 'wtt_bluesky_disconnect':
				if ( ! wp_verify_nonce( $post['_wpnonce'], 'wp-to-twitter-nonce' ) && ! $auth ) {
					wp_die( 'Oops, please try again.' );
				}
				if ( ! $auth ) {
					update_option( 'wpt_bluesky_token', '' );
					update_option( 'wpt_bluesky_username', '' );
				} else {
					delete_user_meta( $auth, 'wpt_bluesky_token' );
					delete_user_meta( $auth, 'wpt_bluesky_username' );
				}
				$message = 'cleared';

				return $message;
				break;
		}
	}

	return '';
}

/**
 * Connect or disconnect from Bluesky API form.
 *
 * @param mixed int/boolean $auth Current author.
 */
function wtt_connect_bluesky( $auth = false ) {
	if ( ! $auth ) {
		echo '<div class="ui-sortable meta-box-sortables">';
		echo '<div class="postbox">';
	}
	$information = '';
	if ( $auth ) {
		wpt_update_authenticated_users();
	}

	$class   = ( $auth ) ? 'wpt-profile' : 'wpt-settings';
	$form    = ( ! $auth ) ? '<form action="" method="post" class="wpt-connection-form">' : '';
	$nonce   = ( ! $auth ) ? wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false ) . '</form>' : '';
	$connect = wpt_bluesky_connection( $auth );
	if ( ! $connect ) {
		$ack    = ( ! $auth ) ? get_option( 'wpt_bluesky_token' ) : get_user_meta( $auth, 'wpt_bluesky_token', true );
		$user   = ( ! $auth ) ? get_option( 'wpt_bluesky_username' ) : get_user_meta( $auth, 'wpt_bluesky_username', true );
		$submit = ( ! $auth ) ? '<p class="submit"><input type="submit" name="submit" class="button-primary" value="' . __( 'Connect to Bluesky', 'wp-to-twitter' ) . '" /></p>' : '';
		print( '
			<h3 class="wpt-has-link"><span>' . __( 'Connect to Bluesky', 'wp-to-twitter' ) . '</span> <a href="https://xposterpro.com/connecting-xposter-and-bluesky/" class="button button-secondary">' . __( 'Instructions', 'wp-to-twitter' ) . '</a></h3>
			<div class="inside ' . $class . '">
			' . $form . '
				<ol class="wpt-oauth-settings">
					<li>' . __( 'Navigate to Settings > Privacy and Security > App passwords in your Bluesky account.', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Click on "Add App Password".', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Name your app password.', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Copy your App Password.', 'wp-to-twitter' ) . '
					<li>' . __( 'Add your App Password and Bluesky Handle to setings', 'wp-to-twitter' ) . '
					<div class="tokens auth-fields">
					<p>
						<label for="wpt_bluesky_token">' . __( 'App Password', 'wp-to-twitter' ) . '</label>
						<input type="text" size="45" name="wpt_bluesky_token" id="wpt_bluesky_token" value="' . esc_attr( wpt_mask_attr( $ack ) ) . '" />
					</p>
					<p>
						<label for="wpt_bluesky_username">' . __( 'Bluesky Handle', 'wp-to-twitter' ) . '</label>
						<input type="text" size="45" name="wpt_bluesky_username" id="wpt_bluesky_username" value="' . esc_attr( wpt_mask_attr( $user ) ) . '" />
					</p>
					</div></li>
				</ol>
				' . $submit . '
				<input type="hidden" name="bluesky_settings" value="wtt_oauth_test" class="hidden" />
				' . $nonce . '
			</div>' );
	} elseif ( $connect ) {
		$ack   = ( ! $auth ) ? get_option( 'wpt_bluesky_token' ) : get_user_meta( $auth, 'wpt_bluesky_token', true );
		$uname = ( ! $auth ) ? get_option( 'wpt_bluesky_username' ) : get_user_meta( $auth, 'wpt_bluesky_username', true );
		$nonce = ( ! $auth ) ? wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false ) . '</form>' : '';
		$site  = get_bloginfo( 'name' );

		if ( ! $auth ) {
			// Translators: Name of the current site.
			$submit = '<input type="submit" name="submit" class="button-primary" value="' . sprintf( __( 'Disconnect %s from Bluesky', 'wp-to-twitter' ), $site ) . '" />
					<input type="hidden" name="bluesky_settings" value="wtt_bluesky_disconnect" class="hidden" />';
		} else {
			$submit = '<input type="checkbox" name="bluesky_settings" value="wtt_bluesky_disconnect" id="disconnect" /> <label for="disconnect">' . __( 'Disconnect Your Account from Bluesky', 'wp-to-twitter' ) . '</label>';
		}

		print( '
			<h3>' . __( 'Bluesky Connection', 'wp-to-twitter' ) . '</h3>
			<div class="inside ' . $class . '">
			' . $information . $form . '
				<div id="wtt_authentication_display">
					<ul>
						<li><strong class="auth_label">' . __( 'Username ', 'wp-to-twitter' ) . '</strong> <code class="auth_code"><a href="https://bsky.app/profile/' . esc_attr( $uname ) . '">' . esc_attr( $uname ) . '</a></code></li>
						<li><strong class="auth_label">' . __( 'Access Token ', 'wp-to-twitter' ) . '</strong> <code class="auth_code">' . esc_attr( wpt_mask_attr( $ack ) ) . '</code></li>
					</ul>
					<div>
					' . $submit . '
					</div>
				</div>
				' . $nonce . '
			</div>' );

	}
	if ( ! $auth ) {
		echo '</div>
		</div>';
	}
}
