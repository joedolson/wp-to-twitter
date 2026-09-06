<?php
/**
 * Class Tests_WP_To_Twitter_Status_Update_Settings
 *
 * @package XPoster
 */

/**
 * Verify status update send/suppress expectations across post types, publish vs.
 * edit states, the global default settings, per-post overrides, and the quick
 * edit / bulk edit save paths.
 */
class Tests_WP_To_Twitter_Status_Update_Settings extends WP_UnitTestCase {

	const CUSTOM_POST_TYPE = 'wpt_cpt';

	/**
	 * Tracks whether a post attempted to send a status update.
	 *
	 * @var int
	 */
	protected $send_attempts = 0;

	/**
	 * Register the custom post type and reset settings before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! post_type_exists( self::CUSTOM_POST_TYPE ) ) {
			register_post_type(
				self::CUSTOM_POST_TYPE,
				array(
					'public' => true,
					'label'  => 'XPoster CPT'
				)
			);
		}

		update_option(
			'wpt_post_types',
			array(
				'post'                 => array(
					'post-published-update' => '1',
					'post-published-text'   => 'New post: #title# #url#',
					'post-edited-update'    => '0',
					'post-edited-text'      => 'Edited post: #title# #url#',
				),
				self::CUSTOM_POST_TYPE => array(
					'post-published-update' => '1',
					'post-published-text'   => 'New CPT: #title# #url#',
					'post-edited-update'    => '0',
					'post-edited-text'      => 'Edited CPT: #title# #url#',
				),
			)
		);
		update_option( 'jd_tweet_default', '0' );
		update_option( 'jd_tweet_default_edit', '0' );
		update_option( 'wpt_inline_edits', '0' );

		$this->send_attempts = 0;
		add_action( 'wpt_post_to_service', array( $this, 'capture_send_attempt' ), 10, 3 );
	}

	/**
	 * Clean up hooks and superglobals.
	 */
	public function tearDown(): void {
		remove_action( 'wpt_post_to_service', array( $this, 'capture_send_attempt' ), 10 );
		$_POST = array();

		parent::tearDown();
	}

	/**
	 * Count a status send attempt.
	 */
	public function capture_send_attempt() {
		++$this->send_attempts;
	}

	/**
	 * Post types exercised by the settings matrix.
	 *
	 * @return array
	 */
	public static function post_type_provider() {
		return array(
			'built-in post type' => array( 'post' ),
			'custom post type'   => array( self::CUSTOM_POST_TYPE ),
		);
	}

	/**
	 * Update a single per-type setting within the wpt_post_types option.
	 *
	 * @param string $type Post type.
	 * @param string $key Setting key.
	 * @param string $value Setting value.
	 */
	protected function set_type_setting( $type, $key, $value ) {
		$settings                  = get_option( 'wpt_post_types' );
		$settings[ $type ][ $key ] = $value;
		update_option( 'wpt_post_types', $settings );
	}

	/**
	 * Create a published post fixture of the given type.
	 *
	 * @param string $type Post type.
	 *
	 * @return int
	 */
	protected function create_post( $type ) {
		return self::factory()->post->create(
			array(
				'post_type'   => $type,
				'post_status' => 'publish',
				'post_title'  => 'Status update fixture',
			)
		);
	}

	/**
	 * Run wpt_post_update as a first publish (prior status was not publish).
	 *
	 * @param int $post_id Post ID.
	 */
	protected function run_as_publish( $post_id ) {
		$before              = new stdClass();
		$before->post_status = 'draft';
		wpt_post_update( $post_id, 'instant', get_post( $post_id ), true, $before );
	}

	/**
	 * Run wpt_post_update as an edit of an already-published post.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function run_as_edit( $post_id ) {
		$before              = new stdClass();
		$before->post_status = 'publish';
		wpt_post_update( $post_id, 'instant', get_post( $post_id ), true, $before );
	}

	// -----------------------------------------------------------------
	// Publish state.
	// -----------------------------------------------------------------

	/**
	 * Publish should send when post-published-update is enabled.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_publish_sends_when_publish_update_enabled( $type ) {
		$this->set_type_setting( $type, 'post-published-update', '1' );
		$post_id = $this->create_post( $type );

		$this->run_as_publish( $post_id );

		$this->assertSame( 1, $this->send_attempts );
	}

	/**
	 * Publish should not send when post-published-update is disabled and there is no override.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_publish_blocked_when_publish_update_disabled( $type ) {
		$this->set_type_setting( $type, 'post-published-update', '0' );
		$post_id = $this->create_post( $type );

		$this->run_as_publish( $post_id );

		$this->assertSame( 0, $this->send_attempts );
	}

	/**
	 * An explicit "yes" override should force a send on publish even when disabled.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_publish_sends_with_explicit_yes_override_when_disabled( $type ) {
		$this->set_type_setting( $type, 'post-published-update', '0' );
		$post_id = $this->create_post( $type );
		update_post_meta( $post_id, '_wpt_post_this', 'yes' );

		$this->run_as_publish( $post_id );

		$this->assertSame( 1, $this->send_attempts );
	}

	// -----------------------------------------------------------------
	// Edit state.
	// -----------------------------------------------------------------

	/**
	 * Editing a published post should send when post-edited-update is enabled.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_edit_sends_when_edit_update_enabled( $type ) {
		$this->set_type_setting( $type, 'post-edited-update', '1' );
		$post_id = $this->create_post( $type );

		$this->run_as_edit( $post_id );

		$this->assertSame( 1, $this->send_attempts );
	}

	/**
	 * Editing a published post must not send when post-edited-update is disabled and there is no override.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_edit_blocked_when_edit_update_disabled( $type ) {
		$this->set_type_setting( $type, 'post-edited-update', '0' );
		$post_id = $this->create_post( $type );

		$this->run_as_edit( $post_id );

		$this->assertSame( 0, $this->send_attempts );
	}

	/**
	 * An explicit "yes" override should force a send on edit even when disabled.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_edit_sends_with_explicit_yes_override_when_disabled( $type ) {
		$this->set_type_setting( $type, 'post-edited-update', '0' );
		$post_id = $this->create_post( $type );
		update_post_meta( $post_id, '_wpt_post_this', 'yes' );

		$this->run_as_edit( $post_id );

		$this->assertSame( 1, $this->send_attempts );
	}

	/**
	 * An explicit "no" override should suppress a send on edit even when enabled.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_edit_blocked_with_explicit_no_override_when_enabled( $type ) {
		$this->set_type_setting( $type, 'post-edited-update', '1' );
		$post_id = $this->create_post( $type );
		update_post_meta( $post_id, '_wpt_post_this', 'no' );

		$this->run_as_edit( $post_id );

		$this->assertSame( 0, $this->send_attempts );
	}

	// -----------------------------------------------------------------
	// Global "jd_tweet_default" setting.
	// -----------------------------------------------------------------

	/**
	 * When the global default is "post automatically", an explicit "no" override always blocks.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_global_default_post_allows_but_explicit_no_blocks( $type ) {
		update_option( 'jd_tweet_default', '0' );
		$this->set_type_setting( $type, 'post-published-update', '1' );
		$this->set_type_setting( $type, 'post-edited-update', '1' );
		$post_id = $this->create_post( $type );
		update_post_meta( $post_id, '_wpt_post_this', 'no' );

		$this->run_as_publish( $post_id );

		$this->assertSame( 0, $this->send_attempts );
	}

	/**
	 * When the global default is "do not post by default", nothing sends without an explicit "yes".
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_global_default_no_post_blocks_without_override( $type ) {
		update_option( 'jd_tweet_default', '1' );
		$this->set_type_setting( $type, 'post-published-update', '1' );
		$this->set_type_setting( $type, 'post-edited-update', '1' );
		$post_id = $this->create_post( $type );

		$this->run_as_publish( $post_id );
		$this->run_as_edit( $post_id );

		$this->assertSame( 0, $this->send_attempts );
	}

	/**
	 * When the global default is "do not post by default", an explicit "yes" override still sends.
	 *
	 * @dataProvider post_type_provider
	 *
	 * @param string $type Post type.
	 */
	public function test_global_default_no_post_allows_explicit_yes_override( $type ) {
		update_option( 'jd_tweet_default', '1' );
		$this->set_type_setting( $type, 'post-published-update', '0' );
		$this->set_type_setting( $type, 'post-edited-update', '0' );
		$post_id = $this->create_post( $type );
		update_post_meta( $post_id, '_wpt_post_this', 'yes' );

		$this->run_as_edit( $post_id );

		$this->assertSame( 1, $this->send_attempts );
	}

	// -----------------------------------------------------------------
	// Quick edit.
	// -----------------------------------------------------------------

	/**
	 * Quick edit submissions must not alter the persisted _wpt_post_this override.
	 */
	public function test_quick_edit_does_not_modify_post_this_meta() {
		$post_id = $this->create_post( 'post' );
		update_post_meta( $post_id, '_wpt_post_this', 'yes' );

		$_POST = array(
			'_inline_edit'        => '1',
			'wp_to_twitter_meta'  => '1',
			'_wpt_post_this'      => 'no',
			'wp_to_twitter_nonce' => wp_create_nonce( 'wp-to-twitter-nonce' ),
		);

		wpt_save_post( $post_id, get_post( $post_id ) );

		$this->assertSame( 'yes', get_post_meta( $post_id, '_wpt_post_this', true ) );
	}

	/**
	 * Quick edit saves must not trigger a status update attempt.
	 */
	public function test_quick_edit_does_not_trigger_status_update() {
		$this->set_type_setting( 'post', 'post-edited-update', '1' );
		$post_id = $this->create_post( 'post' );
		$before  = get_post( $post_id );

		$_POST['_inline_edit'] = '1';

		wpt_do_post_update( $post_id, get_post( $post_id ), true, $before );

		$this->assertSame( 0, $this->send_attempts );
	}

	// -----------------------------------------------------------------
	// Bulk edit.
	// -----------------------------------------------------------------

	/**
	 * Simulate the $_POST payload present for a classic bulk edit save (no metabox fields).
	 *
	 * @return array
	 */
	protected function bulk_edit_post_data() {
		return array(
			'wp_to_twitter_meta'  => '1',
			'wp_to_twitter_nonce' => wp_create_nonce( 'wp-to-twitter-nonce' ),
			'bulk_edit'           => 'Update',
		);
	}

	/**
	 * With no explicit field submitted, bulk edit of a published post must default to "no" when edit updates are disabled.
	 */
	public function test_bulk_edit_defaults_post_this_to_no_when_edit_updates_disabled() {
		$this->set_type_setting( 'post', 'post-edited-update', '0' );
		$post_id = $this->create_post( 'post' );
		delete_post_meta( $post_id, '_wpt_post_this' );

		$_POST = $this->bulk_edit_post_data();

		wpt_save_post( $post_id, get_post( $post_id ) );

		$this->assertSame( 'no', get_post_meta( $post_id, '_wpt_post_this', true ) );
	}

	/**
	 * With no explicit field submitted, bulk edit of a published post should default to "yes" when edit updates are enabled.
	 */
	public function test_bulk_edit_defaults_post_this_to_yes_when_edit_updates_enabled() {
		$this->set_type_setting( 'post', 'post-edited-update', '1' );
		$post_id = $this->create_post( 'post' );
		delete_post_meta( $post_id, '_wpt_post_this' );

		$_POST = $this->bulk_edit_post_data();

		wpt_save_post( $post_id, get_post( $post_id ) );

		$this->assertSame( 'yes', get_post_meta( $post_id, '_wpt_post_this', true ) );
	}

	/**
	 * Test: wpt_bulk_edit_posts() should send updates only when the "process bulk edits" option is enabled.
	 */
	public function test_wpt_bulk_edit_posts_sends_when_inline_edits_enabled() {
		update_option( 'wpt_inline_edits', '1' );
		$this->set_type_setting( 'post', 'post-published-update', '1' );
		$this->set_type_setting( 'post', 'post-edited-update', '1' );
		$post_id = $this->create_post( 'post' );

		wpt_bulk_edit_posts( array( $post_id ) );

		$this->assertSame( 1, $this->send_attempts );
	}

	/**
	 * Test: wpt_bulk_edit_posts() should do nothing when the "process bulk edits" option is disabled.
	 */
	public function test_wpt_bulk_edit_posts_does_nothing_when_inline_edits_disabled() {
		update_option( 'wpt_inline_edits', '0' );
		$this->set_type_setting( 'post', 'post-published-update', '1' );
		$this->set_type_setting( 'post', 'post-edited-update', '1' );
		$post_id = $this->create_post( 'post' );

		wpt_bulk_edit_posts( array( $post_id ) );

		$this->assertSame( 0, $this->send_attempts );
	}
}
