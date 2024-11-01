<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       Bechir
 * @since      1.0.0
 *
 * @package    Wedoio
 * @subpackage Wedoio/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/includes
 * @author
 */
class Wedoio_i18n {


  /**
   * Load the plugin text domain for translation.
   *
   * @since    1.0.0
   */
  public function load_plugin_textdomain() {

    load_plugin_textdomain(
      'wedoio',
      false,
      dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
    );

  }


}
