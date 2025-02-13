<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSite\PostTypes;

/**
 * Bootstrap the post type stuff.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\register_custom_post_types' );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\include_cpt_with_posts' );
	add_filter( 'post_type_link', __NAMESPACE__ . '\\syndicated_post_permalink', 10, 2 );
}

/**
 * Register the `planet_syndicated` custom post type.
 */
function register_custom_post_types() {
	register_post_type(
		'planet_syndicated',
		array(
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'capabilities'       => array(
				// Limit the caps so posts can only be deleted or republished.
				'create_posts'        => 'do_not_allow',
				'edit_others_posts'   => 'do_not_allow',
				'delete_posts'        => 'delete_posts',
				'delete_others_posts' => 'delete_others_posts',
				'publish_posts'       => 'publish_posts',
			),
			'map_meta_cap'       => true,
			'labels'             => array(
				'name'                     => __( 'Syndicated Posts', 'planet-wordpress-site' ),
				'singular_name'            => __( 'Syndicated Post', 'planet-wordpress-site' ),
				'search_items'             => __( 'Search Syndicated Posts', 'planet-wordpress-site' ),
				'not_found'                => __( 'No Syndicated Posts found', 'planet-wordpress-site' ),
				'not_found_in_trash'       => __( 'No Syndicated Posts found in Trash', 'planet-wordpress-site' ),
				'all_items'                => __( 'All Syndicated Posts', 'planet-wordpress-site' ),
				'archives'                 => __( 'Syndicated Post Archives', 'planet-wordpress-site' ),
				'attributes'               => __( 'Post Attributes', 'planet-wordpress-site' ),
				'filter_items_list'        => __( 'Filter Syndicated Posts list', 'planet-wordpress-site' ),
				'items_list_navigation'    => __( 'Syndicated Posts list navigation', 'planet-wordpress-site' ),
				'items_list'               => __( 'Syndicated Posts list', 'planet-wordpress-site' ),
				'item_published'           => __( 'Syndicated Post published', 'planet-wordpress-site' ),
				'item_published_privately' => __( 'Syndicated Post published privately', 'planet-wordpress-site' ),
				'item_reverted_to_draft'   => __( 'Syndicated Post reverted to draft', 'planet-wordpress-site' ),
				'item_trashed'             => __( 'Syndicated Post trashed', 'planet-wordpress-site' ),
			),
		)
	);
}

/**
 * Include the `planet_syndicated` post type in the main query.
 *
 * @param WP_Query $query The main query.
 */
function include_cpt_with_posts( $query ) {
	if (
		! is_admin()
		&& $query->is_main_query()
		&& (
			$query->get( 'post_type' ) === ''
			|| $query->get( 'post_type' ) === 'post'
			|| $query->get( 'post_type' ) === array( 'post' )
		)
	) {
		$query->set( 'post_type', array( 'post', 'planet_syndicated' ) );
	}
}


/**
 * Filter the permalink for syndicated posts.
 *
 * @param string $permalink The post permalink.
 * @param WP_Post $post The post object.
 * @return string The permalink.
 */
function syndicated_post_permalink( $permalink, $post ) {
	if ( 'planet_syndicated' === $post->post_type && get_post_meta( $post->ID, 'permalink', true ) ) {
		$permalink = get_post_meta( $post->ID, 'permalink', true );
	}

	return $permalink;
}
