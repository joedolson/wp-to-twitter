<?php
/**
 * Metabox rendering functions XPoster
 *
 * @category Metabox
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

/**
 * Set up post meta box.
 */
function wpt_add_twitter_outer_box() {
	// add X.com panel to post types where it's enabled.
	$wpt_post_types = get_option( 'wpt_post_types' );
	if ( is_array( $wpt_post_types ) ) {
		foreach ( $wpt_post_types as $key => $value ) {
			if ( '1' === (string) $value['post-published-update'] || '1' === (string) $value['post-edited-update'] ) {
				if ( current_user_can( 'wpt_can_tweet' ) ) {
					add_meta_box( 'wp2t', 'XPoster', 'wpt_add_twitter_inner_box', $key, 'side' );
				}
			}
		}
	}
}
add_action( 'admin_menu', 'wpt_add_twitter_outer_box' );

/**
 * Print post meta box
 *
 * @param  object $post Post object.
 */
function wpt_add_twitter_inner_box( $post ) {
	$nonce = wp_create_nonce( 'wp-to-twitter-nonce' );
	?>
	<div>
		<input type="hidden" name="wp_to_twitter_nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<input type="hidden" name="wp_to_twitter_meta" value="true">
	</div>
	<?php
	if ( current_user_can( 'wpt_can_tweet' ) ) {
		$is_pro = ( function_exists( 'wpt_pro_exists' ) ) ? 'pro' : 'free';
		?>
		<div class='wp-to-twitter <?php echo esc_attr( $is_pro ); ?>'>
		<?php
		$options = get_option( 'wpt_post_types' );
		$status  = $post->post_status;
		wpt_show_metabox_message( $post, $options );
		// Show switch to flip update status.
		wpt_show_post_switch( $post, $options );
		echo '<div class="wpt-options-metabox">';
		$user_tweet = apply_filters( 'wpt_user_text', '', $status );
		// Formulate Template display.
		$template = wpt_display_status_template( $post, $options );
		if ( $user_tweet ) {
			// If a user template is defined, replace the existing template.
			$template = $user_tweet;
		}
		if ( 'publish' === $status && ( current_user_can( 'wpt_tweet_now' ) || current_user_can( 'manage_options' ) ) ) {
			// Show metabox status buttons.
			wpt_display_metabox_status_buttons( $is_pro );
		}
		if ( current_user_can( 'wpt_twitter_custom' ) || current_user_can( 'manage_options' ) ) {
			$custom_update = get_post_meta( $post->ID, '_jd_twitter', true );
			?>
			<p class='jtw'>
				<label for="wpt_custom_tweet"><?php esc_html_e( 'Custom Status Update', 'wp-to-twitter' ); ?></label><br/>
				<textarea class="wpt_tweet_box widefat" name="_jd_twitter" id="wpt_custom_tweet" placeholder="<?php echo esc_attr( $template ); ?>" rows="2" cols="60"><?php echo esc_textarea( stripslashes( $custom_update ) ); ?></textarea>
			</p>
			<div role="alert" class="x-notification notice inline notice-info hidden"><p><?php esc_html_e( 'X length limit reached:', 'wp-to-twitter' ); ?> <span></span></p></div>
			<div role="alert" class="bluesky-notification notice inline notice-info hidden"><p><?php esc_html_e( 'Bluesky length limit reached:', 'wp-to-twitter' ); ?> <span></span></p></div>
			<div role="alert" class="mastodon-notification notice inline notice-info hidden"><p><?php esc_html_e( 'Mastodon length limit reached:', 'wp-to-twitter' ); ?> <span></span></p></div>
			<div class="wpt-template-resources wpt-flex">
				<p class='wpt-template'>
					<?php esc_html_e( 'Default template:', 'wp-to-twitter' ); ?><br /><code><?php echo esc_html( stripcslashes( $template ) ); ?></code>
				</p>
				<div class='wptab' id='notes'>
					<h3><?php esc_html_e( 'Template Tags', 'wp-to-twitter' ); ?></h3>
					<ul class="inline-list">
					<?php
					$tags = wpt_tags();
					foreach ( $tags as $tag ) {
						$pressed = ( false === stripos( $template, '#' . $tag . '#' ) ) ? 'false' : 'true';
						echo '<li><button type="button" class="button-secondary" aria-pressed="' . esc_attr( $pressed ) . '">#' . esc_html( $tag ) . '#</button></li>';
					}
					do_action( 'wpt_notes_tab', $post->ID );
					?>
					</ul>
				</div>
			</div>
			<?php
			/**
			 * Generate fields after the custom template box in the meta box.
			 *
			 * @hook wpt_after_meta_template_box
			 *
			 * @param {int} $post_ID Post ID.
			 */
			do_action( 'wpt_after_meta_template_box', $post->ID );
			if ( get_option( 'jd_keyword_format' ) === '2' ) {
				$custom_keyword = get_post_meta( $post->ID, '_yourls_keyword', true );
				?>
				<label for='yourls_keyword'><?php esc_html_e( 'YOURLS Custom Keyword', 'wp-to-twitter' ); ?></label> <input type='text' name='_yourls_keyword' id='yourls_keyword' value='<?php echo esc_attr( $custom_keyword ); ?>' />
				<?php
			}
		} else {
			?>
			<input type="hidden" name='_jd_twitter' value='<?php echo esc_attr( $template ); ?>' />
			<p class='wpt-template'>
				<?php esc_html_e( 'Template:', 'wp-to-twitter' ); ?> <code><?php echo esc_html( stripcslashes( $template ) ); ?></code>
			</p>
			<?php
		}
		?>
		<div class='wpt-options'>
			<div class='wptab' id='custom'>
			<?php
			// XPoster Pro.
			if ( 'pro' === $is_pro && ( current_user_can( 'wpt_twitter_custom' ) || current_user_can( 'manage_options' ) ) ) {
				wpt_schedule_values( $post->ID );
				do_action( 'wpt_custom_tab', $post->ID, 'visible' );
				if ( current_user_can( 'edit_others_posts' ) ) {
					if ( '1' === get_option( 'jd_individual_twitter_users' ) ) {
						$selected = ( get_post_meta( $post->ID, '_wpt_authorized_users', true ) ) ? get_post_meta( $post->ID, '_wpt_authorized_users', true ) : array();
						if ( function_exists( 'wpt_authorized_users' ) ) {
							wpt_authorized_users( $selected );
							do_action( 'wpt_authors_tab', $post->ID, $selected );
						}
					}
				}
			}
			if ( ! current_user_can( 'wpt_twitter_custom' ) && ! current_user_can( 'manage_options' ) ) {
				?>
				<p><?php esc_html_e( 'Customizing XPoster options is not allowed for your user role.', 'wp-to-twitter' ); ?></p>
				<?php
				if ( 'pro' === $is_pro ) {
					wpt_schedule_values( $post->ID, 'hidden' );
					do_action( 'wpt_custom_tab', $post->ID, 'hidden' );
				}
			}
			?>
			</div>
		</div>
		<?php wpt_show_history( $post->ID ); ?>
		<?php wpt_meta_box_support( $is_pro ); ?>
		</div>
		</div>
		<?php
	} else {
		// permissions: this user isn't allowed to post status updates.
		esc_html_e( 'Your role does not have the ability to post status updates from this site.', 'wp-to-twitter' );
		?>
		<input type='hidden' name='_wpt_post_this' value='no' />
		<?php
	}
}

/**
 * Format history of status updates attempted on current post.
 *
 * @param array $post_id Post ID to fetch status updates on.
 */
function wpt_show_history( $post_id ) {
	$previous_tweets = get_post_meta( $post_id, '_jd_wp_twitter', true );
	$failed_tweets   = get_post_meta( $post_id, '_wpt_failed' );

	if ( ! is_array( $previous_tweets ) && '' !== $previous_tweets ) {
		$previous_tweets = array( 0 => $previous_tweets );
	}
	if ( ! empty( $previous_tweets ) || ! empty( $failed_tweets ) ) {
		?>
	<p class='panel-toggle'>
		<button type="button" aria-expanded="false" class='history-toggle button-secondary'><span class='dashicons dashicons-plus' aria-hidden="true"></span><?php esc_html_e( 'View Update History', 'wp-to-twitter' ); ?></button>
	</p>
	<div class='history'>
	<h4 class='wpt-past-updates'><?php esc_html_e( 'Previous Updates', 'wp-to-twitter' ); ?>:</h4>
	<ul class="striped">
		<?php
		$has_history = false;
		if ( is_array( $previous_tweets ) ) {
			foreach ( $previous_tweets as $previous_tweet ) {
				if ( '' !== $previous_tweet ) {
					$has_history = true;
					$intents     = array();
					if ( wtt_oauth_test() ) {
						$intents['x'] = "<a class='wpt-x' href='https://x.com/intent/tweet?text=" . urlencode( $previous_tweet ) . "'>" . __( 'X', 'wp-to-twitter' ) . '<span class="dashicons dashicons-external" aria-hidden="true"></span></a>';
					}
					if ( wpt_mastodon_connection() ) {
						$mastodon            = get_option( 'wpt_mastodon_instance' );
						$intents['mastodon'] = "<a class='wpt-mastodon' href='" . esc_url( $mastodon ) . '/statuses/new?text=' . urlencode( $previous_tweet ) . "'>" . __( 'Mastodon', 'wp-to-twitter' ) . '<span class="dashicons dashicons-external" aria-hidden="true"></span></a>';
					}
					if ( wpt_bluesky_connection() ) {
						$intents['bluesky'] = "<a class='wpt-bluesky' href='https://bsky.app/intent/compose?text=" . urlencode( $previous_tweet ) . "'>" . __( 'Bluesky', 'wp-to-twitter' ) . '<span class="dashicons dashicons-external" aria-hidden="true"></span></a>';
					}
					$intent_links = implode( ', ', $intents );
					?>
					<li><input type='hidden' name='_jd_wp_twitter[]' value='<?php echo esc_attr( $previous_tweet ); ?>' />
					<?php
					echo wp_kses_post( "<p class='wpt-previous-tweet'>$previous_tweet</p>$intent_links" );
					?>
					</li>
					<?php
				}
			}
		}
		?>
	</ul>
		<?php
		$list       = false;
		$error_list = '';
		if ( is_array( $failed_tweets ) ) {
			foreach ( $failed_tweets as $failed_tweet ) {
				if ( ! empty( $failed_tweet ) ) {
					$ft     = $failed_tweet['sentence'];
					$reason = $failed_tweet['code'];
					$error  = $failed_tweet['error'];
					$list   = true;

					$twitter_intent  = '';
					$mastodon_intent = '';
					$bluesky_intent  = '';
					if ( wtt_oauth_test() ) {
						$twitter_intent = "<a href='https://x.com/intent/tweet?text=" . urlencode( $ft ) . "'>" . __( 'Send to X.com', 'wp-to-twitter' ) . '</a>';
					}
					if ( wpt_mastodon_connection() ) {
						$mastodon        = get_option( 'wpt_mastodon_instance' );
						$mastodon_intent = "<a href='" . esc_url( $mastodon ) . '/statuses/new?text=' . urlencode( $ft ) . "'>" . __( 'Send to Mastodon', 'wp-to-twitter' ) . '</a>';
					}
					if ( wpt_bluesky_connection() ) {
						$bluesky_intent = "<a href='https://bsky.app/intent/compose?text=" . urlencode( $ft ) . "'>" . __( 'Send to Bluesky', 'wp-to-twitter' ) . '</a>';
					}
					$error_list .= "<li><code>Error: $reason</code> $ft $twitter_intent $mastodon_intent $bluesky_intent <br /><em>$error</em></li>";
				}
			}
			if ( true === $list ) {
				?>
				<h4 class='wpt-failed-updates'><?php esc_html_e( 'Failed Status Updates', 'wp-to-twitter' ); ?></h4>
				<ul>
					<?php echo wp_kses_post( $error_list ); ?>
				</ul>
				<?php
			}
		}
		if ( $has_history || $list ) {
			?>
			<p><input type='checkbox' name='wpt_clear_history' id='wptch' value='clear' /> <label for='wptch'><?php esc_html_e( 'Delete Status History', 'wp-to-twitter' ); ?></label></p>
			<?php
		}
		?>
	</div>
		<?php
	}
}

/**
 * Render support link or sales link inside metabox.
 *
 * @param string $is_pro 'pro' if is pro.
 */
function wpt_meta_box_support( $is_pro = 'free' ) {
	?>
	<div class="wpt-support">
	<?php
	if ( 'pro' === $is_pro ) {
		?>
		<p>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'support', admin_url( 'admin.php?page=wp-tweets-pro' ) ) ); ?>#get-support"><?php esc_html_e( 'Get Support', 'wp-to-twitter' ); ?></a>
		</p>
		<?php
	} else {
		?>
		<p class="link-highlight">
			<a href="https://xposterpro.com/awesome/xposter-pro/"><?php esc_html_e( 'Buy XPoster Pro', 'wp-to-twitter' ); ?></a>
		</p>
		<?php
	}
	?>
	</div>
	<?php
}

/**
 * Display metabox status messages when page is loaded.
 *
 * @param WP_Post $post Post object.
 * @param array   $options Posting options.
 */
function wpt_show_metabox_message( $post, $options ) {
	$type      = $post->post_type;
	$status    = $post->post_status;
	$post_this = wpt_get_post_update_status( $post, $options );
	if ( isset( $_REQUEST['message'] ) && '10' !== $_REQUEST['message'] ) {
		// don't display when draft is updated or if no message.
		if ( ! ( ( '1' === $_REQUEST['message'] ) && ( 'publish' === $status && '1' !== $options[ $type ]['post-edited-update'] ) ) && 'no' !== $post_this ) {
			$log = wpt_get_log( 'wpt_status_message', $post->ID );
			if ( is_array( $log ) ) {
				$message = $log['message'];
				$http    = $log['http'];
			} else {
				$message = $log;
				$http    = '200';
			}
			$class = ( '200' !== (string) $http ) ? 'error' : 'success';
			if ( '' !== trim( $message ) ) {
				wp_admin_notice(
					$message,
					array(
						'type'               => $class,
						'additional_classes' => 'inline',
					)
				);
			}
		}
	}
}

/**
 * Check whether a post is supposed to be posted based on settings.
 *
 * @param WP_Post $post Post object.
 * @param array   $options Status update options.
 *
 * @return string
 */
function wpt_get_post_update_status( $post, $options ) {
	$status    = $post->post_status;
	$type      = $post->post_type;
	$post_this = get_post_meta( $post->ID, '_wpt_post_this', true );
	if ( ! $post_this ) {
		$post_this = ( '1' === get_option( 'jd_tweet_default' ) ) ? 'no' : 'yes';
	}
	$is_edit               = ( 'publish' === $status ) ? true : false;
	$status_update_on_edit = ( '1' === $options[ $type ]['post-edited-update'] && '1' !== get_option( 'jd_tweet_default_edit' ) ) ? true : false;
	if ( $is_edit && ! $status_update_on_edit ) {
		$post_this = 'no';
	}

	return $post_this;
}

/**
 * Test whether the metabox should load with a 'yes' or 'no' preset for posting status and display toggle to update.
 *
 * @param WP_Post $post Post object.
 * @param array   $options Status update options.
 */
function wpt_show_post_switch( $post, $options ) {
	$post_this = wpt_get_post_update_status( $post, $options );

	if ( current_user_can( 'wpt_twitter_switch' ) || current_user_can( 'manage_options' ) ) {
		// "no" means 'Don't Post' (is checked)
		$nochecked  = ( 'no' === $post_this ) ? ' checked="checked"' : '';
		$yeschecked = ( 'yes' === $post_this ) ? ' checked="checked"' : '';
		?>
		<p class='toggle-btn-group'>
			<input type='radio' name='_wpt_post_this' value='no' id='jtn'<?php echo esc_attr( $nochecked ); ?> /><label for='jtn'><?php esc_html_e( "Don't Post", 'wp-to-twitter' ); ?></label>
			<input type='radio' name='_wpt_post_this' value='yes' id='jty'<?php echo esc_attr( $yeschecked ); ?> /><label for='jty'><?php esc_html_e( 'Post', 'wp-to-twitter' ); ?></label>
		</p>
		<?php
	} else {
		?>
		<input type='hidden' name='_wpt_post_this' value='<?php echo esc_attr( $post_this ); ?>' />
		<?php
	}
}

/**
 * Generate a status update template for display.
 *
 * @param WP_Post $post Post object.
 * @param array   $options Post status options.
 *
 * @return string
 */
function wpt_display_status_template( $post, $options ) {
	$status   = $post->post_status;
	$type     = $post->post_type;
	$template = ( 'publish' === $status ) ? $options[ $type ]['post-edited-text'] : $options[ $type ]['post-published-text'];
	$expanded = $template;
	if ( '' !== get_option( 'jd_twit_prepend', '' ) ) {
		$expanded = '<em>' . stripslashes( get_option( 'jd_twit_prepend' ) ) . '</em> ' . $expanded;
	}
	if ( '' !== get_option( 'jd_twit_append', '' ) ) {
		$expanded = $expanded . ' <em>' . stripslashes( get_option( 'jd_twit_append' ) ) . '</em>';
	}

	return $expanded;
}

/**
 * Generate post now and schedule update buttons.
 *
 * @param string $is_pro 'pro' if Pro.
 */
function wpt_display_metabox_status_buttons( $is_pro ) {
	?>
	<div class='tweet-buttons'>
		<div class="wpt-buttons">
			<button type='button' class='tweet button-primary' data-action='tweet'><span class='dashicons dashicons-share' aria-hidden='true'></span><?php esc_html_e( 'Share Now', 'wp-to-twitter' ); ?></button>
		<?php
		if ( 'pro' === $is_pro ) {
			?>
			<button type='button' class='tweet schedule button-secondary' data-action='schedule' disabled><?php esc_html_e( 'Schedule', 'wp-to-twitter' ); ?></button>
			<button type='button' class='time button-secondary'><span class='dashicons dashicons-clock' aria-hidden='true'></span><span class='screen-reader-text'><?php esc_html_e( 'Set Date/Time', 'wp-to-twitter' ); ?></span></button>
			<?php
		}
		?>
		</div>
	</div>
	<div class='wpt_log' aria-live='assertive'></div>
	<?php
	if ( 'pro' === $is_pro ) {
		$datavalue = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$timevalue = date_i18n( 'h:s a', current_time( 'timestamp' ) + 3600 ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		?>
		<div id="wpt_set_tweet_time">
			<div class="wpt-date-field">
				<label for="wpt_date"><?php esc_html_e( 'Date', 'wp-to-twitter' ); ?></label>
				<input type="date" value="" class="wpt_date date" name="wpt_datetime" id="wpt_date" data-value="<?php echo esc_attr( $datavalue ); ?>" /><br/>
			</div>
			<div class="wpt-time-field">
				<label for="wpt_time"><?php esc_html_e( 'Time', 'wp-to-twitter' ); ?></label>
				<input type="time" value="<?php echo esc_attr( $timevalue ); ?>" class="wpt_time time" name="wpt_datetime" id="wpt_time" />
			</div>
		</div>
		<?php
	}
}
