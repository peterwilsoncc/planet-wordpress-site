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
class Test_Settings extends WP_UnitTestCase {

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
	 * Undisplayed feeds should not show in the query.
	 */
	public function test_remove_undisplayed_sites_from_post_query() {
		$this->go_to( '/' );
		$this->assertNotEmpty( $GLOBALS['wp_query']->posts, 'Prior to filtering the feed should appear in the query.' );

		// Filter the feed to be undisplayed.
		add_filter(
			'pwp_syndicated_feeds',
			function () {
				return array(
					array(
						'title'     => 'WordPress News',
						'feed_url'  => 'https://wordpress.org/news/feed/',
						'site_link' => 'https://wordpress.org/news/',
						'ingest'    => true,
						'display'   => false,
					),
				);
			}
		);

		$this->go_to( '/' );
		$this->assertEmpty( $GLOBALS['wp_query']->posts, 'The undisplayed feed should not be in the query.' );
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
