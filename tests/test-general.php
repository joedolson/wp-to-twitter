<?php
/**
 * Class Tests_WP_to_Twitter_General
 *
 * @package WP to Twitter
 */

/**
 * Sample test case.
 */
class Tests_WP_To_Twitter_General extends WP_UnitTestCase {
	/**
	 * Verify not in debug mode.
	 */
	public function test_wpt_not_in_debug_mode() {
		// Verify that the constant WPT_DEBUG is false.
		$this->assertFalse( WPT_DEBUG );
	}
}
