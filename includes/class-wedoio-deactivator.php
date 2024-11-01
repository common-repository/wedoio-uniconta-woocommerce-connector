<?php

/**
 * Fired during plugin deactivation
 *
 * @link       Bechir
 * @since      1.0.0
 *
 * @package    Wedoio
 * @subpackage Wedoio/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/includes
 * @author
 */
class Wedoio_Deactivator {

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    1.0.0
   */
  public static function deactivate() {
    $timestamp = wp_next_scheduled('wedoio_cron_userdocs');
    wp_unschedule_event($timestamp, 'wedoio_cron_userdocs');

    $timestamp = wp_next_scheduled('wedoio_cron_pricelist');
    wp_unschedule_event($timestamp, 'wedoio_cron_pricelist');

    $timestamp = wp_next_scheduled('wedoio_cron_invoice');
    wp_unschedule_event($timestamp, 'wedoio_cron_invoice');

    $timestamp = wp_next_scheduled('wedoio_cron_batch');
    wp_unschedule_event($timestamp, 'wedoio_cron_batch');

    $timestamp = wp_next_scheduled('wedoio_cron_batch_clean');
    wp_unschedule_event($timestamp, 'wedoio_cron_batch_clean');
  }

}
