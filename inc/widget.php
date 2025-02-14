<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSite\Widget;

use PWCC\PlanetWordPressSite\Settings;

/**
 * Bootstrap the widget.
 */
function bootstrap() {
	add_action( 'widgets_init', __NAMESPACE__ . '\\register' );
}

/**
 * Register the widget.
 */
function register() {
	register_widget( __NAMESPACE__ . '\\Widget' );
}

/**
 * Widget class.
 *
 * The widget class to display links to each of the displayed
 * syndicated feeds.
 */
class Widget extends \WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'planet_wordpress_site_widget',
			__( 'Planet WordPress Site', 'planet-wordpress-site' ),
			array(
				'description' => __( 'Display the syndicated feeds.', 'planet-wordpress-site' ),
			)
		);
	}

	/**
	 * Output the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		$feeds = Settings\get_displayed_feeds();
		$feeds = wp_list_sort( $feeds, 'title' );

		echo $args['before_widget'];

		foreach ( $feeds as $feed ) {
			if ( ! $feed['display'] ) {
				continue;
			}

			echo '<ul>';
			echo '<li><a href="' . esc_url( $feed['site_link'] ) . '">' . esc_html( $feed['title'] ) . '</a> <a href="' . esc_url( $feed['feed_url'] ) . '">(feed)</a></li>';

			echo '</ul>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Output the widget form.
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		// No form fields.
	}

	/**
	 * Update the widget instance.
	 *
	 * @param array $new_instance New widget instance.
	 * @param array $old_instance Old widget instance.
	 * @return array Updated widget instance.
	 */
	public function update( $new_instance, $old_instance ) {
		return $old_instance;
	}
}
