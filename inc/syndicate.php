<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSite\Syndicate;

use WP_Query;

/**
 * Bootstrap the syndication code.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_cron_jobs' );
	add_action( 'pwp_syndicate_feed', __NAMESPACE__ . '\\syndicate_feed', 10, 1 );
}

/**
 * Register a wp-cron job for each feed.
 */
function register_cron_jobs() {
	$feeds = get_option( 'pwp-syndicated_feeds', array() );
	foreach ( $feeds as $feed ) {
		$timestamp = wp_next_scheduled( 'pwp_syndicate_feed', array( $feed['feed_url'] ) );
		if ( false === $timestamp ) {
			wp_schedule_event( time(), 'hourly', 'pwp_syndicate_feed', array( $feed['feed_url'] ) );
		}
	}
}

/**
 * Read a feed and syndicate the content.
 *
 * @param string $feed_url The URL of the feed to syndicate.
 */
function syndicate_feed( $feed_url ) {
	// First ensure that the feed is in the option.
	$feeds     = get_option( 'pwp-syndicated_feeds', array() );
	$feed_urls = wp_list_pluck( $feeds, 'feed_url' );

	if ( ! in_array( $feed_url, $feed_urls, true ) ) {
		// Delete the cron job, the feed is no longer syndicated.
		wp_clear_scheduled_hook( 'pwp_syndicate_feed', array( $feed_url ) );
		return;
	}

	// Get the feed details from the option.
	$feed_data = wp_list_filter( $feeds, array( 'feed_url' => $feed_url ) );

	// Fetch the feed.
	$response = fetch_feed( $feed_url );

	// If the feed could not be fetched, do not continue.
	if ( is_wp_error( $response ) ) {
		return 'is error';
	}

	// If the feed doesn't have any items, do not continue.
	if ( empty( $response->get_items() ) ) {
		return 'no items';
	}

	// Syndicate the feed items.
	foreach ( $response->get_items() as $item ) {
		syndicate_item( $item, reset( $feed_data ) );
	}
}

/**
 * Syndicate a feed item.
 *
 * Publishes the post as a syndicated post. If a post exists with the GUID
 * already, that post will be updated.
 *
 * @param object $item      The feed item to syndicate.
 * @param array  $feed_data The feed data.
 */
function syndicate_item( $item, $feed_data ) {
	$item_guid = $item->get_id();

	/*
	 * Hash the GUID to create a unique post slug.
	 *
	 * This is a convenient way to ensure the post slug for each
	 * syndicated post is unique. It is not a security measure,
	 * therefore it is not necessary to use a salt.
	 */
	$post_slug = hash( 'sha256', $item_guid );

	// Check if the item has already been syndicated.
	$query = new WP_Query(
		array(
			'post_type'              => 'planet_syndicated',
			'post_status'            => 'any',
			'name'                   => $post_slug,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
		)
	);

	$updating = false;
	if ( $query->have_posts() ) {
		$updating = true;
		$post_id  = $query->posts[0]->ID;
	}

	$post_timestamp = $item->get_date( 'U' );
	$mysql_date_gmt = gmdate( 'Y-m-d H:i:s', $post_timestamp );

	$post_data = array(
		'post_title'    => sanitize_text_field( "{$feed_data['title']}: {$item->get_title()}" ),
		'post_content'  => wp_kses_post( $item->get_content() ),
		'post_excerpt'  => wp_kses_post( $item->get_description() ),
		'post_date_gmt' => $mysql_date_gmt,
		'post_status'   => 'publish',
		'post_type'     => 'planet_syndicated',
		'post_name'     => $post_slug,
		'meta_input'    => array(
			'syndicated_feed_guid' => $item_guid,
			'syndicated_feed_url'  => $feed_data['site_link'],
			'permalink'            => esc_url_raw( $item->get_permalink() ),
		),
	);

	if ( $updating ) {
		$post_data['ID'] = $post_id;
		// Do not update the time.
		unset( $post_data['post_date_gmt'] );
		// Nor the post status, it may have been unpublished intentionally.
		unset( $post_data['post_status'] );
	}

	$post_id = wp_insert_post( $post_data );
}
