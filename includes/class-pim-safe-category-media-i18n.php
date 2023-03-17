<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.parorrey.com
 * @since      1.0.0
 *
 * @package    Pim_Safe_Category_Media
 * @subpackage Pim_Safe_Category_Media/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pim_Safe_Category_Media
 * @subpackage Pim_Safe_Category_Media/includes
 * @author     Ali Qureshi <parorrey@yahoo.com>
 */
class Pim_Safe_Category_Media_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pim-safe-category-media',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
