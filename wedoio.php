<?php

/**
 * @since             1.10.1
 * @package           Wedoio
 *
 * @wordpress-plugin
 * Plugin Name:       Uniconta WooCommerce Connector
 * Plugin URI:        wedoio.com
 * Description:       WooCommerce Uniconta integration synchronizes your WooCommerce Orders, Customers, Customer specific Pricing, Delivery addresses, stock and Products to your Uniconta accounting system. Uniconta SALES ORDERs can be automatically created. The SALES ORDER and PRODUCT sync features require a license purchase from http://wedoio.com. WooCommerce Uniconta integration plugin connects to license server hosted at http://wedoio.com to check the validity of the license key you type in the settings page.
 * Version:           3.1.10
 * Author:            wedoio
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wedoio
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

define('WEDOIO_VERSION', '3.1.10');
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wedoio-activator.php
 */
function activate_wedoio() {
  require_once plugin_dir_path(__FILE__) . 'includes/class-wedoio-activator.php';
  if (is_admin() && current_user_can('activate_plugins') && !is_plugin_active('woocommerce/woocommerce.php')) {
    wp_die("This plugin Requires Woocommerce to be activated.");
  } else {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wedoio-activator.php';
    Wedoio_Activator::activate();
  }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wedoio-deactivator.php
 */
function deactivate_wedoio() {
  require_once plugin_dir_path(__FILE__) . 'includes/class-wedoio-deactivator.php';
  Wedoio_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wedoio');
register_deactivation_hook(__FILE__, 'deactivate_wedoio');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wedoio.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wedoio() {

  $plugin = new Wedoio();
  $plugin->run();

}

run_wedoio();
