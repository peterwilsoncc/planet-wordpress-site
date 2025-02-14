<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSite\Settings;

/**
 * Array of syndicated feeds
 *
 * @return array[] Array if syndicated feeds {
 *     Each feed should be in the following shape:
 *
 *     @type string $title     The title of the feed. Required.
 *     @type string $feed_url  The URL of the feed. Required.
 *     @type string $site_link The URL for linking to the site. Required.
 *     @type bool   $ingest    Whether to ingest the feed. Optional. Default true.
 *     @type bool   $display   Whether to display the feed. Optional. Default true.
 * }
 */
function get_syndicated_feeds() {
	$feeds = array(
		array(
			'title'     => 'WordPress News',
			'feed_url'  => 'https://wordpress.org/news/feed/',
			'site_link' => 'https://wordpress.org/news/',
		),
		array(
			'title'     => 'WordPress Developer Blog',
			'feed_url'  => 'https://developer.wordpress.org/news/feed/',
			'site_link' => 'https://developer.wordpress.org/news/',
		),
		array(
			'title'     => 'Peter Wilson',
			'feed_url'  => 'https://peterwilson.cc/feed/planet-wordpress',
			'site_link' => 'https://peterwilson.cc/tag/wordpress/',
		),
		array(
			'title'     => 'WordPress Tavern',
			'feed_url'  => 'https://wptavern.com/feed/',
			'site_link' => 'https://wptavern.com/',
		),
		array(
			'title'     => 'Matt',
			'feed_url'  => 'https://ma.tt/feed/',
			'site_link' => 'https://ma.tt/',
		),
	);

	foreach ( $feeds as $key => $feed ) {
		if ( ! isset( $feed['ingest'] ) ) {
			$feeds[ $key ]['ingest'] = true;
		}
		if ( ! isset( $feed['display'] ) ) {
			$feeds[ $key ]['display'] = true;
		}
	}

	return $feeds;
}
