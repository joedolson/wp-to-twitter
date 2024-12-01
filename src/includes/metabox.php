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
	<p class="wpt-support">
	<?php
	if ( 'pro' === $is_pro ) {
		?>
		<a href="<?php echo esc_url( add_query_arg( 'tab', 'support', admin_url( 'admin.php?page=wp-tweets-pro' ) ) ); ?>#get-support"><?php _e( 'Get Support', 'wp-to-twitter' ); ?></a> &raquo;
		<?php
	} else {
		?>
		<p class="link-highlight">
			<a href="https://xposterpro.com/awesome/xposter-pro/"><?php _e( 'Buy XPoster Pro', 'wp-to-twitter' ); ?></a>
		</p>
		<?php
	}
	?>
	</p>
	<?php
}