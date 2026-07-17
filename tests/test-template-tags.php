<?php
/**
 * Class Tests_WP_To_Twitter_Template_Tags
 *
 * @package XPoster
 */

/**
 * Verify template tag value generation and parsing.
 */
class Tests_WP_To_Twitter_Template_Tags extends WP_UnitTestCase {
	/**
	 * Ensure options used by value generation are predictable.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'blogname', 'XPoster Test Blog' );
		update_option( 'wtt_twitter_username', 'siteacct' );
		update_option( 'jd_post_excerpt', 200 );
		update_option( 'wpt_x_length', 1000 );
		update_option( 'jd_strip_nonan', '0' );
		update_option( 'wpt_use_cats', '0' );
	}

	/**
	 * Build a post fixture with deterministic values for tag expansion.
	 *
	 * @return int
	 */
	protected function build_post_fixture() {
		$author_id = self::factory()->user->create(
			array(
				'display_name' => 'Author Display',
			)
		);

		update_user_meta( $author_id, 'wtt_twitter_username', 'authoracct' );

		$category_id = self::factory()->category->create(
			array(
				'name'        => 'News',
				'description' => 'News category description',
			)
		);

		$post_id = self::factory()->post->create(
			array(
				'post_author'  => $author_id,
				'post_title'   => 'Template Title',
				'post_content' => 'Template excerpt body for post tag testing.',
				'post_excerpt' => 'Template Excerpt',
				'post_status'  => 'publish',
				'post_date'    => '2026-07-17 12:00:00',
			)
		);

		wp_set_post_categories( $post_id, array( $category_id ) );
		wp_set_post_tags( $post_id, array( 'AlphaTag' ) );

		return $post_id;
	}

	/**
	 * All core template tags should map to the exact values generated for this post.
	 */
	public function test_create_values_maps_all_core_tags() {
		$post_id   = $this->build_post_fixture();
		$post_info = wpt_post_info( $post_id );
		$values    = wpt_create_values( $post_info, $post_id, false, 'x' );
		$tags      = wpt_tags();

		$this->assertSame( $tags, array_keys( $values ) );
		$this->assertSame( 'Template Title', $values['title'] );
		$this->assertSame( 'XPoster Test Blog', $values['blog'] );
		$this->assertSame( 'Template Excerpt', $values['post'] );
		$this->assertSame( 'News', $values['category'] );
		$this->assertSame( 'News', $values['categories'] );
		$this->assertSame( 'News category description', $values['cat_desc'] );
		$this->assertSame( '@authoracct', $values['author'] );
		$this->assertSame( 'Author Display', $values['displayname'] );
		$this->assertSame( '@siteacct', $values['account'] );
		$this->assertSame( '@authoracct', $values['@'] );
		$this->assertStringContainsString( '#AlphaTag', $values['tags'] );
		$this->assertNotSame( '', $values['date'] );
		$this->assertNotSame( '', $values['modified'] );
		$this->assertSame( '', $values['reference'] );
		$this->assertNotSame( '', $values['url'] );
		$this->assertSame( $post_info['postLink'], $values['longurl'] );
	}

	/**
	 * Parsing should replace every template tag with the corresponding passed value.
	 */
	public function test_truncate_status_replaces_all_tags_with_passed_values() {
		$post_id   = $this->build_post_fixture();
		$post_info = wpt_post_info( $post_id );
		$values    = wpt_create_values( $post_info, $post_id, false, 'x' );
		$tags      = array_map( 'wpt_make_tag', wpt_tags() );

		$separator = '|||WPTSEP|||';
		$template  = implode( $separator, $tags );
		$parsed    = wpt_truncate_status( $template, $post_info, $post_id, false, false, 'x' );
		$parts     = explode( $separator, $parsed );

		$this->assertCount( count( $values ), $parts );
		$this->assertSame( array_values( $values ), $parts );
	}
}
