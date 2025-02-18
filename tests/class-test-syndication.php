<?php
/**
 * Test Template
 *
 * @package PWCC\PlanetWordPressSyndicator\Tests
 */

namespace PWCC\PlanetWordPressSyndicator\Tests;

use PWCC\PlanetWordPressSyndicator\Syndicate;
use WP_UnitTestCase;

/**
 * Syndication Tests
 */
class Test_Syndication extends WP_UnitTestCase {

	/**
	 * Set up the initial posts for the syndication tests.
	 *
	 * @param \WP_UnitTest_Factory $factory The factory object.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ) {
		// Set up the test data via a request.
		self::filter_request( 'https://wordpress.org/news/feed/', 'wp-org-news-latest.rss' );
		Syndicate\syndicate_feed( 'https://wordpress.org/news/feed/' );

		// Remove the filter so it doesn't get backed up in the default setup.
		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test the feed category was created.
	 */
	public function test_feed_category_created() {
		$feed_category = get_term_by( 'name', 'WordPress News', 'category' );

		$this->assertInstanceof( 'WP_Term', $feed_category, 'The feed category should exist.' );
	}

	/**
	 * Test that the syndicated posts are created.
	 */
	public function test_syndicated_posts_created() {
		$feed_category = get_term_by( 'name', 'WordPress News', 'category' );

		// Query posts for the feed.
		$query = new \WP_Query(
			array(
				'post_status'   => 'all',
				'category_name' => $feed_category->slug,
			)
		);

		$this->assertSame( 5, $query->found_posts, 'There should be 5 posts in the feed.' );

		// Expected post titles.
		$expected_titles = array(
			'Holiday Break',
			'State of the Word 2024: Legacy, Innovation, and Community',
			'Write Books With the Block Editor',
			'Openverse.org: A Sight for Sore Eyes',
			'WordPress 6.7.1 Maintenance Release',
		);

		$actual_titles = wp_list_pluck( $query->posts, 'post_title' );

		$this->assertSame( $expected_titles, $actual_titles, 'The post titles should match.' );
	}

	/**
	 * Ensure that posts unpublished on WordPress planet are not republished.
	 *
	 * This test is to ensure that posts that are unpublished on the WordPress planet feed are not republished when the feed is updated.
	 */
	public function test_posts_unpublished_in_planet_are_not_republished_upon_feed_update() {
		$feed_category = get_term_by( 'name', 'WordPress News', 'category' );

		// Query the first published post.
		$query = new \WP_Query(
			array(
				'post_status'    => 'publish',
				'category_name'  => $feed_category->slug,
				'posts_per_page' => 1,
			)
		);

		// The query should only return one post.
		$this->assertCount( 1, $query->posts, 'There should be 1 post in query.' );

		$post_id = $query->posts[0]->ID;
		$this->assertSame( 'publish', get_post_status( $post_id ), 'The post should be published initially.' );

		// Unpublish the post.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		// The post should now be a draft.
		$this->assertSame( 'draft', get_post_status( $post_id ), 'The post should be published initially.' );

		// Update the feed.
		self::filter_request( 'https://wordpress.org/news/feed/', 'wp-org-news-latest.rss' );
		Syndicate\syndicate_feed( 'https://wordpress.org/news/feed/' );

		// The post should still be a draft.
		$this->assertSame( 'draft', get_post_status( $post_id ), 'The post should still be a draft.' );
	}

	/**
	 * Ensure that posts that are no longer in the feed are set to expired.
	 */
	public function test_posts_no_longer_in_feed_are_set_to_expired() {
		$feed_category = get_term_by( 'name', 'WordPress News', 'category' );

		// Query the last published post.
		$query = new \WP_Query(
			array(
				'post_status'    => 'publish',
				'category_name'  => $feed_category->slug,
				'posts_per_page' => 1,
				'order'          => 'DESC',
				'orderby'        => 'ID',
			)
		);

		// The query should only return one post.
		$this->assertCount( 1, $query->posts, 'There should be 1 post in query.' );

		$post_id = $query->posts[0]->ID;
		$this->assertSame( 'WordPress 6.7.1 Maintenance Release', get_the_title( $post_id ), 'The last post should be the original last post.' );
		$this->assertSame( 'publish', get_post_status( $post_id ), 'The post should be published initially.' );

		// Update the feed with a new feed that does not contain the post.
		self::filter_request( 'https://wordpress.org/news/feed/', 'wp-org-news-updated.rss' );
		Syndicate\syndicate_feed( 'https://wordpress.org/news/feed/' );

		// The post should now be expired.
		$this->assertSame( 'pwp_expired', get_post_status( $post_id ), 'The post should be expired.' );
	}

	/**
	 * Ensure that expired posts are republished when they are in the feed again.
	 */
	public function test_expired_posts_are_republished_when_in_feed_again() {
		$feed_category = get_term_by( 'name', 'WordPress News', 'category' );

		// Query the posts in the category.
		$query = new \WP_Query(
			array(
				'post_status'   => 'all',
				'category_name' => $feed_category->slug,
			)
		);

		// The query should return 5 posts.
		$this->assertCount( 5, $query->posts, 'There should be 5 posts in the feed.' );

		// Ensure all the posts are published.
		$post_statues = wp_list_pluck( $query->posts, 'post_status' );
		$this->assertSame( array( 'publish', 'publish', 'publish', 'publish', 'publish' ), $post_statues, 'All posts should be published.' );

		// Expire the first post.
		$first_post_id = $query->posts[0]->ID;
		wp_update_post(
			array(
				'ID'          => $first_post_id,
				'post_status' => 'pwp_expired',
			)
		);

		// Ensure the post is expired.
		$this->assertSame( 'pwp_expired', get_post_status( $first_post_id ), 'The first post should be expired.' );

		// Update the feed containing the expired post.
		self::filter_request( 'https://wordpress.org/news/feed/', 'wp-org-news-latest.rss' );
		Syndicate\syndicate_feed( 'https://wordpress.org/news/feed/' );

		// The post should now be published.
		$this->assertSame( 'publish', get_post_status( $first_post_id ), 'The first post should be republished.' );
	}

	/**
	 * Replace an external HTTP request with a local file.
	 *
	 * The file should be in the tests/data/requests directory.
	 *
	 * This adds a pre-flight filter to the HTTP request.
	 *
	 * @param mixed $url_to_replace   The URL of the request.
	 * @param mixed $replacement_file The file to replace the request with.
	 */
	public static function filter_request( $url_to_replace, $replacement_file ) {
		// Fail the test if the file does not exist.
		self::assertTrue( file_exists( __DIR__ . '/data/requests/' . $replacement_file ), 'The replacement file should exist.' );

		// Add the filter.
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( $url_to_replace, $replacement_file ) {
				// Normalize the URLs.
				$url            = untrailingslashit( set_url_scheme( $url ) );
				$url_to_replace = untrailingslashit( set_url_scheme( $url_to_replace ) );

				// Bail if the URLs do not match.
				if ( $url_to_replace !== $url ) {
					return $response;
				}

				// Replace the response with the file contents.
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Needed for the tests.
				$response = file_get_contents( __DIR__ . '/data/requests/' . $replacement_file );
				return array(
					'body'     => $response,
					'headers'  => array(
						'content-type' => 'application/rss+xml; charset=UTF-8',
					),
					'response' => array(
						'code' => 200,
					),
				);
			},
			10,
			3
		);
	}
}
