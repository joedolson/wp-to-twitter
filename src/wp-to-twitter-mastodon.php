<?php
/**
 * Connect Mastodon for XPoster
 *
 * @category Mastodon
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.xposterpro.com
 */

/**
 * Update Mastodon settings.
 *
 * @param mixed int/boolean   $auth Author.
 * @param mixed array/boolean $post POST data.
 */
function wpt_update_mastodon_settings( $auth = false, $post = false ) {
	if ( isset( $post['mastodon_settings'] ) ) {
		switch ( $post['mastodon_settings'] ) {
			case 'wtt_oauth_test':
				if ( ! wp_verify_nonce( $post['_wpnonce'], 'wp-to-twitter-nonce' ) && ! $auth ) {
					wp_die( 'Oops, please try again.' );
				}

				if ( ! empty( $post['wpt_mastodon_token'] ) && ! empty( $post['wpt_mastodon_instance'] ) ) {
					$ack = sanitize_text_field( trim( $post['wpt_mastodon_token'] ) );
					$acs = sanitize_text_field( trim( $post['wpt_mastodon_instance'] ) );

					if ( ! $auth ) {
						// If values are filled with asterisks, do not update; these are masked values.
						if ( stripos( $ack, '***' ) === false ) {
							update_option( 'wpt_mastodon_token', $ack );
						}
						if ( stripos( $acs, '***' ) === false ) {
							update_option( 'wpt_mastodon_instance', $acs );
						}
					} else {
						if ( stripos( $ack, '***' ) === false ) {
							update_user_meta( $auth, 'wpt_mastodon_token', $ack );
						}
						if ( stripos( $acs, '***' ) === false ) {
							update_user_meta( $auth, 'wpt_mastodon_instance', $acs );
						}
					}
					$message  = 'failed';
					$validate = array(
						'token'    => $ack,
						'instance' => $acs,
					);
					$verify   = wpt_mastodon_connection( $auth, $validate );
					if ( isset( $verify['username'] ) ) {
						$username = sanitize_text_field( stripslashes( $verify['username'] ) );
						if ( ! $auth ) {
							update_option( 'wpt_mastodon_username', $username );
						} else {
							update_user_meta( $auth, 'wpt_mastodon_username', $username );
						}
						$message = 'success';
						delete_option( 'wpt_curl_error' );
						if ( '1' === get_option( 'wp_debug_oauth' ) ) {
							echo '<br /><strong>Account Verification Data:</strong><br />';
							print_r( $verify );
							echo '</pre>';
						}
					} else {
						$message = 'noconnection';
					}
				} else {
					$message = 'nodata';
				}

				return $message;
				break;
			case 'wtt_mastodon_disconnect':
				if ( ! wp_verify_nonce( $post['_wpnonce'], 'wp-to-twitter-nonce' ) && ! $auth ) {
					wp_die( 'Oops, please try again.' );
				}
				if ( ! $auth ) {
					update_option( 'wpt_mastodon_token', '' );
					update_option( 'wpt_mastodon_instance', '' );
					update_option( 'wpt_mastodon_username', '' );
				} else {
					delete_user_meta( $auth, 'wpt_mastodon_token' );
					delete_user_meta( $auth, 'wpt_mastodon_instance' );
					delete_user_meta( $auth, 'wpt_mastodon_username' );
				}
				$message = 'cleared';

				return $message;
				break;
		}
	}

	return '';
}

/**
 * Connect or disconnect from Mastodon API form.
 *
 * @param mixed int/boolean $auth Current author.
 */
function wtt_connect_mastodon( $auth = false ) {
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
	$connect = wpt_mastodon_connection( $auth );
	if ( ! $connect ) {
		// show notification to authenticate with OAuth. No longer global; settings only.
		if ( ! wpt_check_oauth() && ! ( isset( $_GET['tab'] ) && 'connection' === $_GET['tab'] ) ) {
			$admin_url = admin_url( 'admin.php?page=wp-tweets-pro&tab=mastodon' );
			// Translators: Settings page to authenticate via OAuth.
			$message = sprintf( __( "Mastodon requires authentication. <a href='%s'>Update your settings</a> to enable XPoster to send updates to Mastodon.", 'wp-to-twitter' ), $admin_url );
			echo "<div class='error'><p>$message</p></div>";
		}

		$ack = ( ! $auth ) ? get_option( 'wpt_mastodon_token' ) : get_user_meta( $auth, 'wpt_mastodon_token', true );
		$acs = ( ! $auth ) ? get_option( 'wpt_mastodon_instance' ) : get_user_meta( $auth, 'wpt_mastodon_instance', true );

		$submit = ( ! $auth ) ? '<p class="submit"><input type="submit" name="submit" class="button-primary" value="' . __( 'Connect to Mastodon', 'wp-to-twitter' ) . '" /></p>' : '';
		print( '
			<h3><span>' . __( 'Connect to Mastodon', 'wp-to-twitter' ) . '</span></h3>
			<div class="inside ' . $class . '">
				<ol class="wpt-oauth-settings">
					<li>' . __( 'Navigate to Preferences > Settings > Development in your Mastodon account.', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Click on "New application".', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Name your application.', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Add your website URL', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Add the API Scopes for your application. Minimum required permissions are "write:statuses" and "write:media".', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Submit your application.', 'wp-to-twitter' ) . '</li>
					<li>' . __( 'Select your application from the list.', 'wp-to-twitter' ) . '</li>
			' . $form . '
					<div class="tokens">
					<p>
						<label for="wpt_mastodon_token">' . __( 'Access Token', 'wp-to-twitter' ) . '</label>
						<input type="text" size="45" name="wpt_mastodon_token" id="wpt_mastodon_token" value="' . esc_attr( wpt_mask_attr( $ack ) ) . '" />
					</p>
					<p>
						<label for="wpt_mastodon_instance">' . __( 'Instance URL', 'wp-to-twitter' ) . '</label>
						<input type="text" size="45" name="wpt_mastodon_instance" id="wpt_mastodon_instance" placeholder="https://toot.io" value="' . esc_attr( $acs ) . '" />
					</p>
					</div>
				</ol>
				' . $submit . '
				<input type="hidden" name="mastodon_settings" value="wtt_oauth_test" class="hidden" />
				' . $nonce . '
			</div>' );
	} elseif ( $connect ) {
		$ack   = ( ! $auth ) ? get_option( 'wpt_mastodon_token' ) : get_user_meta( $auth, 'wpt_mastodon_token', true );
		$acs   = ( ! $auth ) ? get_option( 'wpt_mastodon_instance' ) : get_user_meta( $auth, 'wpt_mastodon_instance', true );
		$uname = ( ! $auth ) ? get_option( 'wpt_mastodon_username' ) : get_user_meta( $auth, 'wpt_mastodon_username', true );
		$nonce = ( ! $auth ) ? wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false ) . '</form>' : '';
		$site  = get_bloginfo( 'name' );

		if ( ! $auth ) {
			// Translators: Name of the current site.
			$submit = '<input type="submit" name="submit" class="button-primary" value="' . sprintf( __( 'Disconnect %s from Mastodon', 'wp-to-twitter' ), $site ) . '" />
					<input type="hidden" name="mastodon_settings" value="wtt_mastodon_disconnect" class="hidden" />';
		} else {
			$submit = '<input type="checkbox" name="mastodon_settings" value="wtt_mastodon_disconnect" id="disconnect" /> <label for="disconnect">' . __( 'Disconnect Your Account from Mastodon', 'wp-to-twitter' ) . '</label>';
		}

		print( '
			<h3>' . __( 'Mastodon Connection', 'wp-to-twitter' ) . '</h3>
			<div class="inside ' . $class . '">
			' . $information . $form . '
				<div id="wtt_authentication_display">
					<ul>
						<li><strong class="auth_label">' . __( 'Username ', 'wp-to-twitter' ) . '</strong> <code class="auth_code"><a href="' . esc_url( $acs ) . '/@' . esc_attr( $uname ) . '">' . esc_attr( $uname ) . '</a></code></li>
						<li><strong class="auth_label">' . __( 'Access Token ', 'wp-to-twitter' ) . '</strong> <code class="auth_code">' . esc_attr( wpt_mask_attr( $ack ) ) . '</code></li>
						<li><strong class="auth_label">' . __( 'Mastodon Instance', 'wp-to-twitter' ) . '</strong> <code class="auth_code">' . esc_attr( $acs ) . '</code></li>
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
