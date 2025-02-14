<?php
/**
 * Planet WordPress Syndicator
 *
 * @package           PlanetWordPressSyndicator
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 */

namespace PWCC\PlanetWordPressSyndicator\Widget;

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
