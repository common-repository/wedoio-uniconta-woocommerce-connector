<?php
/**
 * Wedoio Watchdog functions
 */

global $wedoio_db_version;
$wedoio_db_version = '1.0';

class Wedoio_Watchdog {
  /**
   * Update the database if needed
   */
  public static function updateDatabase() {
  }

  /**
   * Clear the logs
   * @param int $treshold
   */
  public static function clearLogs($max_logs = 1000) {
    if (!get_option("wedoio_db_version")) {
      Wedoio_Watchdog::createDatabase();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_watchdog';

    $max_logs = get_option("wedoio_watchdog_max_logs", $max_logs);

    $count = $wpdb->get_var("SELECT COUNT(*) from $table_name");

    if ($count >= $max_logs) {
      // We fetch the records
      $subquery = $wpdb->prepare("SELECT id from $table_name
                                            ORDER BY id desc
                                            LIMIT $max_logs , $max_logs", $max_logs, $max_logs);
      $ids = $wpdb->get_results($subquery);
      $records = [];
      foreach ($ids as $id) {
        $records[] = $id->id;
      }

      if ($ids) {
        $query = $wpdb->prepare("DELETE FROM $table_name
                                        WHERE id IN
                                          (" . implode(",", $records) . ")", "");
        return $wpdb->query($query);
      }
    }

    return false;
  }

  /**
   * Create a database for the watchdog
   */
  public static function createDatabase() {
    global $wpdb;
    global $wedoio_db_version;

    $table_name = $wpdb->prefix . 'wedoio_watchdog';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
                id mediumint(12) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                tag varchar(55) DEFAULT '' NOT NULL,
                severity varchar(55) DEFAULT 'warning' NOT NULL,
                message text NOT NULL,
                uid mediumint(12),
                location text DEFAULT '' NOT NULL,
                PRIMARY KEY (id)
                ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option("wedoio_db_version", $wedoio_db_version);
  }

  /**
   * Log a message in the database
   * @param $key
   * @param $message
   * @param string $severity
   */
  public static function log($tag, $message, $severity = "info", $variable = null) {
    if (!get_option("wedoio_db_version")) {
      Wedoio_Watchdog::createDatabase();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_watchdog';

    $location = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $current_user = wp_get_current_user();

    if ($variable && function_exists("dtv")) {
      $message .= "<pre>" . print_r($variable, true) . '</pre>';
    }

    $record = array(
      'time' => current_time('mysql'),
      'tag' => $tag,
      'message' => $message,
      'severity' => $severity,
      'location' => $location,
      'uid' => $current_user->ID
    );

    $insert = $wpdb->insert(
      $table_name,
      $record
    );

    self::clearLogs();
  }

  /**
   * Render a single Log content
   */
  public static function renderLog($id = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_watchdog';

    $id = isset($_GET['log_id']) ? $_GET['log_id'] : $id;
    if ($id == "all") $id = false;

    $output = '';

    if ($id) {
      $query = $wpdb->prepare("SELECT * from $table_name WHERE id = %d", $id);
      $log = $wpdb->get_results($query);
      $log = reset($log);
      // Current url
      $current = $_SERVER['REQUEST_URI'];
      $output .= '<a href="' . $current . '&log_id=all">CLOSE</a>';
      $output .= '<table class="wedoio-watchdog-table" border="1" cellpadding="15" cellspacing="0" width="100%">';
      $output .= '<tr><td width="10%"><b>#id</b></td>';
      $output .= '<td width="20%">' . $log->id . '</td>
                        <td rowspan="6" valign="top">' . $log->message . '</td></tr>';
      $output .= '<td><b>Time</b></td>';
      $output .= '<td>' . $log->time . '</td></tr>';
      $output .= '<td><b>Tag</b></td>';
      $output .= '<td>' . $log->tag . '</td></tr>';
      $output .= '<td><b>Severity</b></td>';
      $output .= '<td>' . $log->severity . '</td></tr>';
      $output .= '<td><b>Uid</b></td>';
      $output .= '<td>' . $log->uid . '</td></tr>';
      $output .= '<td><b>Location</b></td>';
      $output .= '<td>' . $log->location . '</td></tr>';
      $output .= '</table>';
    }

    return $output;
  }

  /**
   * Render a table with the logs
   */
  public static function renderLogs() {
    $page = isset($_GET['current_page']) ? $_GET['current_page'] : 0;
    $results = Wedoio_Watchdog::getLogs($page);
    $records = $results['records'];
    $total = $results['total'];

    $output = '';
    $output .= self::renderLog();
    $output .= '<table class="wedoio-watchdog-table" border="1" cellpadding="15" cellspacing="0" width="100%">';
    $output .= '<thead>
                <tr>
                    <th cellpadding="15" align="center">Severity</th>
                    <th cellpadding="15" align="center">Tag</th>
                    <th cellpadding="15" align="center">Message</th>
                    <th cellpadding="15" align="center">Date</th>
                </tr>';

    $output .= '<tbody>';

    if (!$records) {
      $output .= '<tr>
                            <td colspan="4" align="center">No results</td>
                        </tr>';
    }

    foreach ($records as $record) {
      $message = $record->message;
      $message = strip_tags($message);
      $message = substr($message, 0, 50);

      $current = $_SERVER['REQUEST_URI'];
      $message = sprintf('<a href="%s&log_id=%d">%s</a>', $current, $record->id, $message);

      $output .= '<tr class="' . $record->severity . '">';
      $output .= '<td>' . $record->severity . '</td>';
      $output .= '<td>' . $record->tag . '</td>';
      $output .= '<td>' . $message . '</td>';
      $output .= '<td>' . $record->time . '</td>';
      $output .= '</tr>';
    }
    $output .= '</tbody>';
    $output .= '</table>';

    return $output;
  }

  /**
   * Return logs from the database
   * @param $page
   * @param array $filters
   */
  public static function getLogs($page, $limit = 50, $filters = []) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wedoio_watchdog';
    $offset = $page * $limit;

    $query = "SELECT * from $table_name";

    $where = [];

    if (isset($filters['tag'])) {
      $where[] = "tag like '%" . $filters['tag'] . "%'";
    }

    if ($where) {
      $where = " WHERE " . implode(" AND ", $where);
    } else {
      $where = "";
    }

    $query .= $where;

    $query .= " ORDER BY id DESC";

    $limit = " LIMIT $offset, $limit";
    $query .= $limit;

    $records = $wpdb->get_results($query);

    $count = $wpdb->get_var("SELECT COUNT(*) from $table_name" . $where);

    $result = [
      "records" => $records,
      "total" => $count
    ];

    return $result;
  }
}
