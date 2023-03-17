<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.parorrey.com
 * @since             1.0.0
 * @package           Pim_Safe_Category_Media
 *
 * @wordpress-plugin
 * Plugin Name:       PIM Safe Category Media
 * Plugin URI:        https://www.parorrey.com
 * Description:       WordPress plugin that adds a field on the Category page that allows users to upload or select an existing image in either PNG or JPEG format from the WordPress Media Library. Features incude safe deletion of media, prevents users from deleting any images from the WordPress Media Library if the image is being used in posts.
 * Version:           1.0.0
 * Author:            Ali Qureshi
 * Author URI:        https://www.parorrey.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pim-safe-category-media
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PIM_SAFE_CATEGORY_MEDIA_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pim-safe-category-media-activator.php
 */
function activate_pim_safe_category_media() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pim-safe-category-media-activator.php';
	Pim_Safe_Category_Media_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pim-safe-category-media-deactivator.php
 */
function deactivate_pim_safe_category_media() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pim-safe-category-media-deactivator.php';
	Pim_Safe_Category_Media_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pim_safe_category_media' );
register_deactivation_hook( __FILE__, 'deactivate_pim_safe_category_media' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pim-safe-category-media.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pim_safe_category_media() {

	$plugin = new Pim_Safe_Category_Media();
	$plugin->run();

}
run_pim_safe_category_media();
