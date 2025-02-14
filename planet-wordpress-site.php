<?php
/**
 * Planet WordPress Site
 *
 * @package           PlanetWordPressSite
 * @author            Peter Wilson
 * @copyright         2025 Peter Wilson
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name: Planet WordPress Site
 * Description: A POC plugin for the Planet WordPress Site.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Peter Wilson
 * Author URI: https://peterwilson.cc
 * License: MIT
 * Text Domain: planet-wordpress-site
 */

namespace PWCC\PlanetWordPressSite;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/syndicate.php';
require_once __DIR__ . '/inc/widget.php';

bootstrap();
