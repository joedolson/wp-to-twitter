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
			<a class="button-secondary" href="<?php echo esc_url( add_query_arg( 'tab', 'support', admin_url( 'admin.php?page=wp-tweets-pro' ) ) ); ?>#get-support"><?php _e( 'Get Support', 'wp-to-twitter' ); ?></a>
		</p>
		<?php
	} else {
		?>
		<p class="link-highlight">
			<a href="https://xposterpro.com/awesome/xposter-pro/"><?php _e( 'Buy XPoster Pro', 'wp-to-twitter' ); ?></a>
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
	$post_this = wpt_get_post_update_status( $post );
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
				echo "<div class='notice notice-$class'><p>$message</p></div>";
			}
		}
	}
}

/**
 * Check whether a post is supposed to be posted based on settings.
 *
 * @param WP_Post $post Post object.
 *
 * @return string
 */
function wpt_get_post_update_status( $post ) {
	$status    = $post->post_status;
	$post_this = get_post_meta( $post->ID, '_wpt_post_this', true );
	if ( ! $post_this ) {
		$post_this = ( '1' === get_option( 'jd_tweet_default' ) ) ? 'no' : 'yes';
	}
	if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && '1' === get_option( 'jd_tweet_default_edit' ) && 'publish' === $status ) {
		$post_this = 'no';
	}

	return $post_this;
}

/**
 * Test whether the metabox should load with a 'yes' or 'no' preset for posting status and display toggle to update.
 *
 * @param WP_Post $post Post object.
 *
 * @return string
 */
function wpt_show_post_switch( $post ) {
	$post_this = wpt_get_post_update_status( $post );

	if ( current_user_can( 'wpt_twitter_switch' ) || current_user_can( 'manage_options' ) ) {
		// "no" means 'Don't Post' (is checked)
		$nochecked  = ( 'no' === $post_this ) ? ' checked="checked"' : '';
		$yeschecked = ( 'yes' === $post_this ) ? ' checked="checked"' : '';
		$toggle     = "<p class='toggle-btn-group'>
			<input type='radio' name='_wpt_post_this' value='no' id='jtn'$nochecked /><label for='jtn'>" . __( "Don't Post", 'wp-to-twitter' ) . "</label>
			<input type='radio' name='_wpt_post_this' value='yes' id='jty'$yeschecked /><label for='jty'>" . __( 'Post', 'wp-to-twitter' ) . '</label>
		</p>';
	} else {
		$toggle = "<input type='hidden' name='_wpt_post_this' value='$post_this' />";
	}

	return $toggle;
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
 *
 * @return string
 */
function wpt_display_metabox_status_buttons( $is_pro ) {
	$buttons = "<button type='button' class='tweet button-primary' data-action='tweet'><span class='dashicons dashicons-share' aria-hidden='true'></span>" . __( 'Share Now', 'wp-to-twitter' ) . '</button>';
	$fields  = '';
	if ( 'pro' === $is_pro ) {
		$buttons .= "<button type='button' class='tweet schedule button-secondary' data-action='schedule' disabled>" . __( 'Schedule', 'wp-to-twitter' ) . '</button>';
		$buttons .= "<button type='button' class='time button-secondary'><span class='dashicons dashicons-clock' aria-hidden='true'></span><span class='screen-reader-text'>" . __( 'Set Date/Time', 'wp-to-twitter' ) . '</span></button>';
	}
	$buttons = '<div class="wpt-buttons">' . $buttons . '</div>';
	if ( 'pro' === $is_pro ) {
		$datavalue  = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$timevalue  = date_i18n( 'h:s a', current_time( 'timestamp' ) + 3600 ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$date_field = '<div class="wpt-date-field">
			<label for="wpt_date">' . __( 'Date', 'wp-to-twitter' ) . '</label>
			<input type="date" value="" class="wpt_date date" name="wpt_datetime" id="wpt_date" data-value="' . $datavalue . '" /><br/>
		</div>';
		$time_field = '<div class="wpt-time-field">
			<label for="wpt_time">' . __( 'Time', 'wp-to-twitter' ) . '</label>
			<input type="time" value="' . $timevalue . '" class="wpt_time time" name="wpt_datetime" id="wpt_time" />
		</div>';
		$fields     = '<div id="wpt_set_tweet_time">' . $date_field . $time_field . '</div>';
	}
	$buttons  = "<div class='tweet-buttons'>" . $buttons . '</div>';
	$buttons .= "<div class='wpt_log' aria-live='assertive'></div>";
	$buttons .= $fields;

	return $buttons;
}
