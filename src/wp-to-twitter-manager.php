<?php
/**
 * XPoster Settings page
 *
 * @category Settings
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update XPoster settings.
 */
function wpt_updated_settings() {
	if ( empty( $_POST ) ) {
		return;
	}

	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'wp-to-twitter-nonce' ) ) {
		wp_die( 'XPoster: Security check failed' );
	}
	$oauth_message    = '';
	$mastodon_message = '';
	$bluesky_message  = '';
	// Connect to Twitter.
	if ( isset( $_POST['oauth_settings'] ) ) {
		$post          = map_deep( $_POST, 'sanitize_text_field' );
		$oauth_message = wpt_update_oauth_settings( false, $post );
	}
	// Connect to Mastodon.
	if ( isset( $_POST['mastodon_settings'] ) ) {
		$post             = map_deep( $_POST, 'sanitize_text_field' );
		$mastodon_message = wpt_update_mastodon_settings( false, $post );
	}
	// Connect to Bluesky.
	if ( isset( $_POST['bluesky_settings'] ) ) {
		$post            = map_deep( $_POST, 'sanitize_text_field' );
		$bluesky_message = wpt_update_bluesky_settings( false, $post );
	}
	$message = '';

	// notifications from oauth connection.
	if ( isset( $_POST['oauth_settings'] ) ) {
		if ( 'success' === $oauth_message ) {
			$admin_url = admin_url( 'admin.php?page=wp-tweets-pro?tab=basic' );

			print( '
				<div id="message" class="updated fade">
					<p>' . __( 'XPoster is now connected with X.com.', 'wp-to-twitter' ) . " <a href='$admin_url'>" . __( 'Configure your status update templates', 'wp-to-twitter' ) . '</a></p>
				</div>
			' );
		} elseif ( 'failed' === $oauth_message ) {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'XPoster failed to connect with X.com.', 'wp-to-twitter' ) . ' <strong>' . __( 'Error:', 'wp-to-twitter' ) . '</strong> ' . get_option( 'wpt_error' ) . '</p>
				</div>
			' );
		} elseif ( 'cleared' === $oauth_message ) {
			print( '
				<div id="message" class="updated fade">
					<p>' . __( 'OAuth Authentication Data Cleared.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		} elseif ( 'nosync' === $oauth_message ) {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'OAuth Authentication Failed. Your server time is not in sync with the X.com servers. Talk to your hosting service to see what can be done.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		} elseif ( 'noconnection' === $oauth_message ) {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'OAuth Authentication Failed. XPoster was unable to complete a connection with those credentials.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		} else {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'OAuth Authentication response not understood.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		}
	}

	// notifications from Mastodon connection.
	if ( isset( $_POST['mastodon_settings'] ) ) {
		if ( 'success' === $mastodon_message ) {
			$admin_url = admin_url( 'admin.php?page=wp-tweets-pro?tab=basic' );

			print( '
				<div id="message" class="updated fade">
					<p>' . __( 'XPoster is now connected to your Mastodon instance.', 'wp-to-twitter' ) . " <a href='$admin_url'>" . __( 'Configure your status update templates', 'wp-to-twitter' ) . '</a></p>
				</div>
			' );
		} elseif ( 'failed' === $mastodon_message ) {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'XPoster failed to connect with your Mastodon instance.', 'wp-to-twitter' ) . ' <strong>' . __( 'Error:', 'wp-to-twitter' ) . '</strong> ' . get_option( 'wpt_error' ) . '</p>
				</div>
			' );
		} elseif ( 'cleared' === $mastodon_message ) {
			print( '
				<div id="message" class="updated fade">
					<p>' . __( 'Mastodon authentication data cleared.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		} elseif ( 'noconnection' === $mastodon_message ) {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'Mastodon authentication Failed. XPoster was unable to complete a connection with those credentials.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		} else {
			print( '
				<div id="message" class="error fade">
					<p>' . __( 'Mastodon authentication response not understood.', 'wp-to-twitter' ) . '</p>
				</div>
			' );
		}
	}

	// notifications from Bluesky connection.
	if ( isset( $_POST['bluesky_settings'] ) ) {
		if ( 'success' === $bluesky_message ) {
			$admin_url = admin_url( 'admin.php?page=wp-tweets-pro?tab=basic' );
			wp_admin_notice(
				__( 'XPoster is now connected to your Bluesky account.', 'wp-to-twitter' ) . " <a href='$admin_url'>" . __( 'Configure your status update templates', 'wp-to-twitter' ) . '</a>',
				array(
					'type' => 'notice',
				)
			);
		} elseif ( 'failed' === $bluesky_message ) {
			wp_admin_notice(
				__( 'XPoster failed to connect with your Bluesky account.', 'wp-to-twitter' ) . ' <strong>' . __( 'Error:', 'wp-to-twitter' ) . '</strong> ' . get_option( 'wpt_error' ),
				array(
					'type' => 'error',
				)
			);
		} elseif ( 'cleared' === $bluesky_message ) {
			wp_admin_notice(
				__( 'Bluesky authentication data cleared.', 'wp-to-twitter' ),
				array(
					'type' => 'notice',
				)
			);
		} elseif ( 'noconnection' === $bluesky_message ) {
			wp_admin_notice(
				__( 'Bluesky authentication Failed. XPoster was unable to complete a connection with those credentials.', 'wp-to-twitter' ),
				array(
					'type' => 'error',
				)
			);
		} else {
			wp_admin_notice(
				__( 'Bluesky authentication response not understood.', 'wp-to-twitter' ),
				array(
					'type' => 'error',
				)
			);
		}
	}

	if ( isset( $_POST['submit-type'] ) && 'advanced' === $_POST['submit-type'] ) {
		$default      = ( isset( $_POST['jd_tweet_default'] ) ) ? sanitize_textarea_field( $_POST['jd_tweet_default'] ) : 0;
		$default_edit = ( isset( $_POST['jd_tweet_default_edit'] ) ) ? sanitize_textarea_field( $_POST['jd_tweet_default_edit'] ) : 0;
		update_option( 'jd_tweet_default', $default );
		update_option( 'jd_tweet_default_edit', $default_edit );

		if ( isset( $_POST['wpt_rate_limiting'] ) && '1' !== get_option( 'wpt_rate_limiting' ) ) {
			$extend = __( 'Rate Limiting is enabled. Default rate limits are set at 10 posts per category/term per hour. <a href="#special_cases">Edit global default</a> or edit individual terms to customize limits for each category or taxonomy term.', 'wp-to-twitter' );
			wp_schedule_event( time() + 3600, 'hourly', 'wptratelimits' );
		} else {
			$extend = '';
			wp_clear_scheduled_hook( 'wptratelimits' );
		}

		update_option( 'wpt_rate_limiting', ( isset( $_POST['wpt_rate_limiting'] ) ) ? 1 : 0 );
		update_option( 'wpt_inline_edits', ( isset( $_POST['wpt_inline_edits'] ) ) ? 1 : 0 );
		update_option( 'jd_twit_custom_url', sanitize_text_field( $_POST['jd_twit_custom_url'] ) );
		update_option( 'wpt_default_rate_limit', ( isset( $_POST['wpt_default_rate_limit'] ) ? intval( $_POST['wpt_default_rate_limit'] ) : false ) );
		update_option( 'jd_strip_nonan', ( isset( $_POST['jd_strip_nonan'] ) ) ? 1 : 0 );
		update_option( 'jd_twit_prepend', sanitize_text_field( $_POST['jd_twit_prepend'] ) );
		update_option( 'jd_twit_append', sanitize_text_field( $_POST['jd_twit_append'] ) );
		update_option( 'jd_post_excerpt', (int) $_POST['jd_post_excerpt'] );
		update_option( 'jd_max_tags', (int) $_POST['jd_max_tags'] );
		$use_cats = ( isset( $_POST['wpt_use_cats'] ) ) ? 1 : 0;
		update_option( 'wpt_use_cats', $use_cats );
		update_option( 'wpt_tag_source', ( ( isset( $_POST['wpt_tag_source'] ) && 'slug' === $_POST['wpt_tag_source'] ) ? 'slug' : '' ) );
		update_option( 'jd_max_characters', (int) $_POST['jd_max_characters'] );
		update_option( 'jd_replace_character', ( isset( $_POST['jd_replace_character'] ) ? sanitize_text_field( $_POST['jd_replace_character'] ) : '' ) );
		update_option( 'jd_date_format', sanitize_text_field( $_POST['jd_date_format'] ) );
		update_option( 'jd_dynamic_analytics', sanitize_text_field( $_POST['jd-dynamic-analytics'] ) );

		$twitter_analytics = ( isset( $_POST['twitter-analytics'] ) ) ? absint( $_POST['twitter-analytics'] ) : 0;
		if ( 1 === (int) $twitter_analytics ) {
			update_option( 'use_dynamic_analytics', 0 );
			update_option( 'use-twitter-analytics', 1 );
			update_option( 'no-analytics', 0 );
		} elseif ( 2 === (int) $twitter_analytics ) {
			update_option( 'use_dynamic_analytics', 1 );
			update_option( 'use-twitter-analytics', 0 );
			update_option( 'no-analytics', 0 );
		} else {
			update_option( 'use_dynamic_analytics', 0 );
			update_option( 'use-twitter-analytics', 0 );
			update_option( 'no-analytics', 1 );
		}

		update_option( 'twitter-analytics-campaign', sanitize_text_field( $_POST['twitter-analytics-campaign'] ) );

		if ( isset( $_POST['wpt_caps'] ) ) {
			$perms = map_deep( $_POST['wpt_caps'], 'sanitize_text_field' );
			$caps  = array( 'wpt_twitter_oauth', 'wpt_twitter_custom', 'wpt_twitter_switch', 'wpt_can_tweet', 'wpt_tweet_now' );
			foreach ( $perms as $key => $value ) {
				$role = get_role( $key );
				if ( is_object( $role ) ) {
					foreach ( $caps as $v ) {
						if ( isset( $value[ $v ] ) ) {
							$role->add_cap( $v );
						} else {
							$role->remove_cap( $v );
						}
					}
				}
			}
		}

		update_option( 'wp_debug_oauth', ( isset( $_POST['wp_debug_oauth'] ) ) ? 1 : 0 );
		update_option( 'wpt_debug_tweets', ( isset( $_POST['wpt_debug_tweets'] ) ) ? 1 : 0 );
		$wpt_truncation_order = isset( $_POST['wpt_truncation_order'] ) ? $_POST['wpt_truncation_order'] : array();
		$wpt_truncation_order = map_deep( $wpt_truncation_order, 'sanitize_text_field' );
		update_option( 'wpt_truncation_order', $wpt_truncation_order );
		$message .= __( 'XPoster Advanced Options Updated', 'wp-to-twitter' ) . '. ' . $extend;
	}

	if ( isset( $_POST['submit-type'] ) && 'options' === $_POST['submit-type'] ) {
		// UPDATE OPTIONS.
		$wpt_settings = get_option( 'wpt_post_types' );
		if ( ! is_array( $wpt_settings ) ) {
			$wpt_settings = array();
		}

		$keys   = array();
		$values = array();
		foreach ( $_POST['wpt_post_types'] as $key => $value ) {
			$value = map_deep( $value, 'sanitize_textarea_field' );
			// using wp_encode_emoji allows me to save emoji in templates.
			// ...but I haven't found a way to convert the saved emoji *back* to unicode.
			// sending the HTML entity just yields a broken character on X.com.
			$array = array(
				'post-published-update' => ( isset( $value['post-published-update'] ) ) ? $value['post-published-update'] : '',
				'post-published-text'   => $value['post-published-text'],
				'post-edited-update'    => ( isset( $value['post-edited-update'] ) ) ? $value['post-edited-update'] : '',
				'post-edited-text'      => $value['post-edited-text'],
			);
			array_push( $keys, $key );
			array_push( $values, $array );
		}

		$wpt_settings = array_combine( $keys, $values );
		update_option( 'wpt_post_types', $wpt_settings );
		$newlink_published_text = ( isset( $_POST['newlink-published-text'] ) ) ? sanitize_text_field( $_POST['newlink-published-text'] ) : '';
		update_option( 'newlink-published-text', $newlink_published_text );
		update_option( 'jd_twit_blogroll', ( isset( $_POST['jd_twit_blogroll'] ) ) ? 1 : '' );
		$message  = wpt_select_shortener( map_deep( $_POST, 'sanitize_text_field' ) );
		$message .= __( 'XPoster Options Updated', 'wp-to-twitter' );
		$message  = apply_filters( 'wpt_settings', $message, $_POST );
	}

	if ( isset( $_POST['wpt_shortener_update'] ) && 'true' === $_POST['wpt_shortener_update'] ) {
		$message = wpt_shortener_update( map_deep( $_POST, 'sanitize_text_field' ) );
	}

	// Check whether the server has supported for needed functions.
	if ( isset( $_POST['submit-type'] ) && 'check-support' === $_POST['submit-type'] ) {
		$service = ( isset( $_POST['bluesky'] ) ) ? 'bluesky' : 'xcom';
		$service = ( isset( $_POST['mastodon'] ) ) ? 'mastodon' : $service;
		$message = wpt_check_functions( $service );
	}

	if ( $message ) {
		echo '<div id="message" class="updated is-dismissible"><p>' . $message . '</p></div>';
	}
}

/**
 * Build array of post types eligible for XPoster to send updates for.
 *
 * @return array
 */
function wpt_possible_post_types() {
	$post_types = get_post_types( array(), 'objects' );
	$exclusions = array( 'wp_navigation', 'wp_block', 'attachment', 'nav_menu_item', 'revision' );
	/**
	 * Exclude post types from the list of available types to post to X.com.
	 *
	 * @hook wpt_exclude_post_types
	 *
	 * @param {array} $exclusions Array of post type name slugs to exclude.
	 *
	 * @return {array}
	 */
	$excluded = apply_filters( 'wpt_exclude_post_types', $exclusions );
	$return   = array();
	foreach ( $post_types as $type ) {
		// If post type is both private & has no UI, don't show.
		if ( false === $type->public && false === $type->show_ui || in_array( $type->name, $excluded, true ) ) {
			continue;
		}
		$return[] = $type;
	}

	return $return;
}

/**
 * Show XPoster settings form.
 */
function wpt_update_settings() {
	?>
	<div class="wrap" id="wp-to-twitter">
	<?php
	if ( defined( 'WPT_STAGING_MODE' ) && true === WPT_STAGING_MODE ) {
		echo "<div class='updated notice'><p>" . __( 'XPoster is in staging mode. Status updates will be reported as if successfully sent, but will not be posted.', 'wp-to-twitter' ) . '</p></div>';
	}
	wpt_updated_settings();
	wpt_show_last_update();
	wpt_handle_errors();
	if ( ! function_exists( 'wpt_pro_exists' ) ) {
		?>
	<aside class="xposter-sales"><p class="link-highlight">
		<?php
			// Translators: URL to purchase.
			printf( __( 'Buy <strong>XPoster Pro</strong> &mdash; supercharge your social media! <a href="%s">Buy Now</a>', 'wp-to-twitter' ), 'https://xposterpro.com/awesome/xposter-pro/' );
		?>
		</p></aside>
		<?php
	}
	?>
	<h1><?php _e( 'XPoster Options', 'wp-to-twitter' ); ?></h1>

	<?php wpt_max_length(); ?>

	<nav class='nav-tab-wrapper' aria-labelledby="wpt-nav">
		<h2 id="wpt-nav" class="screen-reader-text"><?php _e( 'XPoster Settings', 'wp-to-twitter' ); ?></h2>
		<?php wpt_settings_tabs(); ?>
	</nav>
	<div id="wpt_settings_page" class="postbox-container jcd-wide">
	<div class="metabox-holder">

	<?php
		$default = ( '' === get_option( 'wtt_twitter_username', '' ) ) ? 'connection' : 'basic';
		$current = ( isset( $_GET['tab'] ) ) ? sanitize_text_field( $_GET['tab'] ) : $default;
	if ( 'connection' === $current ) {
		if ( function_exists( 'wtt_connect_oauth' ) ) {
			wtt_connect_oauth();
		}
	}
	if ( 'mastodon' === $current ) {
		if ( function_exists( 'wtt_connect_mastodon' ) ) {
			wtt_connect_mastodon();
		}
	}
	if ( 'bluesky' === $current ) {
		if ( function_exists( 'wtt_connect_bluesky' ) ) {
			wtt_connect_bluesky();
		}
	}
	if ( 'pro' === $current ) {
		if ( function_exists( 'wpt_pro_functions' ) ) {
			wpt_pro_functions();
			if ( function_exists( 'wpt_notes' ) ) {
				wpt_notes();
			}
		} else {
			if ( ! function_exists( 'wpt_pro_exists' ) ) {
				?>
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<div class="inside purchase">
							<h3><strong><?php _e( 'XPoster Pro', 'wp-to-twitter' ); ?></strong></h3>
							<p>
								Are you wasting time switching between social media and WordPress to promote your posts? Do you have to delete updates because you accidentally published a post? Do you want to be able to schedule your post to send next week, directly from your post editor? XPoster Pro will help you out!
							</p>
							<h3>What will XPoster PRO do for you?</h3>
							<p>
								It takes the great automation from XPoster and turns it up to eleven: publish to unique accounts for each site author; schedule up to 3 re-posts at an interval of your choice; and, with a delay between publishing and your status updates, check your status before it's shared with your followers.
							</p>
							<p class="link-highlight">
								<a href="https://xposterpro.com/awesome/xposter-pro/">Upgrade to XPoster Pro</a>
							</p>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}
	if ( 'basic' === $current ) {
		?>
	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h3><span><?php _e( 'Status Update Templates', 'wp-to-twitter' ); ?></span></h3>

			<div class="inside wpt-settings">
				<form method="post" action="">
					<?php
					$nonce = wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false );
					echo "<div>$nonce</div>";
					?>
					<div>
						<?php
						echo apply_filters( 'wpt_tweet_length', '' );
						echo apply_filters( 'wpt_auto_tweet', '' );
						echo apply_filters( 'wpt_pick_shortener', '' );
						$post_types   = wpt_possible_post_types();
						$wpt_settings = get_option( 'wpt_post_types' );
						$tabs         = "<ul class='tabs' role='tablist'>";
						foreach ( $post_types as $type ) {
							$name  = $type->labels->name;
							$slug  = $type->name;
							$tabs .= "<li><a href='#wpt_$slug' role='tab' id='tab_wpt_$slug' aria-controls='wpt_$slug'>$name</a></li>";
						}
						if ( '1' === get_option( 'link_manager_enabled' ) || true === apply_filters( 'pre_option_link_manager_enabled', false ) ) {
							$tabs .= "<li><a href='#wpt_links' id='tab_wpt_links' aria-controls='wpt_links'>" . __( 'Links', 'wp-to-twitter' ) . '</a></li>';
						}
						$tabs .= '</ul>';
						echo $tabs;
						foreach ( $post_types as $type ) {
							$name = $type->labels->name;
							$slug = $type->name;
							?>
							<div class='wptab wpt_types wpt_<?php echo esc_attr( $slug ); ?>' aria-labelledby='tab_wpt_<?php echo esc_attr( $slug ); ?>' role="tabpanel" id='wpt_<?php echo esc_attr( $slug ); ?>'>
							<fieldset>
								<legend class="screen-reader-text"><?php _e( 'Status Templates', 'wp-to-twitter' ); ?></legend>
								<p>
									<input type="checkbox" name="wpt_post_types[<?php echo esc_attr( $slug ); ?>][post-published-update]" id="<?php echo esc_attr( $slug ); ?>-post-published-update" value="1" <?php echo wpt_checkbox( 'wpt_post_types', $slug, 'post-published-update' ); ?> />
									<label for="<?php echo esc_attr( $slug ); ?>-post-published-update"><strong>
									<?php
									// Translators: post type.
									printf( __( 'Update when %s are published', 'wp-to-twitter' ), $name );
									?>
									</strong></label>
									<label for="<?php echo $slug; ?>-post-published-text"><br/>
									<?php
									// Translators: post type.
									printf( __( 'Template for new %s', 'wp-to-twitter' ), $name );
									?>
									</label><br/>
									<textarea class="wpt-template widefat" name="wpt_post_types[<?php echo esc_attr( $slug ); ?>][post-published-text]" id="<?php echo esc_attr( $slug ); ?>-post-published-text" cols="60" rows="3"><?php echo ( isset( $wpt_settings[ $slug ] ) ) ? esc_attr( stripslashes( $wpt_settings[ $slug ]['post-published-text'] ) ) : ''; ?></textarea>
								</p>
								<p>
									<input type="checkbox" name="wpt_post_types[<?php echo esc_attr( $slug ); ?>][post-edited-update]" id="<?php echo esc_attr( $slug ); ?>-post-edited-update" value="1" <?php echo wpt_checkbox( 'wpt_post_types', $slug, 'post-edited-update' ); ?> />
									<label for="<?php echo esc_attr( $slug ); ?>-post-edited-update"><strong>
									<?php
									// Translators: post type name.
									printf( __( 'Update when %s are edited', 'wp-to-twitter' ), $name );
									?>
									</strong></label><br/><label for="<?php echo esc_attr( $slug ); ?>-post-edited-text">
									<?php
									// Translators: post type name.
									printf( __( 'Template for %1$s edits', 'wp-to-twitter' ), $name );
									?>
									</label><br/>
									<textarea class="wpt-template widefat" name="wpt_post_types[<?php echo esc_attr( $slug ); ?>][post-edited-text]" id="<?php echo esc_attr( $slug ); ?>-post-edited-text" cols="60" rows="3"><?php echo ( isset( $wpt_settings[ $slug ] ) ) ? esc_attr( stripslashes( $wpt_settings[ $slug ]['post-edited-text'] ) ) : ''; ?></textarea>
								</p>
							</fieldset>
							<?php
							if ( function_exists( 'wpt_list_terms' ) ) {
								wpt_list_terms( $slug, $name );
							}
							?>
							</div>
							<?php
						}
						if ( '1' === get_option( 'link_manager_enabled' ) || true === apply_filters( 'pre_option_link_manager_enabled', false ) ) {
							?>
						<div class='wptab wpt_types wpt_links' id="wpt_links">
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e( 'Links', 'wp-to-twitter' ); ?></span></legend>
								<p>
									<input type="checkbox" name="jd_twit_blogroll" id="jd_twit_blogroll" value="1" <?php echo wpt_checkbox( 'jd_twit_blogroll' ); ?> />
									<label for="jd_twit_blogroll"><strong><?php _e( 'Send status update when you post a link', 'wp-to-twitter' ); ?></strong></label><br/>
									<label for="newlink-published-text"><?php _e( 'Text for new link updates:', 'wp-to-twitter' ); ?></label>
									<input aria-describedby="newlink-published-text-label" type="text" class="wpt-template" name="newlink-published-text" id="newlink-published-text" class="widefat" maxlength="120" value="<?php echo esc_attr( stripslashes( get_option( 'newlink-published-text' ) ) ); ?>"/><br/><span id="newlink-published-text-label"><?php _e( 'Available shortcodes: <code>#url#</code>, <code>#title#</code>, and <code>#description#</code>.', 'wp-to-twitter' ); ?></span>
								</p>
							</fieldset>
						</div>
							<?php
						}
						?>
						<div>
							<input type="hidden" name="submit-type" value="options" />
						</div>
						<input type="submit" name="submit" value="<?php esc_attr_e( 'Save XPoster Options', 'wp-to-twitter' ); ?>" class="button-primary" />
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><span><?php _e( 'Status Template Tags', 'wp-to-twitter' ); ?></span></h3>
				<div class="inside">
					<ul>
						<li><?php _e( '<code>#title#</code>: the title of your blog post', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#blog#</code>: the title of your blog', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#post#</code>: a short excerpt of the post content', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#category#</code>: the first selected category for the post', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#cat_desc#</code>: custom value from the category description field', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#date#</code>: the post date', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#modified#</code>: the post modified date', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#url#</code>: the post URL', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#longurl#</code>: the unshortened post URL', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#author#</code>: the post author (@reference if available, otherwise display name)', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#displayname#</code>: post author\'s display name', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#account#</code>: the twitter @reference for the account (or the author, if author settings are enabled and set.)', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#@#</code>: the twitter @reference for the author or blank, if not set', 'wp-to-twitter' ); ?></li>
						<li><?php _e( '<code>#tags#</code>: your tags modified into hashtags.', 'wp-to-twitter' ); ?></li>
						<?php
						if ( function_exists( 'wpt_pro_exists' ) && true === wpt_pro_exists() ) {
							?>
						<li><?php _e( '<code>#reference#</code>: Used only in co-tweeting. @reference to main account when posted to author account, @reference to author account in post to main account.', 'wp-to-twitter' ); ?></li>
							<?php
						}
						?>
					</ul>
					<p>
					<?php _e( 'Create custom shortcodes and access WordPress custom fields by using square brackets and the name of your custom field.', 'wp-to-twitter' ); ?>
					<br />
					<?php _e( '<strong>Example:</strong> <code>[[custom_field]]</code>', 'wp-to-twitter' ); ?>
					</p>
					<p>
					<?php _e( 'Create custom shortcodes and access the post author\'s custom user meta fields by using curly brackets and the name of the custom field.', 'wp-to-twitter' ); ?>
					<br />
					<?php _e( '<strong>Example:</strong> <code>{{user_meta}}</code>', 'wp-to-twitter' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
	if ( 'shortener' === $current ) {
		echo apply_filters( 'wpt_shortener_controls', '' );
	}

	if ( 'advanced' === $current ) {
		?>
	<form method="post" action="">
	<div class="ui-sortable meta-box-sortables">
		<div class="postbox">
			<h3><span><?php _e( 'Tag Settings', 'wp-to-twitter' ); ?></span></h3>
			<div class="inside">
					<div>
						<?php
							$nonce = wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false );
							echo "<div>$nonce</div>";
						?>

						<fieldset>
							<legend class='screen-reader-text'><?php _e( 'Hashtags', 'wp-to-twitter' ); ?></legend>
							<p>
								<input type="checkbox" name="jd_strip_nonan" id="jd_strip_nonan" value="1" <?php echo checked( get_option( 'jd_strip_nonan' ), 1 ); ?> /> <label for="jd_strip_nonan"><?php _e( 'Strip nonalphanumeric characters from tags', 'wp-to-twitter' ); ?></label>
							</p>
							<p>
								<input type="checkbox" name="wpt_tag_source" id="wpt_tag_source" value="slug" <?php checked( get_option( 'wpt_tag_source' ), 'slug' ); ?> />
								<label for="wpt_tag_source"><?php _e( 'Use tag slug as hashtag value', 'wp-to-twitter' ); ?></label><br/>
							</p>
							<p>
								<input type="checkbox" name="wpt_use_cats" id="wpt_use_cats" value="1" <?php checked( get_option( 'wpt_use_cats' ), '1' ); ?> />
								<label for="wpt_use_cats"><?php _e( 'Use categories instead of tags', 'wp-to-twitter' ); ?></label><br/>
							</p>
							<?php
							if ( ! ( '[ ]' === get_option( 'jd_replace_character' ) || '' === get_option( 'jd_replace_character', '' ) ) ) {
								?>
							<p>
								<label for="jd_replace_character"><?php _e( 'Spaces in tags replaced with:', 'wp-to-twitter' ); ?></label>
								<input type="text" name="jd_replace_character" id="jd_replace_character" value="<?php echo esc_attr( get_option( 'jd_replace_character' ) ); ?>" size="3"/>
							</p>
								<?php
							}
							?>
							<p>
								<label for="jd_max_tags"><?php _e( 'Maximum number of tags to include:', 'wp-to-twitter' ); ?></label>
								<input aria-describedby="jd_max_characters_label" type="text" name="jd_max_tags" id="jd_max_tags" value="<?php echo esc_attr( get_option( 'jd_max_tags' ) ); ?>" size="3" />
							</p>
							<p>
								<label for="jd_max_characters"><?php _e( 'Maximum length in characters for included tags:', 'wp-to-twitter' ); ?></label>
								<input type="text" name="jd_max_characters" id="jd_max_characters" value="<?php echo esc_attr( get_option( 'jd_max_characters' ) ); ?>" size="3"/>
							</p>
						</fieldset>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Template Settings', 'wp-to-twitter' ); ?></span></h3>
						<div class="inside">
						<fieldset>
							<legend class='screen-reader-text'><?php _e( 'Template Settings', 'wp-to-twitter' ); ?></legend>
							<p>
								<label for="jd_post_excerpt"><?php _e( 'Post excerpt length in characters', 'wp-to-twitter' ); ?></label><br />
								<input type="text" name="jd_post_excerpt" id="jd_post_excerpt" size="3" maxlength="3" value="<?php echo( esc_attr( get_option( 'jd_post_excerpt' ) ) ); ?>" />
							</p>
							<?php
							if ( '' === get_option( 'jd_date_format', '' ) ) {
								$format = stripslashes( get_option( 'date_format' ) );
							} else {
								$format = get_option( 'jd_date_format' );
							}
							?>
							<p>
								<label for="jd_date_format"><?php _e( 'Date Format:', 'wp-to-twitter' ); ?></label><br />
								<input type="text" aria-describedby="date_format_label" name="jd_date_format" id="jd_date_format" size="12" maxlength="12" value="<?php echo trim( esc_attr( $format ) ); ?>" />
								<span id="date_format_label"><?php _e( 'Currently:', 'wp-to-twitter' ); ?> <?php echo date_i18n( $format ); ?> <a href='https://wordpress.org/support/article/formatting-date-and-time/'><?php _e( 'Date Formatting', 'wp-to-twitter' ); ?></a>
								</span>
							</p>

							<p>
								<label for="jd_twit_prepend"><?php _e( 'Custom text before status:', 'wp-to-twitter' ); ?></label><br />
								<input type="text" name="jd_twit_prepend" id="jd_twit_prepend" size="20" value="<?php echo esc_attr( stripslashes( get_option( 'jd_twit_prepend' ) ) ); ?>"/>
							</p>
							<p>
								<label for="jd_twit_append"><?php _e( 'Custom text after status:', 'wp-to-twitter' ); ?></label><br />
								<input type="text" name="jd_twit_append" id="jd_twit_append" size="20" value="<?php echo esc_attr( stripslashes( get_option( 'jd_twit_append' ) ) ); ?>"/>
							</p>
							<p>
								<label for="jd_twit_custom_url"><?php _e( 'Custom field for alternate post URL:', 'wp-to-twitter' ); ?></label><br />
								<input type="text" name="jd_twit_custom_url" id="jd_twit_custom_url" size="30" maxlength="120" value="<?php echo esc_attr( stripslashes( get_option( 'jd_twit_custom_url' ) ) ); ?>"/>
							</p>
						</fieldset>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Status update controls', 'wp-to-twitter' ); ?></span></h3>
					<div class="inside">
						<fieldset>
							<legend id="special_cases" class='screen-reader-text'><?php _e( 'Special Cases', 'wp-to-twitter' ); ?></legend>
							<ul>
								<li>
									<input type="checkbox" name="jd_tweet_default" id="jd_tweet_default" value="1" <?php echo wpt_checkbox( 'jd_tweet_default' ); ?> />
									<label for="jd_tweet_default"><?php _e( 'Do not post statuses by default', 'wp-to-twitter' ); ?></label>
								</li>
								<li>
									<input type="checkbox" name="jd_tweet_default_edit" id="jd_tweet_default_edit" value="1" <?php echo wpt_checkbox( 'jd_tweet_default_edit' ); ?> />
									<label for="jd_tweet_default_edit"><?php _e( 'Do not post statuses by default when editing', 'wp-to-twitter' ); ?></label>
								</li>
								<li>
									<input type="checkbox" name="wpt_inline_edits" id="wpt_inline_edits" value="1" <?php echo wpt_checkbox( 'wpt_inline_edits' ); ?> />
									<label for="wpt_inline_edits"><?php _e( 'Allow status updates from Quick Edit', 'wp-to-twitter' ); ?></label>
								</li>
								<li>
								<input type="checkbox" name="wpt_rate_limiting" id="wpt_rate_limiting" value="1" <?php echo wpt_checkbox( 'wpt_rate_limiting' ); ?> />
								<label for="wpt_rate_limiting"><?php _e( 'Enable Rate Limiting', 'wp-to-twitter' ); ?></label>
								<?php
								if ( '1' === get_option( 'wpt_rate_limiting' ) ) {
									?>
								<input type="number" name="wpt_default_rate_limit" min="1" id="wpt_default_rate_limit" value="<?php echo wpt_default_rate_limit(); ?>" />
								<label for="wpt_default_rate_limit"><?php _e( 'Default Rate Limit per category per hour', 'wp-to-twitter' ); ?></label>
									<?php
								}
								?>
								</li>
							</ul>
						</fieldset>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Google Analytics Settings', 'wp-to-twitter' ); ?></span></h3>
					<div class="inside">
						<fieldset>
							<legend class='screen-reader-text'><?php _e( 'Google Analytics Settings', 'wp-to-twitter' ); ?></legend>
							<p>
								<input type="radio" name="twitter-analytics" id="use-twitter-analytics" value="1" <?php echo wpt_checkbox( 'use-twitter-analytics' ); ?> />
								<label for="use-twitter-analytics"><?php _e( 'Use a Static Identifier', 'wp-to-twitter' ); ?></label><br/>
								<label for="twitter-analytics-campaign"><?php _e( 'Static Campaign identifier', 'wp-to-twitter' ); ?></label>
								<input type="text" name="twitter-analytics-campaign" id="twitter-analytics-campaign" size="40" maxlength="120" value="<?php echo esc_attr( get_option( 'twitter-analytics-campaign' ) ); ?>"/><br/>
							</p>
							<p>
								<input type="radio" name="twitter-analytics" id="use-dynamic-analytics" value="2" <?php echo wpt_checkbox( 'use_dynamic_analytics' ); ?> />
								<label for="use-dynamic-analytics"><?php _e( 'Use a dynamic identifier', 'wp-to-twitter' ); ?></label><br/>
								<label for="jd-dynamic-analytics"><?php _e( 'What dynamic identifier would you like to use?', 'wp-to-twitter' ); ?></label>
								<select name="jd-dynamic-analytics" id="jd-dynamic-analytics">
									<option value="post_category"<?php checked( get_option( 'jd_dynamic_analytics' ), 'post_category' ); ?>><?php _e( 'Category', 'wp-to-twitter' ); ?></option>
									<option value="post_ID"<?php checked( get_option( 'jd_dynamic_analytics' ), 'post_ID' ); ?>><?php _e( 'Post ID', 'wp-to-twitter' ); ?></option>
									<option value="post_title"<?php checked( get_option( 'jd_dynamic_analytics' ), 'post_title' ); ?>><?php _e( 'Post Title', 'wp-to-twitter' ); ?></option>
									<option value="post_author"<?php checked( get_option( 'jd_dynamic_analytics' ), 'post_author' ); ?>><?php _e( 'Author', 'wp-to-twitter' ); ?></option>
								</select><br/>
							</p>
							<p>
								<input type="radio" name="twitter-analytics" id="no-analytics" value="3" <?php echo wpt_checkbox( 'no-analytics' ); ?> /> <label for="no-analytics"><?php _e( 'No Analytics', 'wp-to-twitter' ); ?></label>
							</p>
						</fieldset>
					</div>
				</div>
			</div>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Permissions', 'wp-to-twitter' ); ?></span></h3>
					<div class="inside">

						<div class='wpt-permissions'>
							<fieldset>
								<legend class="screen-reader-text"><?php _e( 'Permissions', 'wp-to-twitter' ); ?></legend>
								<?php
								global $wp_roles;
								$roles          = $wp_roles->get_names();
								$caps           = array(
									'wpt_can_tweet'      => __( 'Can send Status updates', 'wp-to-twitter' ),
									'wpt_twitter_custom' => __( 'Set Custom Status Update input when creating a Post', 'wp-to-twitter' ),
									'wpt_twitter_switch' => __( 'Toggle the Post/Don\'t Post option', 'wp-to-twitter' ),
									'wpt_tweet_now'      => __( 'Can see Update Now button', 'wp-to-twitter' ),
									'wpt_twitter_oauth'  => __( 'Allow user to authenticate with services', 'wp-to-twitter' ),
								);
								$role_tabs      = '';
								$role_container = '';
								foreach ( $roles as $role => $rolename ) {
									if ( 'administrator' === $role ) {
										continue;
									}
									$role_tabs      .= "<li><a href='#wpt_" . sanitize_title( $role ) . "'>$rolename</a></li>\n";
									$role_container .= "<div class='wptab wpt_$role' id='wpt_" . sanitize_title( $role ) . "' aria-live='assertive'><fieldset id='wpt_$role' class='roles'><legend>$rolename</legend>";
									$role_container .= "<input type='hidden' value='none' name='wpt_caps[" . $role . "][none]' />
									<ul class='wpt-settings checkboxes'>";
									foreach ( $caps as $cap => $name ) {
										$role_container .= wpt_cap_checkbox( $role, $cap, $name );
									}
									$role_container .= '</ul></fieldset></div>';
								}
								echo "
		<ul class='tabs'>
			$role_tabs
		</ul>
		$role_container";
								?>
							</fieldset>
						</div>
					</div>
				</div>
			</div>
			<?php
			$default_order   = array(
				'excerpt'  => 0,
				'title'    => 1,
				'date'     => 2,
				'category' => 3,
				'blogname' => 4,
				'author'   => 5,
				'account'  => 6,
				'tags'     => 7,
				'modified' => 8,
				'@'        => 9,
				'cat_desc' => 10,
			);
			$preferred_order = get_option( 'wpt_truncation_order', false );
			$preferred_order = map_deep( $preferred_order, 'absint' );
			if ( $preferred_order && $preferred_order !== $default_order ) {
				?>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Template tag priority order', 'wp-to-twitter' ); ?></span></h3>
					<div class="inside">
						<?php
						$inputs = '';
						if ( ! $preferred_order ) {
							$preferred_order = array();
						}
						$preferred_order = array_merge( $default_order, $preferred_order );
						if ( is_array( $preferred_order ) ) {
							$default_order = $preferred_order;
						}
						asort( $default_order );
						foreach ( $default_order as $k => $v ) {
							if ( 'blogname' === $k ) {
								$label = '<code>#blog#</code>';
							} elseif ( 'excerpt' === $k ) {
								$label = '<code>#post#</code>';
							} else {
								$label = '<code>#' . $k . '#</code>';
							}
							$inputs .= "<div class='wpt-truncate'><label for='" . esc_attr( "$k-$v" ) . "'>$label</label><br /><input type='number' size='3' value='" . esc_attr( $v ) . "' name='wpt_truncation_order[" . esc_attr( $k ) . "]' /></div> ";
						}
						?>
						<fieldset>
							<legend class='screen-reader-text'><?php _e( 'Template tag priority order', 'wp-to-twitter' ); ?></legend>
							<p>
							<?php
							_e( 'The order in which items will be abbreviated or removed from your status if the status is too long to send.', 'wp-to-twitter' );
							_e( 'Tags with lower values will be modified first.', 'wp-to-twitter' );
							?>
							</p>
							<p>
							<?php echo $inputs; ?>
							</p>
						</fieldset>
					</div>
				</div>
			</div>
				<?php
			}
			?>
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h3><span><?php _e( 'Debugging', 'wp-to-twitter' ); ?></span></h3>
					<div class="inside">
						<fieldset>
							<legend class='screen-reader-text'><?php _e( 'Miscellaneous Settings', 'wp-to-twitter' ); ?></legend>
							<ul>
								<li>
									<input type="checkbox" name="wp_debug_oauth" id="wp_debug_oauth" value="1" <?php echo wpt_checkbox( 'wp_debug_oauth' ); ?> /> <label for="wp_debug_oauth"><?php _e( 'Get Debugging Data for Service Connections', 'wp-to-twitter' ); ?></label>
								</li>
								<li>
									<input type="checkbox" name="wpt_debug_tweets" id="wpt_debug_tweets" value="1" <?php echo wpt_checkbox( 'wpt_debug_tweets' ); ?> /> <label for="wpt_debug_tweets"><?php _e( 'Enable XPoster Debugging', 'wp-to-twitter' ); ?></label>
								</li>
							</ul>
						</fieldset>
						<div>
							<input type="hidden" name="submit-type" value="advanced"/>
						</div>
						<input type="submit" name="submit" value="<?php _e( 'Save Advanced XPoster Options', 'wp-to-twitter' ); ?>" class="button-primary"/>
					</div>
				</form>
			</div>
		</div>
	</div>
		<?php
	}
	if ( 'support' === $current ) {
		?>
		<div class="postbox" id="get-support">
			<h3><span><?php _e( 'Get Plug-in Support', 'wp-to-twitter' ); ?></span></h3>

			<div class="inside">
			<?php wpt_get_support_form(); ?>
			</div>
		</div>
		<?php
	}
	?>
	</div>
	</div>
	<?php wpt_sidebar(); ?>
	</div>
	</div>
	<?php
}

/**
 * Show XPoster sidebar content.
 */
function wpt_sidebar() {
	$context = ( ! function_exists( 'wpt_pro_exists' ) ) ? 'free' : 'premium';
	?>
	<div class="postbox-container jcd-narrow">
	<div class="metabox-holder">
		<div class="ui-sortable meta-box-sortables<?php echo ' ' . $context; ?>">
			<div class="postbox">
				<?php
				if ( 'free' === $context ) {
					?>
					<h3><span><strong><?php _e( 'Buy XPoster Pro', 'wp-to-twitter' ); ?></strong></span></h3>
					<?php
				} else {
					?>
					<h3><span><strong><?php _e( 'XPoster Support', 'wp-to-twitter' ); ?></strong></span></h3>
					<?php
				}
				?>
				<div class="inside resources">
					<?php
					if ( 'free' === $context ) {
						?>
					<p class="link-highlight">
						<a href="https://xposterpro.com/awesome/xposter-pro/">Buy XPoster Pro</a>
					</p>
						<?php
					}
					?>
					<div>
					<ul class="wpt-flex wpt-social">
						<li class="mastodon"><a href="https://toot.io/@joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 61 65" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M60.7539 14.3904C59.8143 7.40642 53.7273 1.90257 46.5117 0.836066C45.2943 0.655854 40.6819 0 29.9973 0H29.9175C19.2299 0 16.937 0.655854 15.7196 0.836066C8.70488 1.87302 2.29885 6.81852 0.744617 13.8852C-0.00294988 17.3654 -0.0827298 21.2237 0.0561464 24.7629C0.254119 29.8384 0.292531 34.905 0.753482 39.9598C1.07215 43.3175 1.62806 46.6484 2.41704 49.9276C3.89445 55.9839 9.87499 61.0239 15.7344 63.0801C22.0077 65.2244 28.7542 65.5804 35.2184 64.1082C35.9295 63.9428 36.6318 63.7508 37.3252 63.5321C38.8971 63.0329 40.738 62.4745 42.0913 61.4937C42.1099 61.4799 42.1251 61.4621 42.1358 61.4417C42.1466 61.4212 42.1526 61.3986 42.1534 61.3755V56.4773C42.153 56.4557 42.1479 56.4345 42.1383 56.4151C42.1287 56.3958 42.1149 56.3788 42.0979 56.3655C42.0809 56.3522 42.0611 56.3429 42.04 56.3382C42.019 56.3335 41.9971 56.3336 41.9761 56.3384C37.8345 57.3276 33.5905 57.8234 29.3324 57.8156C22.0045 57.8156 20.0336 54.3384 19.4693 52.8908C19.0156 51.6397 18.7275 50.3346 18.6124 49.0088C18.6112 48.9866 18.6153 48.9643 18.6243 48.9439C18.6333 48.9236 18.647 48.9056 18.6643 48.8915C18.6816 48.8774 18.7019 48.8675 18.7237 48.8628C18.7455 48.858 18.7681 48.8585 18.7897 48.8641C22.8622 49.8465 27.037 50.3423 31.2265 50.3412C32.234 50.3412 33.2387 50.3412 34.2463 50.3146C38.4598 50.1964 42.9009 49.9808 47.0465 49.1713C47.1499 49.1506 47.2534 49.1329 47.342 49.1063C53.881 47.8507 60.1038 43.9097 60.7362 33.9301C60.7598 33.5372 60.8189 29.8148 60.8189 29.4071C60.8218 28.0215 61.2651 19.5781 60.7539 14.3904Z" fill="url(#paint0_linear_89_8)"/><path d="M50.3943 22.237V39.5876H43.5185V22.7481C43.5185 19.2029 42.0411 17.3949 39.036 17.3949C35.7325 17.3949 34.0778 19.5338 34.0778 23.7585V32.9759H27.2434V23.7585C27.2434 19.5338 25.5857 17.3949 22.2822 17.3949C19.2949 17.3949 17.8027 19.2029 17.8027 22.7481V39.5876H10.9298V22.237C10.9298 18.6918 11.835 15.8754 13.6453 13.7877C15.5128 11.7049 17.9623 10.6355 21.0028 10.6355C24.522 10.6355 27.1813 11.9885 28.9542 14.6917L30.665 17.5633L32.3788 14.6917C34.1517 11.9885 36.811 10.6355 40.3243 10.6355C43.3619 10.6355 45.8114 11.7049 47.6847 13.7877C49.4931 15.8734 50.3963 18.6899 50.3943 22.237Z" fill="white"/><defs><linearGradient id="paint0_linear_89_8" x1="30.5" y1="0" x2="30.5" y2="65" gradientUnits="userSpaceOnUse"><stop stop-color="#6364FF"/><stop offset="1" stop-color="#563ACC"/></linearGradient></defs></svg>
							<span class="screen-reader-text">Mastodon</span></a>
						</li>
						<li class="bluesky"><a href="https://bsky.app/profile/joedolson.bsky.social">
							<svg width="24" height="24" viewBox="0 0 568 501" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M123.121 33.6637C188.241 82.5526 258.281 181.681 284 234.873C309.719 181.681 379.759 82.5526 444.879 33.6637C491.866 -1.61183 568 -28.9064 568 57.9464C568 75.2916 558.055 203.659 552.222 224.501C531.947 296.954 458.067 315.434 392.347 304.249C507.222 323.8 536.444 388.56 473.333 453.32C353.473 576.312 301.061 422.461 287.631 383.039C285.169 375.812 284.017 372.431 284 375.306C283.983 372.431 282.831 375.812 280.369 383.039C266.939 422.461 214.527 576.312 94.6667 453.32C31.5556 388.56 60.7778 323.8 175.653 304.249C109.933 315.434 36.0535 296.954 15.7778 224.501C9.94525 203.659 0 75.2916 0 57.9464C0 -28.9064 76.1345 -1.61183 123.121 33.6637Z" fill="#1185fe"/></svg>
							<span class="screen-reader-text">Bluesky</span></a>
						</li>
						<li class="linkedin"><a href="https://linkedin.com/in/joedolson">
							<svg aria-hidden="true" height="24" viewBox="0 0 72 72" width="24" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M8,72 L64,72 C68.418278,72 72,68.418278 72,64 L72,8 C72,3.581722 68.418278,-8.11624501e-16 64,0 L8,0 C3.581722,8.11624501e-16 -5.41083001e-16,3.581722 0,8 L0,64 C5.41083001e-16,68.418278 3.581722,72 8,72 Z" fill="#007EBB"/><path d="M62,62 L51.315625,62 L51.315625,43.8021149 C51.315625,38.8127542 49.4197917,36.0245323 45.4707031,36.0245323 C41.1746094,36.0245323 38.9300781,38.9261103 38.9300781,43.8021149 L38.9300781,62 L28.6333333,62 L28.6333333,27.3333333 L38.9300781,27.3333333 L38.9300781,32.0029283 C38.9300781,32.0029283 42.0260417,26.2742151 49.3825521,26.2742151 C56.7356771,26.2742151 62,30.7644705 62,40.051212 L62,62 Z M16.349349,22.7940133 C12.8420573,22.7940133 10,19.9296567 10,16.3970067 C10,12.8643566 12.8420573,10 16.349349,10 C19.8566406,10 22.6970052,12.8643566 22.6970052,16.3970067 C22.6970052,19.9296567 19.8566406,22.7940133 16.349349,22.7940133 Z M11.0325521,62 L21.769401,62 L21.769401,27.3333333 L11.0325521,27.3333333 L11.0325521,62 Z" fill="#FFF"/></g></svg>
							<span class="screen-reader-text">LinkedIn</span></a>
						</li>
						<li class="github"><a href="https://github.com/joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 1024 1024" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8C0 11.54 2.29 14.53 5.47 15.59C5.87 15.66 6.02 15.42 6.02 15.21C6.02 15.02 6.01 14.39 6.01 13.72C4 14.09 3.48 13.23 3.32 12.78C3.23 12.55 2.84 11.84 2.5 11.65C2.22 11.5 1.82 11.13 2.49 11.12C3.12 11.11 3.57 11.7 3.72 11.94C4.44 13.15 5.59 12.81 6.05 12.6C6.12 12.08 6.33 11.73 6.56 11.53C4.78 11.33 2.92 10.64 2.92 7.58C2.92 6.71 3.23 5.99 3.74 5.43C3.66 5.23 3.38 4.41 3.82 3.31C3.82 3.31 4.49 3.1 6.02 4.13C6.66 3.95 7.34 3.86 8.02 3.86C8.7 3.86 9.38 3.95 10.02 4.13C11.55 3.09 12.22 3.31 12.22 3.31C12.66 4.41 12.38 5.23 12.3 5.43C12.81 5.99 13.12 6.7 13.12 7.58C13.12 10.65 11.25 11.33 9.47 11.53C9.76 11.78 10.01 12.26 10.01 13.01C10.01 14.08 10 14.94 10 15.21C10 15.42 10.15 15.67 10.55 15.59C13.71 14.53 16 11.53 16 8C16 3.58 12.42 0 8 0Z" transform="scale(64)" fill="#1B1F23"/></svg>
							<span class="screen-reader-text">GitHub</span></a>
						</li>
					</ul>
					<?php
					if ( 'premium' === $context ) {
						$support_url = admin_url( 'admin.php?page=wp-tweets-pro' );
						$support     = '<a href="' . esc_url( add_query_arg( 'tab', 'support', $support_url ) ) . '#get-support">' . __( 'Get Support', 'wp-to-twitter' ) . '</a> &bull; ';
					} else {
						$support_url = false;
						$support     = '';
					}
					?>
					<p><?php echo $support; ?><a href="https://docs.xposterpro.com/"><?php _e( 'Documentation', 'wp-to-twitter' ); ?></a></p>
					</div>
				</div>
			</div>
		</div>

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<?php
				$admin_url = admin_url( 'admin.php?page=wp-tweets-pro&amp;refresh_wpt_server_string=true' );
				$link      = "<a href='" . $admin_url . "'>" . __( 'Test again', 'wp-to-twitter' ) . '</a>';
				?>
				<h3><?php _e( 'X.com Time Check', 'wp-to-twitter' ); ?> &bull; <?php echo $link; ?></h3>

				<div class="inside server">
				<?php wpt_do_server_check(); ?>
				</div>
			</div>
		</div>

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><?php _e( 'Test Status Updates', 'wp-to-twitter' ); ?></h3>

				<div class="inside test">
				<p>
				<?php _e( 'Check whether XPoster is set up for your connected services and URL Shortener. The test sends a status update to each connected service and shortens a URL.', 'wp-to-twitter' ); ?>
				</p>
				<form method="post" action="">
					<input type="hidden" name="submit-type" value="check-support" />
					<?php
					$nonce = wp_nonce_field( 'wp-to-twitter-nonce', '_wpnonce', true, false ) . wp_referer_field( false );
					echo "<div>$nonce</div>";
					?>
					<p>
						<input type="submit" name="status-update" value="<?php esc_attr_e( 'Test Updates', 'wp-to-twitter' ); ?>" class="button-secondary" />
					</p>
				</form>
				</div>
			</div>
		</div>

		<?php
		if ( '1' === get_option( 'wpt_rate_limiting' ) ) {
			?>
		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h3><?php _e( 'Monitor Rate Limiting', 'wp-to-twitter' ); ?></h3>

				<div class="inside server">
					<?php echo wpt_view_rate_limits(); ?>
				</div>
			</div>
		</div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Compare your server time to X.com's time.
 *
 * @param boolean $test Doing a test.
 */
function wpt_do_server_check( $test = false ) {
	$wpt_server_string = get_option( 'wpt_server_string' );
	$date              = '';
	if ( ! $wpt_server_string || isset( $_GET['refresh_wpt_server_string'] ) || true === $test ) {
		$server_time = gmdate( DATE_COOKIE );
		$response    = wp_remote_get(
			'https://twitter.com/',
			array(
				'timeout'     => 30,
				'redirection' => 1,
			)
		);

		if ( is_wp_error( $response ) ) {
			$warning = '';
			$error   = $response->errors;
			if ( is_array( $error ) ) {
				$warning = '<ul>';
				foreach ( $error as $k => $e ) {
					foreach ( $e as $v ) {
						$warning .= '<li>' . $v . '</li>';
					}
				}
				$warning .= '</ul>';
			}
			$errors = '<li>' . $warning . '</li>';
		} else {
			$date   = gmdate( DATE_COOKIE, strtotime( $response['headers']['date'] ) );
			$errors = '';
		}

		if ( ! is_wp_error( $response ) ) {
			if ( abs( strtotime( $server_time ) - strtotime( $response['headers']['date'] ) ) > 300 ) {
				$diff = __( 'Your time stamps are more than 5 minutes apart. Your server could lose its connection with X.com.', 'wp-to-twitter' );
			} else {
				$diff = __( 'Your time stamp matches the X.com server time', 'wp-to-twitter' );
			}
			$diff = "<li>$diff</li>";
		} else {
			$diff = '<li>' . __( 'XPoster could not contact X.com.', 'wp-to-twitter' ) . '</li>';
		}

		$timezone = '<li>' . __( 'Your server timezone:', 'wp-to-twitter' ) . ' ' . date_default_timezone_get() . '</li>';

		$search  = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
		$replace = array( 'Mon', 'Tues', 'Wed', 'Thurs', 'Fri', 'Sat', 'Sun' );

		$server_time = str_replace( $search, $replace, $server_time );
		$date        = str_replace( $search, $replace, $date );

		$wpt_server_string =
			'<ul>
				<li>' . __( 'Your server time:', 'wp-to-twitter' ) . '<br /><code>' . $server_time . '</code>' . '</li>' .
				'<li>' . __( 'X.com\'s server time: ', 'wp-to-twitter' ) . '<br /><code>' . $date . '</code>' . "</li>
				$timezone
				$diff
				$errors
			</ul>";
		update_option( 'wpt_server_string', $wpt_server_string );
	}
	echo $wpt_server_string;
}

add_filter( 'wpt_tweet_length', 'wpt_tweet_length' );
/**
 * Add control to set maximum length for a status update.
 *
 * @return string HTML control.
 */
function wpt_tweet_length() {
	$language = get_locale();
	switch ( $language ) {
		case 'zh_CN':
		case 'zh_HK':
		case 'zh_HK':
		case 'ja':
		case 'ko_KR':
			$default = 140;
			break;
		default:
			$default = 280;
	}
	if ( ! get_option( 'wpt_tweet_length' ) ) {
		// If not set, save as option so character counter works correctly.
		update_option( 'wpt_tweet_length', $default );
	}
	$update_length = intval( ( get_option( 'wpt_tweet_length' ) ) ? get_option( 'wpt_tweet_length' ) : $default );
	$control       = "<p class='tweet_length_control'>
					<label for='wpt_tweet_length'>" . __( 'Maximum Status Length', 'wp-to-twitter' ) . "</label>
					<input type='number' min='0' max='25000' step='1' value='$update_length' id='wpt_tweet_length' aria-describedby='maxlengthwarning' name='wpt_tweet_length' />
					<span id='maxlengthwarning'>" . __( 'X.com Statuses longer than 280 characters require an <a href="https://help.twitter.com/en/using-x/x-premium">X Premium</a> subscription.', 'wp-to-twitter' ) . ' ' . __( 'Most Mastodon servers have a 500 character limit.', 'wp-to-twitter' ) . '</span>
				</p>';

	return $control;
}

add_filter( 'wpt_settings', 'wpt_set_tweet_length' );
/**
 * Set the maximum length for a status update.
 */
function wpt_set_tweet_length() {
	if ( isset( $_POST['wpt_tweet_length'] ) ) {
		update_option( 'wpt_tweet_length', intval( $_POST['wpt_tweet_length'] ) );
	}
}


add_filter( 'wpt_auto_tweet', 'wpt_auto_tweet' );
/**
 * Add control to allow auto status updates on imported posts.
 *
 * @return string HTML control.
 */
function wpt_auto_tweet() {
	$allow   = ( '0' === get_option( 'wpt_auto_tweet_allowed', '0' ) ) ? false : true;
	$note    = ( $allow ) ? '<strong id="auto_tweet_note">(' . __( 'When publishing manually, you will need to save drafts prior to publishing to support XPoster metabox options.', 'wp-to-twitter' ) . ')</strong>' : '';
	$control = "<p class='wpt_auto_tweet_allowed'>
					<input type='checkbox' value='1' " . checked( $allow, true, false ) . "id='wpt_auto_tweet_allowed' name='wpt_auto_tweet_allowed' aria-describedby='auto_tweet_note' /> <label for='wpt_auto_tweet_allowed'>" . __( 'Allow status updates from Post Importers', 'wp-to-twitter' ) . "</label> $note
				</p>";

	return $control;
}

add_filter( 'wpt_settings', 'wpt_set_auto_tweet_allowed' );
/**
 * Set the automatic status update allowed parameter..
 */
function wpt_set_auto_tweet_allowed() {
	if ( isset( $_POST['wpt_auto_tweet_allowed'] ) ) {
		update_option( 'wpt_auto_tweet_allowed', '1' );
	} else {
		delete_option( 'wpt_auto_tweet_allowed' );
	}
}
