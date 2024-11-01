<?php
/**
 * The admin-specific functionality of the plugin.
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/admin
 */

/**
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 * @package    Wedoio
 * @subpackage Wedoio/admin
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

class Wedoio_Admin {

  /**
   * The ID of this plugin.
   * @since    1.0.0
   * @access   private
   * @var      string $plugin_name The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   * @since    1.0.0
   * @access   private
   * @var      string $version The current version of this plugin.
   */
  private $version;

  private $user;

  private $option_name = 'wedoio-settings-group';

  private $api;

  /**
   * Initialize the class and set its properties.
   * @param string $plugin_name The name of this plugin.
   * @param string $version The version of this plugin.
   * @since    1.0.0
   *
   */
  public function __construct($plugin_name, $version) {
    global $api;
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    $this->user = NULL;

    try {
      $this->api = new WedoioApi();
      $wedoio_token = get_option('wedoio_token');
      $uniconta_username = get_option('uniconta_username');
      $uniconta_password = get_option('uniconta_password');
      $uniconta_company = get_option('uniconta_company');
      $uniconta_token = $uniconta_username . ":" . $uniconta_password;
      $this->api->wedoioToken = $wedoio_token;
      $this->api->unicontaToken = $uniconta_token;
      $this->api->unicontaCompany = $uniconta_company;

      $api = $this->api;

      $krumo_link = dirname(__FILE__) . "/krumo/class.krumo.php";
      include_once($krumo_link);
      krumo::$skin = 'orange';

    } catch (Exception $e) {
      $this->error_log($e->getMessage());
    }
  }

  /**
   * Return a webhook key that needs to be appended to validate the requests
   */
  public static function webhookKey() {
    $data = esc_attr(get_option('wedoio_token')) . esc_attr(get_option('uniconta_username')) . esc_attr(get_option('uniconta_password'));
    $hash = md5($data);
    return $hash;
  }

  /**
   * Register the stylesheets for the admin area.
   * @since    1.0.0
   */
  public function enqueue_styles() {
    /**
     * This function is provided for demonstration purposes only.
     * An instance of this class should be passed to the run() function
     * defined in Wedoio_Loader as all of the hooks are defined
     * in that particular class.
     * The Wedoio_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */
    wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) .
      'css/wedoio-admin.css', array(), $this->version, 'all');
    wp_enqueue_style($this->plugin_name . "-icons", plugin_dir_url(__FILE__) .
      'icons/style.css', array(), $this->version, 'all');
    //<link href="https://file.myfontastic.com/mN5au8L9TSuaKqVBkwRFB5/icons.css" rel="stylesheet">
//        wp_enqueue_style($this->plugin_name . "-fontastic", "https://file.myfontastic.com/mN5au8L9TSuaKqVBkwRFB5/icons.css", array(), $this->version, 'all');
  }

  /**
   * Register the JavaScript for the admin area.
   * @since    1.0.0
   */
  public function enqueue_scripts() {
    /**
     * This function is provided for demonstration purposes only.
     * An instance of this class should be passed to the run() function
     * defined in Wedoio_Loader as all of the hooks are defined
     * in that particular class.
     * The Wedoio_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */
//        wp_enqueue_script($this->plugin_name."-require", plugin_dir_url(__FILE__) .'js/require.js', array('jquery'), $this->version, FALSE);
//        wp_enqueue_script($this->plugin_name . "-react", 'https://unpkg.com/react@16/umd/react.development.js', array(), $this->version, FALSE);
//        wp_enqueue_script($this->plugin_name . "-react-dom", 'https://unpkg.com/react-dom@16/umd/react-dom.development.js', array(), $this->version, FALSE);
    wp_enqueue_script($this->plugin_name . "-jquery-once", 'https://cdnjs.cloudflare.com/ajax/libs/jquery-once/2.2.1/jquery.once.min.js', array(), $this->version, FALSE);
    wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) .
      'js/wedoio-admin.js', array('jquery', $this->plugin_name . "-jquery-once"), $this->version, FALSE);
    wp_localize_script($this->plugin_name, 'ajax_object',
      array('ajax_url' => admin_url('admin-ajax.php')));
//        wp_enqueue_script($this->plugin_name."-mapper", plugin_dir_url(__FILE__) .
//            'js/main.mapper.js', array('jquery','suggest'), $this->version, TRUE);

//        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) .
//            'js/lib/wedoio.js', array('jquery','suggest',$this->plugin_name."-jquery-once"), $this->version, TRUE);

    wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/dist/main.js', array('jquery', 'suggest', $this->plugin_name . "-jquery-once"), $this->version, TRUE);

  }

  public function wedoio_add_admin_page() {
    add_menu_page(
      'Uniconta',
      'Uniconta',
      'manage_options',
      $this->plugin_name,
      array(
        $this,
        'wedoio_display_admin_page'
      ), // Calls function to require the partial
      plugins_url('wedoio-uniconta-woocommerce-connector/assets/icon.png'),
      70
    );

    add_submenu_page(
      "wedoio",
      "Uniconta",
      "Uniconta",
      "manage_options",
      $this->plugin_name,
      array(
        $this,
        'wedoio_display_admin_page'
      )
    );
  }

  /**
   * Render the options page for plugin
   * @since  1.0.0
   */
  public function wedoio_display_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (!session_id()) session_start();

    wp_enqueue_style($this->plugin_name . "-wedoio-mapper-js", plugin_dir_url(__FILE__) .
      'js/react/wedoio-mapper/build/static/css/main.3d81959b.css', array(), $this->version, 'all');

    wp_enqueue_script($this->plugin_name . "-wedoio-mapper-js", plugin_dir_url(__FILE__) . 'js/react/wedoio-mapper/build/static/js/main.f93798f6.js', array(), $this->version, TRUE);

    include_once plugin_dir_path(dirname(__FILE__)) .
      'admin/partials/wedoio-admin-display.php';
  }

  /**
   * Wedoio / Uniconta Actions/Filters
   * **/
  public function wedoio_registration_save($user_id) {

    if (isset($GLOBALS['syncUserFromUniconta'])) return;

    // Since the user have just been created it could not have a RowId yet so we just create that on uniconta
    $user = new WP_User($user_id);
    Wedoio_Debtor::syncUser($user);
  }

  /**
   * Build data to send to uniconta
   */
  public static function buildUserData($uid) {

    global $api;
    $user = get_user_by('id', $uid);
    $email = $user->user_email;
    $user_data = get_user_meta($uid);

    $nickname = $user_data['nickname'][0];
    $display_name = $user->data->user_login;
    $name = $nickname ? $nickname : $display_name;

    $first_name = $user_data['first_name'][0];
    $last_name = $user_data['last_name'][0];
    $description = $user_data['description'][0];

    $data = array(
      "_Text" => array($description),
      "_Name" => array($name),
      "_ContactEmail" => array($email)
    );

    // Gathering the field mapping option
    $mapping = esc_attr(get_option('uniconta_users_field_mapping'));
    $mapping = explode("\n", $mapping);
    $fieldsMapping = array();

    $user_keys = array(
      "first_name", "last_name", "nickname", "description", "user_email"
    );

    foreach ($mapping as $line) {
      $explode = explode("|", $line);

      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = $explode[0];
        $uniconta = trim($uniconta);
        $wp = $explode[1];
        $wp = trim($wp);
        $fieldsMapping[$uniconta] = $wp;

        $value = null;
        if (isset($user->data->$wp) && $user->data->$wp) {
          $value = $user->data->$wp;
        } else {
          $value = get_user_meta($uid, $wp, true);
        }

        if (strpos($uniconta, "Country") && $value) {
          $index = $api->getCountry($value);
          if ($index !== false) {
            $value = $index + 1;
          }

        }

        if (strpos($wp, "ACF_") === 0) {
          $field_name = str_replace("ACF_", "", $wp);
          $value = get_field($field_name, "user_$uid");
        }

        if ($value != "") $data[$uniconta][] = $value;
      }
    }

//        foreach($fieldsMapping as $uniconta => $wp_key){
//            $wp_key = trim($wp_key);
//            $uniconta_value = get_user_meta($uid,$wp_key,true);
//
//            if(isset($user->data->$wp_key)){
//                $data[$uniconta][] = $user->data->$wp_key;
//            }
//
//            if($uniconta_value){
//                $data[$uniconta][] = $uniconta_value;
//            }
//        }

    $curatedData = array();
    foreach ($data as $key => $values) {
      $value = reset($values);
      if ($value) {
        $curatedData[$key] = $value;
      }
    }

    $data = $curatedData;

    $data['_Name'] = $first_name . " " . $last_name;

//        unset($data['_Country']);
//        unset($data['_DeliveryCountry']);

    return $data;
  }

  /**
   * When a profile is updated
   */
  public function wedoio_user_update_profile($user_id) {

    if (isset($GLOBALS['syncUserFromUniconta'])) return;

    $user = new WP_User($user_id);
    Wedoio_Debtor::syncUser($user);
  }

  public function wedoio_plugin_settings() {
    //register our settings
    add_settings_section(
      $this->option_name . '_general',
      __('General', $this->plugin_name),
      array($this, 'options_general_callback'),
      $this->plugin_name
    );

    register_setting($this->plugin_name . "_general", 'wedoio_token');
    register_setting($this->plugin_name . "_general", 'uniconta_username');
    register_setting($this->plugin_name . "_general", 'uniconta_password');
    register_setting($this->plugin_name . "_general", 'uniconta_token');
    register_setting($this->plugin_name . "_general", 'uniconta_company');
    register_setting($this->plugin_name . "_general", 'uniconta_anonymous_account');
    register_setting($this->plugin_name . "_general", 'uniconta_accept_eula');
    register_setting($this->plugin_name . "_general", 'uniconta_prevent_double_order_sync');
    register_setting($this->plugin_name . "_general", 'uniconta_use_anonymous_debtor_for_orders');
    register_setting($this->plugin_name . "_general", 'uniconta_enable_user_sync');

    register_setting($this->plugin_name . "_mapping", 'uniconta_products_field_mapping');
    register_setting($this->plugin_name . "_mapping", 'uniconta_users_field_mapping');
    register_setting($this->plugin_name . "_mapping", 'uniconta_debtors_field_mapping');
    register_setting($this->plugin_name . "_mapping", 'uniconta_users_default_payment_term');
    register_setting($this->plugin_name . "_mapping", 'uniconta_invitem_group_primary');
    register_setting($this->plugin_name . "_mapping", 'uniconta_invitem_group_gallery');
    register_setting($this->plugin_name . "_mapping", 'uniconta_categories_mapping');
    register_setting($this->plugin_name . "_mapping", 'uniconta_categories_table');
    register_setting($this->plugin_name . "_mapping", 'uniconta_categories_csv', array($this, "handle_file_upload"));
    register_setting($this->plugin_name . "_mapping", 'uniconta_tags_mapping');
    register_setting($this->plugin_name . "_mapping", 'uniconta_tags_table');

    register_setting($this->plugin_name . "_plugin_ext", 'uniconta_manage_stock');
    register_setting($this->plugin_name . "_plugin_ext", 'use_plugin_ext_csp');
    register_setting($this->plugin_name . "_plugin_ext", 'use_plugin_ext_custom_roles');
    register_setting($this->plugin_name . "_plugin_ext", 'use_plugin_ext_multiple_addresses');
    register_setting($this->plugin_name . "_plugin_ext", 'use_plugin_woocommerce_multilingual_currencies');

    register_setting($this->plugin_name . "_crons", 'wedoio_cron_userdocs_active', array($this, "handle_cron_userdocs_status"));
    register_setting($this->plugin_name . "_crons", 'wedoio_cron_userdocs_execute', array($this, "handle_cron_userdocs_execute"));

    register_setting($this->plugin_name . "_crons", 'wedoio_cron_pricelist_active', array($this, "handle_cron_pricelist_status"));
    register_setting($this->plugin_name . "_crons", 'wedoio_cron_pricelist_execute', array($this, "handle_cron_pricelist_execute"));

    register_setting($this->plugin_name . "_crons", 'wedoio_cron_stock_active', array($this, "handle_cron_stock_status"));
    register_setting($this->plugin_name . "_crons", 'wedoio_cron_stock_execute', array($this, "handle_cron_stock_execute"));

    register_setting($this->plugin_name . "_crons", 'wedoio_cron_invoice_active', array($this, "handle_cron_invoice_status"));
    register_setting($this->plugin_name . "_crons", 'wedoio_cron_invoice_execute', array($this, "handle_cron_invoice_execute"));

    register_setting($this->plugin_name . "_master_sync", 'master_sync_for_images');

    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_debtors', array($this, "handle_hook_debtors_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invitems', array($this, "handle_hook_invitems_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_numberserie', array($this, "handle_hook_numberserie_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariant1', array($this, "handle_hook_invvariant1_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariant2', array($this, "handle_hook_invvariant2_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariant3', array($this, "handle_hook_invvariant3_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariant4', array($this, "handle_hook_invvariant4_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariant5', array($this, "handle_hook_invvariant5_status_change"));
    register_setting($this->plugin_name . "_hooks", 'uniconta_hook_invvariantdetail', array($this, "handle_hook_invvariantdetail_status_change"));


    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $available_gateways = array_keys($available_gateways);

    foreach ($available_gateways as $gateway) {
      register_setting($this->plugin_name . "_payment", 'uniconta_' . $gateway . '_payment');
    }

    if (in_array("epay_dk", $available_gateways) !== false) {
      for ($i = 1; $i <= 32; $i++) {
        register_setting($this->plugin_name . "_payment", 'uniconta_epay_dk_payment_' . $i);
      }
    }


    register_setting($this->plugin_name . "_payment", 'uniconta_invoice_numberserie');

    register_setting($this->plugin_name . "_payment", 'uniconta_quickpay_gl_account');
    register_setting($this->plugin_name . "_payment", 'uniconta_quickpay_gl_journal');
  }

  public function handle_cron_userdocs_status($option) {
    if ($option) {
      if (!wp_next_scheduled('wedoio_cron_userdocs')) {
        wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_userdocs');
      }
    } else {
      $timestamp = wp_next_scheduled('wedoio_cron_userdocs');
      wp_unschedule_event($timestamp, 'wedoio_cron_userdocs');
    }
    return $option;
  }

  public function handle_cron_userdocs_execute($option) {
    if ($option) {
      $timestamp = wp_next_scheduled('wedoio_cron_userdocs');
      wp_unschedule_event($timestamp, 'wedoio_cron_userdocs');
      wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_userdocs');
      $this->wedoio_cron_userdocs();

      return null;
    }
    return $option;
  }

  function handle_hook_debtors_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_DEBTOR, $option);
  }

  function handle_hook_invitems_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVITEM, $option);
  }

  function handle_hook_numberserie_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_NUMBERSERIE, $option);
  }

  function handle_hook_invvariant1_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANT1, $option);
  }

  function handle_hook_invvariant2_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANT2, $option);
  }

  function handle_hook_invvariant3_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANT3, $option);
  }

  function handle_hook_invvariant4_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANT4, $option);
  }

  function handle_hook_invvariant5_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANT5, $option);
  }

  function handle_hook_invvariantdetail_status_change($option) {
    return $this->handle_hook_status_change(TABLEID_INVVARIANTDETAIL, $option);
  }

  function handle_hook_status_change($tableId, $option) {

    $api = new WedoioApi();
    $hook_url = $original_url = site_url() . "/uniconta-webhook";
    $hook_url = str_replace("http://", "", $hook_url);
    $hook_url = str_replace("https://", "", $hook_url);

    $hook_search = $api->fetch("TableChangeEvent", ["_TableId" => $tableId]);
    $hook = false;

    foreach ($hook_search as $hook_found) {
      $hook = new WedoioTableChangeEvent();
      $hook->import($hook_found);
      if (strpos($hook->_Url, $hook_url) !== false) {
        break;
      } else {
        $hook = false;
      }
    }

    if ($option == 1 && !$hook) {
      // If it's set to active but there is no hook we have to create one
      $api->set("TableChangeEvent", [
        "_TableId" => $tableId,
        "_Url" => $original_url
      ]);
    }

    if ($option == 0 && $hook) {
      // If the hook_state is set to inactive but we find a hook, we have to delete it
      $params['method'] = "DELETE";
      $api->send("TableChangeEvent/" . $hook->RowId, $params);
    }

    return null;
  }

  /**
   * Cron for the userDocs
   */
  function wedoio_cron_userdocs() {
    return; // Cron disabled in new versions. This code will be removed in a later version
    $api = new WedoioApi();
    $last_fetch = get_option("wedoio_cron_userdocs_last_fetch", time());
//        $last_fetch = strtotime("15-05-2018");
    $now = time();

    $endpoint = "UserDocsClient.json?_Created=[LAST]..[NOW]";
    $endpoint = str_replace("[LAST]", date("Y-m-d", $last_fetch) . "T" . date("H:i:s", $last_fetch), $endpoint);
    $endpoint = str_replace("[NOW]", date("Y-m-d", $now) . "T" . date("H:i:s", $now), $endpoint);

    $res = $api->send($endpoint);
    $body = $res['body'];
    $update_objs = json_decode($body, true);

    $this->wedoio_cron_userdocs_cb($update_objs);

    update_option("wedoio_cron_userdocs_last_fetch", time());
  }

  public function handle_cron_pricelist_status($option) {
    if ($option) {
      if (!wp_next_scheduled('wedoio_cron_pricelist')) {
        wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_pricelist');
      }
    } else {
      $timestamp = wp_next_scheduled('wedoio_cron_pricelist');
      wp_unschedule_event($timestamp, 'wedoio_cron_pricelist');
    }
    return $option;
  }

  public function handle_cron_pricelist_execute($option) {
    if ($option) {
      $timestamp = wp_next_scheduled('wedoio_cron_pricelist');
      wp_unschedule_event($timestamp, 'wedoio_cron_pricelist');
      wp_schedule_event(time(), 'hourly', 'wedoio_cron_pricelist');
      $this->wedoio_cron_pricelist();

      return null;
    }
    return $option;
  }

  /**
   * Cron for the pricelist
   */
  function wedoio_cron_pricelist() {
    return; // Cron disabled in new versions. This code will be removed in a later version
    if (get_option("wedoio_cron_pricelist_active", 1)) {
      $api = new WedoioApi();
      $items = $api->fetch("DebtorPriceList/woo");

      foreach ($items as $item) {
        $name = $item->_Name;
        Wedoio_Debtor::syncPriceList($name);
      }

      update_option("wedoio_cron_pricelist_last_fetch", time());
    }
  }

  /**
   * Activate the crons if not done
   */
  public static function activate_crons() {

    // For new setups we don't activate the crons at all

//    if (!wp_next_scheduled('wedoio_cron_userdocs') && get_option("wedoio_cron_userdocs_active", 1)) {
//      wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_userdocs');
//    }
//
//    if (!wp_next_scheduled('wedoio_cron_pricelist') && get_option("wedoio_cron_pricelist_active", 1)) {
//      wp_schedule_event(time(), 'fifteen_minutes', 'wedoio_cron_pricelist');
//    }
//
//    if (!wp_next_scheduled('wedoio_cron_invoice') && get_option("wedoio_cron_invoice", 1)) {
//      wp_schedule_event(time(), 'five_minutes', 'wedoio_cron_invoice');
//    }
//
//    if (!wp_next_scheduled('wedoio_cron_batch')) {
//      wp_schedule_event(time(), 'every_minute', 'wedoio_cron_batch');
//    }
//
//    if (!wp_next_scheduled('wedoio_cron_batch_clean')) {
//      wp_schedule_event(time(), 'hourly', 'wedoio_cron_batch_clean');
//    }
//
//    // In case the stock cron is still activated. We disable it
//    $timestamp = wp_next_scheduled('wedoio_cron_stock');
//    if ($timestamp) wp_unschedule_event($timestamp, 'wedoio_cron_stock');

  }

  /**
   * Cron for the stock
   */
  function wedoio_cron_stock() {

    return;

    if (get_option("wedoio_cron_stock_active", 1)) {
      $api = new WedoioApi();
      $invitems = $api->fetch("InvItem/woo");
      foreach ($invitems as $invitem) {
        $pid = wc_get_product_id_by_sku($invitem->_Item);
        if ($pid) {
          $stock = get_post_meta($pid, "_stock", true);
          if ($stock != $invitem->_Qty) {
            update_post_meta($pid, '_stock', $invitem->_Qty);
          }
        }
      }

      update_option("wedoio_cron_stock_last_fetch", time());
    }
  }

  public function handle_cron_invoice_status($option) {
    if ($option) {
      if (!wp_next_scheduled('wedoio_cron_invoice')) {
        wp_schedule_event(time(), 'five_minutes', 'wedoio_cron_invoice');
      }
    } else {
      $timestamp = wp_next_scheduled('wedoio_cron_invoice');
      wp_unschedule_event($timestamp, 'wedoio_cron_invoice');
    }
    return $option;
  }

  public function handle_cron_invoice_execute($option) {
    if ($option) {
      $timestamp = wp_next_scheduled('wedoio_cron_invoice');
      wp_unschedule_event($timestamp, 'wedoio_cron_invoice');
      wp_schedule_event(time(), 'five_minutes', 'wedoio_cron_invoice');
      $this->wedoio_cron_invoice();

      return null;
    }
    return $option;
  }

  /**
   * Cron for the invoices
   */
  function wedoio_cron_invoice() {
    return;
    if (get_option("wedoio_cron_invoice_active", 1)) {
      Wedoio_DebtorOrder::processInvoices();
      update_option("wedoio_cron_invoice_last_fetch", time());
    }
  }

  /**
   * Cron for the batches
   */
  function wedoio_cron_batch() {
    WedoioBatchApi::processCron();
  }

  /**
   * Cron for cleaning the batches
   */
  function wedoio_cron_batch_clean() {
    WedoioBatchApi::garbageCollector();
  }

  /**
   * Proceed with the update of images on the invitem
   * @param $items
   */
  function wedoio_cron_userdocs_cb($items) {
    return;
    if (get_option("wedoio_cron_userdocs_active", 1)) {
      $products = array();
      foreach ($items as $item) {
        $rowId = $item['TableRowId'];
        $products[$rowId] = $item;
      }

      foreach ($products as $rowId => $item) {
        // We sync Images for each product found
        Wedoio_InvItem::syncInvItemImagesFromRowId($rowId);
      }
    }
  }

  /**
   * Find Product by its uniconta rowId
   */
  public static function findProductByRowId($rowId) {
    $args = array(
      "post_type" => "product",
      "meta_key" => "_uniconta-rowid",
      "meta_value" => $rowId
    );

    $products = new WP_Query($args);

    $product = reset($products->posts);
    return $product;
  }

  /**
   * Set an Image for a product
   */
  public static function setImage($data, $post_id) {
    $upload_dir = wp_upload_dir();
    $image_data = base64_decode($data['data']);
    $filename = $data['filename'] . ".png";

    if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
    else                                    $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

//        print_r($file);

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => sanitize_file_name($filename),
      'post_content' => '',
      'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    $res1 = wp_update_attachment_metadata($attach_id, $attach_data);

//        if($group == "featured"){
//            $res2= set_post_thumbnail( $post_id, $attach_id );
//        }

    return $attach_id;
  }

  public function handle_file_upload($option) {

    if (!empty($_FILES["uniconta_categories_csv"]["tmp_name"])) {
      if (preg_match('/\.csv$/i', $_FILES['uniconta_categories_csv']['name'])) {
        $csv = array_map(function ($v) {
          return str_getcsv($v, ";");
        }, file($_FILES["uniconta_categories_csv"]["tmp_name"]));
        array_shift($csv);

        $categories = [];
        foreach ($csv as $line) {
          if ($line[0] == "" && $line[1] == "") continue;
          $categories[] = [
            "value" => utf8_encode($line[0]),
            "title" => utf8_encode($line[1])
          ];
        }


        update_option("uniconta_categories_csv_list", json_encode($categories));
      }
    }

    return $option;
  }

  public function wedoio_bulk_actions($actions) {
    $actions['add_to_uniconta'] = 'Add to uniconta';

    return $actions;
  }

  public function wedoio_handle_bulk_actions($redirect_to, $doaction, $user_id) {
  }

  public function wedoio_bulk_action_admin_notice() {
  }

  /**
   * Check if we are connected to wedoio or not
   */
  public function wedoio_status_check() {
    $color = "orange";
    return '<div class="status-outlet" style="width:20px;height:20px;display:inline-block;background:' . $color . ';border-radius:50%"></div> ';
  }

  /**
   * Check the status of the hooks on Uniconta
   */
  public function wedoio_hooks_status_check() {
    $api = new WedoioApi();
    $hook_url = site_url() . "/uniconta-webhook";
    $hook_url = str_replace("http://", "", $hook_url);
    $hook_url = str_replace("https://", "", $hook_url);

    $tableevents = $api->fetch("TableChangeEvent");

    $hooks = [
      "Debtor" => 0,
      "InvItem" => 0,
      "InvItemStorage" => 0,
      "InvVariant1" => 0,
      "InvVariant2" => 0,
      "InvVariant3" => 0,
      "InvVariant4" => 0,
      "InvVariant5" => 0,
      "InvVariantDetail" => 0,
      "NumberSerie" => 0
    ];

    foreach ($tableevents as $tableevent) {
      $hook = new WedoioTableChangeEvent();
      $hook->import($tableevent);
      $hook_type = $hook->hook_type;
      if (strpos($hook->_Url, $hook_url) !== false) {
        $hooks[$hook_type] = 1;
      }
    }

    return $hooks;
  }

  /**
   * Placeholder functions
   */
  public function wedoio_plugin_settings_page() {
  }

  public function options_general_callback() {
  }

  public function mapping_general_callback() {
  }

  public function plugin_ext_general_callback() {
  }

  public function wedoio_order_create($order_id) {
    if (class_exists("Wedoio_Background_Order")) {
      $request = new Wedoio_Background_Order();
      $request->data(['order_id' => $order_id])->dispatch();
    } else {
      Wedoio_DebtorOrder::syncDebtorOrder($order_id);
    }

    /*global $wpdb, $woocommerce, $wedoioUpdate;

    if ($wedoioUpdate === true) return;
    $wedoioUpdate = true;

    $post = get_post($order_id);

    // If not an order, bye !!
    if ($post->post_type != 'shop_order') {
        return;
    }

    // Now we create the order and sync it
    $order = wc_get_order($order_id);
    $orderRowId = get_post_meta($order_id, "_uniconta-rowid", true);

    $customer_id = $order->get_customer_id();

    //We get the row id for the user
    $rowId = Wedoio_Public::getUserRowId($customer_id);
//        print_r("rowID " . $rowId);

    if ($rowId) {
        // We get the Debtor on Uniconta
        $debtor = $this->api->fetchDebtor($rowId);
        $account = $debtor->_Account;
    } else {
        $account = get_option('uniconta_anonymous_account');
    }

    if (!$account) return;

    $country = $order->get_shipping_country();
    $country = $this->api->getCountry($country);
    $country += 1;

    $debtorOrder_data = [
        "_DCAccount" => $account,
        "_DeliveryName" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
        "_DeliveryAddress1" => $order->get_shipping_address_1(),
        "_DeliveryAddress2" => $order->get_shipping_address_2(),
        "_DeliveryCity" => $order->get_shipping_city(),
        "_DeliveryCountry" => $country,
        "_DeliveryZipCode" => $order->get_shipping_postcode(),
        "_OurRef" => $order_id,
        "_DeleteOrder" => 0
//            "OrderTotal" => $order->get_total()
    ];

    $debtorOrder = false;

//        if($orderRowId){
//            $debtorOrder = $this->api->fetchDebtorOrder($orderRowId);
//            $debtorOrder_data['RowId'] = $orderRowId;
//            $this->api->setDebtorOrder($debtorOrder_data);
//        }else{
//            $debtorOrder = $this->api->setDebtorOrder($debtorOrder_data);
//        }

    if ($orderRowId) {
        $this->api->send("DebtorOrder/$orderRowId", [
            "method" => "DELETE"
        ]);
    }

    $debtorOrder = $this->api->setDebtorOrder($debtorOrder_data);

    if (!$debtorOrder) return;

    // We set the RowId on the order
    update_post_meta($order_id, "_uniconta-rowid", $debtorOrder->RowId);
    $orderRowId = $debtorOrder->RowId;

    // We get the line items
    $items = $order->get_items();
    $products = array();

    if (!$debtorOrder) return;
//        $orderNumber = $debtorOrder->_OrderNumber;

//        if(!$orderNumber) return;

    // We get all the UNICONTA debtorOrderLine
//        $debtorOrderLines = $this->api->fetch("DebtorOrderLine",array("_OrderNumber" => $orderNumber));
//        foreach($debtorOrderLines as $debtorOrderLine){
//            // We clean the order
//            $debtorOrderLineRowId = $debtorOrderLine->RowId;
//            if($debtorOrderLineRowId){
//                $this->api->deleteDebtorOrderLine($debtorOrderLineRowId);
//            }
//        }

    // We delete all the DebtorOrderLines on a Debtor by invoicing it and killing the invoice soon after

//        $inv = $this->api->send("DebtorOrder/$orderRowId/Invoice",[
//            "method" => "PUT"
//        ]);
//
//        $invRowId = isset($inv->RowId) ? $inv->RowId : false;
//
//        if($invRowId){
//            $this->api->send("DebtorInvoice/$invRowId",[
//                "method" => "DELETE"
//            ]);
//        }
//
    print '<pre>';

    foreach ($items as $line_item_id => $item) {
        $pid = $item->get_product_id();
        $product = wc_get_product($pid);
        $pRowId = get_post_meta($pid, "_uniconta-rowid", true);

        $_item = false;
        if ($pRowId) {
            // We get the invItem
            $invItem = $this->api->fetch("InvItem", $pRowId);
            $_item = $invItem->_Item;
        }

        if (!$_item) continue;

        $liRowId = $item->get_meta("_uniconta-rowid", false);
        $debtorOrderLine = [
            'debtorOrder' => $debtorOrder->RowId,
            '_Qty' => $item->get_quantity(),
            '_Item' => $_item,
            '_Text' => $product->get_name(),
//                '_Price' => $item->get_total()
        ];

//            print_r($debtorOrderLine);
//            print_r($liRowId);

        $debtorOrderLine = $this->api->setDebtorOrderLine($debtorOrderLine);
//            print_r($debtorOrderLine);
        if (!$liRowId) {
            // We create a debtorOrderLine
//                $item->update_meta_data("_uniconta-rowid",$debtorOrderLine->RowId);
        }
    }
*/
//        print '</pre>';
//        exit;
  }

  /**
   * Return a webhook key that needs to be appended to validate the requests
   */
  public function getCompanyName() {
    $name = "";

    $uniconta_company = get_option('uniconta_company');
    if ($uniconta_company) {
      $company = $this->api->sendDirect("Company/$uniconta_company.json");
      $company = json_decode($company['body']);
//            $name =print_r($company,true);
      $name = isset($company->_Name) ? $company->_Name : __("Company Not found");
      $name = "(" . $name . ")";
    }

    return $name;
  }

  /**
   * Return a webhook key that needs to be appended to validate the requests
   */
  public function getCompanies() {

    $companies = $this->api->sendDirect("Company.json");
    $companies = json_decode($companies['body']);

    $options = array();
    $options[""] = "N/A";

    if (is_array($companies)) {
      foreach ($companies as $company) {
        $options[$company->RowId] = $company->_Name;
      }
    }

    return $options;
  }

  /**
   * @return array
   */
  public function getNumberseries() {
    $items = $this->api->fetch("NumberSerie");
//        $items = json_decode($items['body']);

    $options = array();
    $options[""] = "N/A";

    if (is_array($items)) {
      foreach ($items as $item) {
        $options[$item->RowId] = $item->_Name;
      }
    }

    return $options;
  }

  /**
   * Return the GL Accounts available in the system
   */
  public function getGLAccounts() {
    $api = new WedoioApi();
    $accounts = $api->fetch("GLAccount?_AccountType=10..17");

    $options = array();
    $options[""] = "N/A";
    foreach ($accounts as $account) {
      $options[$account->RowId] = $account->_Name;
    }

    return $options;
  }

  /**
   * Return the GL Accounts available in the system
   */
  public function getGLJournals() {
    $api = new WedoioApi();
    $journals = $api->fetch("GLDailyJournal");

    $options = array();
    $options[""] = "N/A";
    foreach ($journals as $journal) {
      $options[$journal->RowId] = $journal->_Name;
    }

    return $options;
  }

  /**
   * Return the GL Accounts available in the system
   */
  public function getPaymentTerms() {
    $api = new WedoioApi();
    $payments = $api->fetch("PaymentTerm");

    $options = array();
    $options[""] = "N/A";
    foreach ($payments as $payment) {
      $options[$payment->_Payment] = $payment->_Name;
    }

    return $options;
  }


  /**
   * @return array
   */
  public function getUnicontaTablesClient() {
    $tables = $companies = $this->api->send("TableHeaderClient");
    $tables = $tables['body'];
    $tables = json_decode($tables, true);

    $options = array();
    $options[""] = "N/A";
    foreach ($tables as $table) {
      $options[$table['RowId']] = $table['_Name'];
    }

    return $options;
  }

  /**
   * return the woocommerce product fields list for an autocomplete
   */
  public function wp_ajax_wedoio_product_autocomplete() {

    $s = wp_unslash($_GET['q']);

    $list = [
      "post_content",
      "post_title",
      "post_excerpt",
      "post_status",
      "_sku",
      "_price",
      "_regular_price",
      "_sale_price",
      "_stock",
      "_stock_status",
    ];

    $found = array();

    foreach ($list as $name) {
      if (preg_match('/' . $s . '/', $name) === 1) {
        $found[] = $name;
      }
    }

    if (count($found) == 0) {
      $found[] = 'Nothing Found';
    }

    echo implode("\n", $found);
    wp_die();
  }

  function wp_ajax_wedoio_fetch_fields() {
    $entity = isset($_GET['entity']) ? $_GET['entity'] : "products";

    switch ($entity) {
      case "api":
        $query = isset($_GET['query']) ? $_GET['query'] : "";
        $this->wp_get_api_query($query);
        break;
      case "products":
        $fields = array(
          "post_content",
          "post_title",
          "post_excerpt",
          "post_status",
          "_sku",
          "_price",
          "_regular_price",
          "_sale_price",
          "_stock",
          "_stock_status",
        );
        break;
      case "categories":
        $fields = array();
        $categories = $this->wp_get_product_categories();
        $sub_categories = $this->wp_get_product_categories(90);

        foreach ($categories as $id => $category) {
          $fields[] = $id . " | " . $category;
        }

        foreach ($sub_categories as $id => $category) {
          $fields[] = $id . " | " . $category;
        }


//                $fields = array_values($categories);
        break;
      case "InvItem":
        $invItems = $this->api->fetch("InvItem", array());
        $invItem = reset($invItems);
        WedoioApi::extractFields("UserFields", "UserField", $invItem);

        $fields[] = json_encode($invItem);

        $userFields = (Array)$invItem->UserFieldsExtract;
        $invItem = (Array)$invItem;
        $Itemfields = array_keys($invItem);
        $fields = array();

        foreach ($Itemfields as $index => $key) {
          if (strpos($key, "_") === 0) {
            $fields[] = $key;
          }
        }

        foreach ($userFields as $key => $field) {
          $fields[] = $key;
        }

        break;
      default:
        $fields = array();
    }

    if ($fields) print json_encode($fields);
    wp_die();
  }

  /**
   * Make a query on the system
   * @param $query
   */
  function wp_get_api_query($query) {
    if ($query) {
      $krumo_link = dirname(__FILE__) . "/krumo/class.krumo.php";
      include_once($krumo_link);
      $result = $this->api->sendDirect($query, array());
      $body = $result['body'];
      $result = json_decode($body);
      krumo($result);
    }
  }

  function wp_get_product_categories($parent = 0) {
    $taxonomy = 'product_cat';
    $orderby = 'name';
    $show_count = 0;      // 1 for yes, 0 for no
    $pad_counts = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no
    $title = '';
    $empty = 0;

    $args = array(
      'taxonomy' => $taxonomy,
      'child_of' => 0,
      'orderby' => $orderby,
      'hierarchical' => $hierarchical,
      'parent' => $parent,
      'title_li' => $title,
      'hide_empty' => $empty
    );
    $categories = array();
    $all_categories = get_categories($args);
    foreach ($all_categories as $cat) {
      $category_id = $cat->term_id;

      $prefix = "";
      if ($cat->category_parent != 0) {
        $prefix = "--";
      }

      $categories[$category_id] = $prefix . $cat->name;

      $sub_cats = $this->wp_get_product_categories($category_id);
      $categories += $sub_cats;
    }

    return $categories;
  }

  public function fetch_images_groups() {
    $res = $this->api->send("/AttachmentGroup.json");
    $body = $res['body'];
    $groups = json_decode($body, true);
    $options = array();
    foreach ($groups as $group) {
      $number = $group['Number'];
      $options[$number] = $group['Name'];
    }
    return $options;
  }

  /**
   * Process a wedoio batch api operation
   */
  public function wp_ajax_wedoio_batchapi_process() {
    session_start();
    $batch_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
    $action = isset($_REQUEST['batch_action']) ? $_REQUEST['batch_action'] : false;
    $cron = isset($_REQUEST['batch_cron']) ? (bool)$_REQUEST['batch_cron'] : false;

    $batch = new WedoioBatchApi($batch_id, $cron);

    switch ($action) {
      case "invitem":
        if ($batch->status() == "new") {
          $batch->addOperation("wedoio_batch_invitem_init");
        }
        break;
      case "debtor":
        if ($batch->status() == "new") {
          $batch->addOperation("wedoio_batch_debtor_init");
        }
        break;
      case "test":
        if ($batch->status() == "new") {
          $batch->addOperation("wedoio_batch_test_init");
        }
        break;
    }

    $batch->process();
    $state = $batch->getState();
    print json_encode($state);
    wp_die();
  }

  /**
   * Process a batch operation
   */
  public function wp_ajax_wedoio_batch_process() {
    session_start();
    // Add a nonce to identify the process
    $batch_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
    $action = isset($_REQUEST['batch_action']) ? $_REQUEST['batch_action'] : false;
    $items_by_batch = 5;

    $db_batches = get_option("wedoio_batches", []);

    $batch_default = [
      "action" => $action,
      "items" => [],
      "current" => 0,
      "status" => "start",
      "results" => [],
      "msg" => "",
      "percent" => 0,
      "start" => time()
    ];

    $batch = $batch_default;

    if (!$batch_id) {
      $batch['msg'] = "No ID";
      print json_encode($batch);
      wp_die();
    }

    // We clean the old batches ?
    foreach ($_SESSION['wedoio_batch'] as $id => $b) {
      if (time() - $b['start'] > 60 * 60) {
        unset($_SESSION['wedoio_batch'][$id]);// No batch should last more than one hour lol
      }
    }

    switch ($action) {
      case "invitem":
        $action = "wedoio_batch_process_invitem_batch_sync";
        $batch_session = isset($_SESSION['wedoio_batch'][$batch_id]) ? $_SESSION['wedoio_batch'][$batch_id] : [];

        $batch = array_merge($batch_default, $batch_session);
//                $batch['status'] = isset($batch['status']) ? $batch['status'] : "init";

        $results = $batch['results'];
        $percent = 5;

        switch ($batch['status']) {
          case "start":
            $batch['msg'] = "initializing batch process";
            $batch['status'] = "init";
            $_SESSION['wedoio_batch'][$batch_id] = $batch;
            break;
          case "init":
            // We prepare the batch there by fetching the items
            $batch['items'] = $this->wedoio_batch_process_invitem_init();
            $batch['status'] = "next";
            $_SESSION['wedoio_batch'][$batch_id] = $batch;
            break;
          case "next":
            $current = $batch['current'];
            $items = $batch['items'];
            $items_to_process = array_slice($items, $current * $items_by_batch, $items_by_batch);


            $count = count($results);
            $total = count($items);

            if (count($results)) {
              $percent_done = intval(100 * $count / $total);
              $percent = 20 + (80 * $percent_done / 100);
            } else {
              $percent = 20;
            }


            $msg = "processed $count out of $total";
            $batch['msg'] = $msg;

            if ($items_to_process) {
              try {
                $results += $this->$action($items_to_process);
                $batch['status'] = "next";
              } catch (Exception $e) {
                $batch['status'] = "error";
                $batch['msg'] = $e->getMessage();
              }
              $batch['results'] = $results;
              $batch['current'] += 1;
            } else {
              $batch['status'] = "finished";
              $count = count($batch['results']);
              $time = time() - $batch['start'];
              $batch['items'] = [];
              $batch['msg'] = "syncronized $count items in $time secs";
              $percent = 100;
            }
            $_SESSION['wedoio_batch'][$batch_id] = $batch;
            break;
          case "finished":
            $percent = 100;
            $batch['items'] = [];
            break;
        }

        if ($batch['status'] !== "finished") {
          $_SESSION['wedoio_batch'][$batch_id] = $batch;
        } else {
          unset($_SESSION['wedoio_batch'][$batch_id]);
        }

        $batch['percent'] = $percent;

        break;
      default:
        $batch['status'] = "no-action";
        $batch['msg'] = "no action found";
    }

    print json_encode($batch);
    wp_die();
  }

  /**
   * Adding cron intervals
   * @param $schedules
   * @return mixed
   */
  function wedoio_add_cron_interval($schedules) {
    $schedules['every_minute'] = array(
      'interval' => 60,
      'display' => esc_html__('Every minute'),
    );

    $schedules['five_minutes'] = array(
      'interval' => 60 * 5,
      'display' => esc_html__('Every 5 mns'),
    );

    $schedules['fifteen_minutes'] = array(
      'interval' => 60 * 15,
      'display' => esc_html__('Every 15 mns'),
    );

    $schedules['half_hour'] = array(
      'interval' => 60 * 30,
      'display' => esc_html__('Every half hour'),
    );

    return $schedules;
  }

  /**
   *  Check the service status
   */
  function check_service_status() {
    global $wpdb; // this is how you get access to the database

    $result = $this->api->statusCheck();
    $color = $result ? "green" : "red";
    print $color;

    wp_die(); // this is required to terminate immediately and return a proper response
  }

  /**
   *
   */
  function load_organisations_options() {

    $uniconta_company = esc_attr(get_option('uniconta_company'));
    $options = $this->getCompanies();

    ?>
    <select
      name="uniconta_company"
      style="width: 420px"
      class="organisations-select">
      <?php foreach ($options as $id => $name) : ?>
        <option
          value=<?php print $id ?> <?php print $id == $uniconta_company ? "selected" : "" ?>><?php print $name . " ($id) " ?></option>
      <?php endforeach; ?>
    </select>

    <?php

    wp_die(); // this is required to terminate immediately and return a proper response
  }

  /**
   * @param $atts
   * @return string
   */
  public static function wedoio_partial_invoices_shortcode($atts) {
    $order_id = false;
    extract(shortcode_atts([
      "order_id" => false
    ], $atts));

    $output = "";

    if ($order_id) {
      $invoices = Wedoio_DebtorOrder::getOrderDebtorInvoices($order_id);
      if ($invoices) {
        $output .= '<ul>';
        $index = 1;
        foreach ($invoices as $invoice) {
          $pdf = $invoice['invoice_pdf'];
          $date = $invoice['invoice']['_Date'];
          $date_parts = explode("T", $date);
          $date = reset($date_parts);
          $output .= '<li>';
          $output .= '<a href="' . $pdf . '" target="_blank">' . sprintf(__('Invoice %d | %s'), $invoice['invoice']['_InvoiceNumber'], $date) . '</a>';
          $output .= '</li>';
          $index++;
        }
        $output .= '</ul>';
      }
    }

    return $output;
  }

}
