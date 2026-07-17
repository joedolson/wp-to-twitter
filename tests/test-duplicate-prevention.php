<?php
/**
 * Class Tests_WP_To_Twitter_Duplicate_Prevention
 *
 * @package XPoster
 */

/**
 * Verify duplicate-prevention logic for status updates.
 */
class Tests_WP_To_Twitter_Duplicate_Prevention extends WP_UnitTestCase {
	/**
	 * Clean option/transient state before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( 'wpt_disabled_services' );
		delete_option( 'wpt_last_x' );
		update_option( 'wtt_twitter_username', 'siteacct' );
		update_option( 'wpt_x_length', 1000 );
		update_option( 'jd_post_excerpt', 200 );
	}

	/**
	 * Clean persisted state after each test.
	 */
	public function tearDown(): void {
		delete_option( 'wpt_last_x' );

		parent::tearDown();
	}

	/**
	 * Build a simple published post fixture.
	 *
	 * @return int
	 */
	protected function create_published_post() {
		return self::factory()->post->create(
			array(
				'post_title'   => 'Duplicate Test Post',
				'post_content' => 'Content for duplicate prevention testing.',
				'post_excerpt' => 'Duplicate excerpt',
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * The second immediate check should be blocked by the recent transient gate.
	 */
	public function test_recent_transient_blocks_immediate_duplicate() {
		$post_id = $this->create_published_post();

		$this->assertFalse( wpt_check_recent_tweet( $post_id, false ) );
		$this->assertTrue( wpt_check_recent_tweet( $post_id, false ) );
	}

	/**
	 * If rendered status matches last sent value, the service should be blocked.
	 */
	public function test_prepare_post_blocks_identical_last_sent_status() {
		$post_id  = $this->create_published_post();
		$template = 'New post: #title# #url#';
		$status   = wpt_truncate_status( $template, array(), $post_id, false, false, 'x' );
		$services = array( 'x' => true );

		update_option( 'wpt_last_x', $status );

		$checks = wpt_prepare_post( $post_id, false, $template, $services );

		$this->assertArrayHasKey( 'x', $checks );
		$this->assertFalse( $checks['x'] );
	}
}
