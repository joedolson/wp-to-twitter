<?php
/**
 * Class Tests_WP_To_Twitter_Update_Classification
 *
 * @package XPoster
 */

/**
 * Verify publish versus edit classification and gating behavior.
 */
class Tests_WP_To_Twitter_Update_Classification extends WP_UnitTestCase {
	/**
	 * Tracks whether a post attempted to send a status update.
	 *
	 * @var int
	 */
	protected $send_attempts = 0;

	/**
	 * Configure common options for tests.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option(
			'wpt_post_types',
			array(
				'post' => array(
					'post-published-update' => '1',
					'post-published-text'   => 'New post: #title# #url#',
					'post-edited-update'    => '0',
					'post-edited-text'      => 'Edited post: #title# #url#',
				),
			)
		);
		update_option( 'jd_tweet_default', '0' );
		update_option( 'jd_tweet_default_edit', '0' );
		$this->send_attempts = 0;
		add_action( 'wpt_post_to_service', array( $this, 'capture_send_attempt' ), 10, 3 );
	}

	/**
	 * Clean up hooks and globals.
	 */
	public function tearDown(): void {
		remove_action( 'wpt_post_to_service', array( $this, 'capture_send_attempt' ), 10 );
		unset( $_POST['edit_date'], $_POST['save'] );

		parent::tearDown();
	}

	/**
	 * Count a status send attempt.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $post_info Post information.
	 * @param string $template Template used for sending.
	 */
	public function capture_send_attempt( $post_id, $post_info, $template ) {
		++$this->send_attempts;
	}

	/**
	 * A published post saved again should be treated as an edit.
	 */
	public function test_classifier_treats_published_post_save_as_edit() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$post_info = wpt_post_info( $post_id );
		$before    = get_post( $post_id );

		$this->assertSame( 'edit', wpt_classify_post_update( $post_id, 'instant', $post_info, true, $before ) );
	}

	/**
	 * A first publish from a non-published status should be treated as publish.
	 */
	public function test_classifier_treats_first_publish_from_draft_as_publish() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$post_info           = wpt_post_info( $post_id );
		$before              = new stdClass();
		$before->post_status = 'draft';

		$this->assertSame( 'publish', wpt_classify_post_update( $post_id, 'instant', $post_info, true, $before ) );
	}

	/**
	 * Scheduled publishing context should always be treated as publish.
	 */
	public function test_classifier_treats_future_context_as_publish() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$post_info = wpt_post_info( $post_id );

		$this->assertSame( 'publish', wpt_classify_post_update( $post_id, 'future', $post_info, true, null ) );
	}

	/**
	 * Backdated classic-editor first publish should still be treated as publish.
	 */
	public function test_classifier_treats_backdated_first_publish_as_publish() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$post_info          = wpt_post_info( $post_id );
		$_POST['edit_date'] = '1';

		$this->assertSame( 'publish', wpt_classify_post_update( $post_id, 'instant', $post_info, null, null ) );
	}

	/**
	 * Editing a published post should not send when edit updates are disabled.
	 */
	public function test_post_update_does_not_send_when_edit_updates_disabled() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
				'post_title'  => 'Published post',
			)
		);

		$before = get_post( $post_id );

		wpt_post_update( $post_id, 'instant', get_post( $post_id ), true, $before );

		$this->assertSame( 0, $this->send_attempts );
	}

	/**
	 * First publish from a non-published state should still send using publish settings.
	 */
	public function test_post_update_sends_on_first_publish_from_draft() {
		$post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
				'post_title'  => 'Newly published post',
			)
		);

		$before              = new stdClass();
		$before->post_status = 'draft';

		wpt_post_update( $post_id, 'instant', get_post( $post_id ), true, $before );

		$this->assertSame( 1, $this->send_attempts );
	}
}
