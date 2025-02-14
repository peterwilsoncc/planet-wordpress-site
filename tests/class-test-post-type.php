<?php
/**
 * Test Template
 *
 * @package PWCC\PlanetWordPressSyndicator\Tests
 */

namespace PWCC\PlanetWordPressSyndicator\Tests;

use WP_UnitTestCase;

/**
 * Sample Tests
 */
class Test_Post_Type extends WP_UnitTestCase {

	/**
	 * Shared posts.
	 *
	 * @var array
	 */
	public static $posts = array();

	/**
	 * Shared users.
	 *
	 * @var array
	 */
	public static $users = array();

	/**
	 * Generate shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$posts = $factory->post->create_many(
			3,
			array(
				'post_type'  => 'planet_syndicated',
				'post_title' => 'Test Post 1',
			)
		);

		self::$users['author'] = $factory->user->create(
			array(
				'role' => 'author',
			)
		);

		self::$users['editor'] = $factory->user->create(
			array(
				'role' => 'editor',
			)
		);

		self::$users['admin'] = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}
}
