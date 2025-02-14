<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSite;

/**
 * Bootstrap the plugin.
 */
function bootstrap() {
	Syndicate\bootstrap();

	add_action( 'pre_get_posts', __NAMESPACE__ . '\\remove_hidden_sites_from_post_query' );
	add_filter( 'post_link', __NAMESPACE__ . '\\syndicated_post_permalink', 10, 2 );
}

/**
 * Remove sites that are no longer being displayed from the post feed.
 *
 * @param WP_Query $query The query object.
 */
function remove_hidden_sites_from_post_query( $query ) {
	if ( is_admin() ) {
		// Make no changes in the admin.
		return;
	}

	if (
		$query->get( 'post_type' ) !== 'post'
		&& $query->get( 'post_type' ) !== array( 'post' )
		&& $query->get( 'post_type' ) !== ''
	) {
		// Only make changes to queries for posts.
		return;
	}

	$hidden_site_ids = Settings\get_term_ids_for_undisplayed_sites();
	if ( empty( $hidden_site_ids ) ) {
		// No sites are hidden.
		return;
	}

	$already_hidden = $query->get( 'category__not_in' );
	if ( ! is_array( $already_hidden ) ) {
		$already_hidden = array();
	}

	$query->set( 'category__not_in', array_merge( $already_hidden, $hidden_site_ids ) );
}

/**
 * Filter the permalink for syndicated posts.
 *
 * @param string  $permalink The post permalink.
 * @param WP_Post $post The post object.
 * @return string The permalink.
 */
function syndicated_post_permalink( $permalink, $post ) {
	if ( 'post' === $post->post_type && get_post_meta( $post->ID, 'permalink', true ) ) {
		$permalink = get_post_meta( $post->ID, 'permalink', true );
	}
	return $permalink;
}
