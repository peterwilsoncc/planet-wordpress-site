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
 * Bootstrap the settings.
 */
function bootstrap() {
	add_action( 'admin_init', __NAMESPACE__ . '\\init_settings' );

	add_action( 'admin_menu', __NAMESPACE__ . '\\init_options_page' );
}

/**
 * Register the settings page under the options menu.
 */
function init_options_page() {
	add_options_page(
		'Planet WordPress Site',
		'Planet WordPress Site',
		'manage_options',
		'planet-wordpress-site',
		__NAMESPACE__ . '\\render_options_page'
	);
}

/**
 * Initialise the settings fields.
 */
function init_settings() {
	register_setting(
		'planet-wordpress-site',
		'pwp-syndicated_feeds',
		array(
			'type'              => 'array',

			'label'             => 'Syndicated Feeds',
			'description'       => 'A list of feeds to syndicate.',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_syndicated_feeds',
		),
	);

	add_settings_section(
		'default',
		'Default',
		'__return_empty_string',
		'planet-wordpress-site'
	);

	add_settings_field(
		'pwp-syndicated_feeds',
		'Syndicated Feeds',
		__NAMESPACE__ . '\\render_syndicated_feeds_field',
		'planet-wordpress-site',
		'default'
	);
}

/**
 * Sanitize the syndicated feeds for saving.
 *
 * @param array $feeds The feeds to sanitize.
 * @return array The sanitized feeds.
 */
function sanitize_syndicated_feeds( $feeds ) {
	$sanitized = array();
	$count     = 0;

	foreach ( $feeds as $feed ) {
		if ( ! empty( $feed['title'] ) && ! empty( $feed['feed_url'] ) ) {
			$sanitized[ $count ] = array(
				'title'     => sanitize_text_field( $feed['title'] ),
				'feed_url'  => esc_url_raw( $feed['feed_url'] ),
				'site_link' => esc_url_raw( $feed['site_link'] ),
			);

			if ( empty( $sanitized[ $count ]['site_link'] ) ) {
				// Remove `/feed/` or `/feed` from the end of the feed url.
				$sanitized[ $count ]['site_link'] = preg_replace( '/\/feed\/?$/', '', $sanitized[ $count ]['feed_url'] );
			}

			++$count;
		}
	}

	return $sanitized;
}

/**
 * Render the options page.
 */
function render_options_page() {
	?>
	<div class="wrap">
		<h1>Planet WordPress Site</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'planet-wordpress-site' );
			do_settings_sections( 'planet-wordpress-site' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Render the syndicated feeds field.
 */
function render_syndicated_feeds_field() {
	$feeds = get_option( 'pwp-syndicated_feeds', array() );
	$count = 0;

	$feeds = wp_list_sort(
		$feeds,
		array(
			'title' => 'ASC',
		)
	);

	?>
	<table>
		<tr>
			<th>Site Title</th>
			<th>Feed URL</th>
			<th>Site link</th>
		</tr>
	<?php
	foreach ( $feeds as $feed ) {
		echo '<tr>';
		echo '<td><input type="text" name="pwp-syndicated_feeds[' . (int) $count . '][title]" value="' . esc_attr( $feed['title'] ) . '"></td>';
		echo '<td><input type="url" name="pwp-syndicated_feeds[' . (int) $count . '][feed_url]" value="' . esc_url( $feed['feed_url'] ) . '"></td>';
		echo '<td><input type="url" name="pwp-syndicated_feeds[' . (int) $count . '][site_link]" value="' . esc_url( $feed['site_link'] ) . '"></td>';
		echo '</tr>';
		++$count;
	}

	echo '<tr>';
	echo '<td><input type="text" name="pwp-syndicated_feeds[' . ( (int) $count + 1 ) . '][title]" value=""></td>';
	echo '<td><input type="url" name="pwp-syndicated_feeds[' . ( (int) $count + 1 ) . '][feed_url]" value=""></td>';
	echo '<td><input type="url" name="pwp-syndicated_feeds[' . ( (int) $count + 1 ) . '][site_link]" value=""></td>';
	echo '</tr>';
	echo '</table>';
}
