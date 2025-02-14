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

use PWCC\PlanetWordPressSite\Settings;
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
	$feeds = Settings\get_syndicated_feeds();
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
	$feeds     = Settings\get_syndicated_feeds();
	$feed_urls = wp_list_pluck( $feeds, 'feed_url' );

	if ( ! in_array( $feed_url, $feed_urls, true ) ) {
		// Delete the cron job, the feed is no longer syndicated.
		wp_clear_scheduled_hook( 'pwp_syndicate_feed', array( $feed_url ) );
		return;
	}

	// Get the feed details from the option.
	$feed_data = wp_list_filter( $feeds, array( 'feed_url' => $feed_url ) );
	$feed_data = reset( $feed_data );

	if ( false === $feed_data['ingest'] ) {
		// Registered but not ingesting at this time.
		return;
	}

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

	$term = maybe_create_category( $feed_data );
	if ( is_wp_error( $term ) ) {
		// Something went wrong creating/getting the category.
		return;
	}

	// Syndicate the feed items.
	foreach ( $response->get_items() as $item ) {
		syndicate_item( $item, $feed_data, $term['term_id'] );
	}
}

/**
 * Create or update the category for a feed.
 *
 * Creates a category for the feed based on the feed URL. If a category
 * exists, the name will be updated if it has changed.
 *
 * @param array $feed_data The feed data.
 * @return array|WP_Error The term data or a WP_Error object.
 */
function maybe_create_category( $feed_data ) {
	// Base the slug on the feed URL to allow for the site name to change.
	$term_slug  = hash( 'sha256', $feed_data['feed_url'] );
	$term_title = wp_strip_all_tags( $feed_data['title'] );

	$term                  = get_term_by( 'slug', $term_slug, 'category' );
	$term_syndication_link = get_term_meta( $term->term_id, 'syndication_link', true );

	if ( false === $term ) {
		$new_term = wp_insert_term(
			$term_title,
			'category',
			array(
				'slug' => $term_slug,
			)
		);

		update_term_meta( $new_term['term_id'], 'syndication_link', wp_slash( esc_url_raw( $feed_data['site_link'] ) ) );
		return $new_term;
	}

	// Update the term if the name or site link has changed.
	if ( $term->name !== $term_title || $term_syndication_link !== $feed_data['site_link'] ) {
		$new_term = wp_update_term(
			$term->term_id,
			'category',
			array(
				'name' => $term_title,
			)
		);

		update_term_meta( $new_term['term_id'], 'syndication_link', wp_slash( esc_url_raw( $feed_data['site_link'] ) ) );
		return $new_term;
	}

	return array(
		'term_id'          => $term->term_id,
		'term_taxonomy_id' => $term->term_taxonomy_id,
	);
}

/**
 * Syndicate a feed item.
 *
 * Publishes the post as a syndicated post. If a post exists with the GUID
 * already, that post will be updated.
 *
 * @param object $item      The feed item to syndicate.
 * @param array  $feed_data The feed data.
 * @param int    $term_id   The term ID for the feed.
 */
function syndicate_item( $item, $feed_data, $term_id ) {
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
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'draft' ),
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
		$old_post = get_post( $post_id );
	}

	$post_timestamp = $item->get_date( 'U' );
	$mysql_date_gmt = gmdate( 'Y-m-d H:i:s', $post_timestamp );

	$post_data = array(
		'post_title'     => wp_strip_all_tags( $item->get_title() ),
		'post_content'   => wp_kses_post( $item->get_content() ),
		'post_excerpt'   => wp_kses_post( $item->get_description() ),
		'post_date_gmt'  => $mysql_date_gmt,
		'post_status'    => 'publish',
		'post_type'      => 'post',
		'post_name'      => $post_slug,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'to_ping'        => '',
		'meta_input'     => array(
			'syndicated_feed_guid' => $item_guid,
			'syndicated_feed_url'  => $feed_data['site_link'],
			'permalink'            => esc_url_raw( $item->get_permalink() ),
		),
		'post_category'  => array( $term_id ),
	);

	if ( $updating ) {
		$post_data['ID'] = $post_id;
		// Do not update the time.
		unset( $post_data['post_date_gmt'] );
		// Nor the post status, it may have been unpublished intentionally.
		unset( $post_data['post_status'] );

		// Check if the post is unchanged.
		$old_source_permalink = get_post_meta( $post_id, 'permalink', true );
		if (
			$old_post->post_title === $post_data['post_title']
			&& $old_post->post_content === $post_data['post_content']
			&& $old_post->post_excerpt === $post_data['post_excerpt']
			&& $old_source_permalink === $post_data['meta_input']['permalink']
		) {
			// Bypass the update, nothing has changed.
			return;
		}
		wp_update_post( $post_data );
		return;
	}

	$post_id = wp_insert_post( $post_data );
}
