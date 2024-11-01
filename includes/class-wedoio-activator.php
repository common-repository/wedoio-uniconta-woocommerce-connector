<?php

/**
 * Fired during plugin activation
 *
 * @link       Bechir
 * @since      1.0.0
 *
 * @package    Wedoio
 * @subpackage Wedoio/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/includes
 * @author
 */
class Wedoio_Activator {

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    1.0.0
   */
  public static function activate() {

    if (!wp_next_scheduled('wedoio_cron_userdocs') && get_option("wedoio_cron_userdocs_active", 1)) {
      wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_userdocs');
    }

    if (!wp_next_scheduled('wedoio_cron_pricelist') && get_option("wedoio_cron_pricelist_active", 1)) {
      wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_pricelist');
    }

    if (!wp_next_scheduled('wedoio_cron_invoice') && get_option("wedoio_cron_invoice_active", 1)) {
      wp_schedule_event(time(), 'five_minutes', 'wedoio_cron_invoice');
    }

    if (!wp_next_scheduled('wedoio_cron_batch')) {
      wp_schedule_event(time(), 'every_minute', 'wedoio_cron_batch');
    }

    if (!wp_next_scheduled('wedoio_cron_batch_clean')) {
      wp_schedule_event(time(), 'hourly', 'wedoio_cron_batch_clean');
    }

    /**
     * The class responsible for the watchdog
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-watchdog.php';

    Wedoio_Watchdog::createDatabase();
  }

}
