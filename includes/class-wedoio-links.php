<?php
/**
 * Wedoio Watchdog functions
 */

global $wedoio_links_db_version;
$wedoio_links_db_version = '1.0';

class Wedoio_Links {
  /**
   * Update the database if needed
   */
  public static function updateDatabase() {
  }

  /**
   * Create a database for the watchdog
   */
  public static function createDatabase() {
    global $wpdb;
    global $wedoio_db_links_version;

    $table_name = $wpdb->prefix . 'wedoio_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
                id mediumint(12) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                link_type varchar(55) DEFAULT '' NOT NULL,
                uniconta_id varchar(55) NOT NULL,
                wp_id varchar(55) NOT NULL,
                PRIMARY KEY (id)
                ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option("wedoio_db_links_version", $wedoio_db_links_version);
  }

  /**
   * Log a message in the database
   * @param $key
   * @param $message
   * @param string $severity
   */
  public static function link($link_type, $uniconta_id, $wp_id) {
    if (!get_option("wedoio_db_links_version")) {
      Wedoio_Links::createDatabase();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_links';

    $record = array(
      'time' => current_time('mysql'),
      'link_type' => $link_type,
      'uniconta_id' => intval($uniconta_id),
      'wp_id' => intval($wp_id)
    );

    $insert = $wpdb->insert(
      $table_name,
      $record
    );

    return $insert;
  }

  public static function unlink($link_type, $filters) {
    if (!get_option("wedoio_db_links_version")) {
      Wedoio_Links::createDatabase();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_links';

    $filters["link_type"] = $link_type;
    $delete = $wpdb->delete($table_name, $filters);
    return $delete;
  }

  /**
   * Return logs from the database
   * @param $page
   * @param array $filters
   */
  public static function getLinks($link_type, $filters = []) {

    if (!get_option("wedoio_db_links_version")) {
      Wedoio_Links::createDatabase();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_links';

    $query = "SELECT * from $table_name";

    $where = [];

    $where[] = "link_type = '" . $link_type . "'";

    if (isset($filters['uniconta_id'])) {
      $where[] = "uniconta_id ='" . $filters['uniconta_id'] . "'";
    }

    if (isset($filters['wp_id'])) {
      $where[] = "wp_id ='" . $filters['wp_id'] . "'";
    }

    if ($where) {
      $where = " WHERE " . implode(" AND ", $where);
    } else {
      $where = "";
    }

    $query .= $where;

    $records = $wpdb->get_results($query);

    return $records;
  }
}
