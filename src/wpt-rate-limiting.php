<?php
/**
 * Rate limiting in XPoster
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

add_action( 'wptratelimits', 'wpt_clear_rate_limits' );
/**
 * Hourly cron job to reset rate limits
 */
function wpt_clear_rate_limits() {
	delete_option( 'wpt_rate_limits' );
}

/**
 * Logs successful status updates for rate limiting.
 *
 * @param int    $auth Author.
 * @param string $ts Timestamp.
 * @param int    $post_ID Post ID.
 */
function wpt_log_success( $auth, $ts, $post_ID ) {
	if ( ! $post_ID ) {
		return;
	}

	// get record of recent updates.
	$rate_limit = get_option( 'wpt_rate_limits' );
	if ( ! is_array( $rate_limit ) ) {
		$rate_limit = array();
	}
	$post              = get_post( $post_ID );
	$post_type         = $post->post_type;
	$object_taxonomies = get_object_taxonomies( $post_type );
	$terms             = wp_get_object_terms( $post_ID, $object_taxonomies, array( 'fields' => 'all' ) );

	foreach ( $terms as $term ) {
		$term_id = $term->term_id;
		$tax     = $term->taxonomy;

		$rate_limit[ $auth ][ $term_id . '+' . $tax ][] = $post_ID;
	}

	update_option( 'wpt_rate_limits', $rate_limit );
}

/**
 * Test updates against rate limiting rules.
 *
 * @param int $post_ID Post ID.
 * @param int $auth Author ID.
 *
 * @return boolean True if OK to post update.
 */
function wpt_test_rate_limit( $post_ID, $auth ) {
	// record of recent updates.
	$rate_limit = get_option( 'wpt_rate_limits' );
	$return     = true;
	if ( ! $rate_limit ) {
		return true;
	} else {
		$post = get_post( $post_ID );
		if ( is_object( $post ) ) {
			$post_type         = $post->post_type;
			$object_taxonomies = get_object_taxonomies( $post_type );
			$terms             = wp_get_object_terms( $post_ID, $object_taxonomies, array( 'fields' => 'all' ) );

			foreach ( $terms as $term ) {
				$term_id = $term->term_id;
				$limit   = wpt_get_rate_limit( $term_id );
				$tax     = $term->taxonomy;
				$count   = ( isset( $rate_limit[ $auth ][ $term_id . '+' . $tax ] ) ) ? count( $rate_limit[ $auth ][ $term_id . '+' . $tax ] ) : false;
				if ( $count && $count >= $limit ) {
					$return = false;
				}
			}
		} else {
			// don't rate limit if no post.
			$return = true;
		}
	}

	return $return;
}

/**
 * Default rate limiting value. Limit can't be 0.
 *
 * @param int $term Term ID.
 *
 * @return integer Default rate limit
 */
function wpt_default_rate_limit( $term = false ) {
	$limit = ( '' !== get_option( 'wpt_default_rate_limit' ) ) ? get_option( 'wpt_default_rate_limit' ) : 10;
	$limit = ( 0 === (int) $limit ) ? 1 : $limit;
	/**
	 * Filter the default rate limit.
	 *
	 * @hook wpt_default_rate_limit
	 *
	 * @param {int} $limit Integer number of posts allowed.
	 * @param {int|bool} $term  Term ID or false for default value.
	 *
	 * @return {int}
	 */
	return apply_filters( 'wpt_default_rate_limit', $limit, $term );
}

/**
 * Get the current rate limit for a given term ID.
 *
 * @param string $term Term ID.
 * @uses filter wpt_default_rate_limit
 *
 * @return integer Number of status updates allowed per hour in this category.
 */
function wpt_get_rate_limit( $term ) {
	$limits = get_option( 'wpt_rate_limit' );
	$limit  = isset( $limits[ $term ] ) ? $limits[ $term ] : wpt_default_rate_limit( $term );
	if ( ! is_int( $limit ) ) {
		$limit = wpt_default_rate_limit( $term );
	}

	return $limit;
}

add_action( 'init', 'wpt_term_rate_limits' );
/**
 * Get term-based rate limits.
 */
function wpt_term_rate_limits() {
	$args       = apply_filters( 'wpt_rate_limit_taxonomies', array() );
	$taxonomies = get_taxonomies( $args );
	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array();
	}
	foreach ( $taxonomies as $value ) {
		add_action( $value . '_add_form_fields', 'wpt_add_term_rate_limit', 10, 1 );
		add_action( $value . '_edit_form_fields', 'wpt_edit_term_rate_limit', 10, 2 );
		add_action( 'edit_' . $value, 'wpt_save_term_rate_limit', 10, 2 );
		add_action( 'created_' . $value, 'wpt_save_term_rate_limit', 10, 2 );
	}
}

/**
 * Save rate limit for a term.
 *
 * @param int $term_id Term ID.
 * @param int $tax_id Taxonomy ID.
 */
function wpt_save_term_rate_limit( $term_id, $tax_id ) {
	$limits     = get_option( 'wpt_rate_limit' );
	$option_set = isset( $_POST['wpt_rate_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['wpt_rate_limit'] ) ) : wpt_default_rate_limit( $term_id );
	if ( isset( $_POST['taxonomy'] ) ) {
		if ( isset( $_POST['wpt_rate_limit'] ) ) {
			$limits[ $term_id ] = $option_set;
			update_option( 'wpt_rate_limit', $limits );
		}
	}
}

/**
 * Edit term rate limits.
 *
 * @param object $term Term object.
 * @param object $taxonomy Taxonomy object.
 */
function wpt_edit_term_rate_limit( $term, $taxonomy ) {
	$t_id       = $term->term_id;
	$limits     = get_option( 'wpt_rate_limit' );
	$option_set = isset( $limits[ $t_id ] ) ? $limits[ $t_id ] : wpt_default_rate_limit( $t_id );
	?>
	<tr class="form-field">
		<th valign="top" scope="row">
			<label for="wpt_rate_limit"><?php esc_html_e( 'Max updates per hour on this term', 'wp-to-twitter' ); ?></label>
		</th>
		<td>
			<input type='number' size='4' value='<?php echo esc_attr( $option_set ); ?>' name='wpt_rate_limit' id='wpt_rate_limit' />
		</td>
	</tr>
	<?php
}

/**
 * Add a rate limit for a given term.
 *
 * @param object $term Term Object.
 */
function wpt_add_term_rate_limit( $term ) {
	$default = wpt_default_rate_limit();
	?>
	<div class="form-field">
		<label for="wpt_rate_limit"><?php esc_html_e( 'Max updates per hour on this term', 'wp-to-twitter' ); ?></label> <input type='number' value='<?php echo esc_attr( $default ); ?>' id='wpt_rate_limit' name='wpt_rate_limit' />
	</div>
	<?php
}

/**
 * View rate limit status.
 */
function wpt_view_rate_limits() {
	$limits = get_option( 'wpt_rate_limits' );
	if ( ! wp_next_scheduled( 'wptratelimits' ) ) {
		wp_schedule_event( time() + 3600, 'hourly', 'wptratelimits' );
	}
	$next_scheduled = human_time_diff( wp_next_scheduled( 'wptratelimits' ), time() );
	if ( is_array( $limits ) ) {
		?>
		<ul>
		<?php
		foreach ( $limits as $auth => $term ) {
			$author = ( 0 === (int) $auth ) ? get_option( 'wtt_twitter_username' ) : get_user_meta( $auth, 'wtt_twitter_username', true );
			?>
			<li><h4><a href='https://x.com/$author'>@<?php echo esc_html( $author ); ?></a>:</h4><ul>
				<?php
				foreach ( $term as $id => $value ) {
					$count         = count( $value );
					$term_array    = explode( '+', $id );
					$t             = $term_array[0];
					$x             = $term_array[1];
					$limit         = wpt_get_rate_limit( $t );
					$term_object   = get_term( $t, $x );
					$term_label    = $term_object->name;
					$rate_limiting = ( $count >= $limit ) ? 'rate-limited' : 'active';
					$dashicon      = ( $count >= $limit ) ? "<span class='dashicons dashicons-no' aria-hidden='true'></span>" : "<span class='dashicons dashicons-yes' aria-hidden='true'></span>";
					?>
					<li class='<?php echo esc_attr( $rate_limiting ); ?>'>
						<?php wp_kses_post( $dashicon . $term_label ); ?>:
					<?php
					// Translators: Number of tweets sent, number allowed.
					echo wp_kses_post( sprintf( _n( '%1$s update sent, %2$s allowed.', '%1$s updates sent, %2$s allowed.', $count, 'wp-to-twitter' ), "<strong>$count</strong>", "<strong>$limit</strong>" ) );
					?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
		</ul>
		<?php
	} else {
		?>
		<p><?php esc_html_e( 'No updates have been sent this hour.', 'wp-to-twitter' ); ?></p>
		<?php
	}
	// Translators: Time until next scheduled rate limiting reset.
	$next = sprintf( __( 'Next reset in %s.', 'wp-to-twitter' ), $next_scheduled );
	?>
	<p><?php echo esc_html( $next ); ?></p>
	<?php
}
