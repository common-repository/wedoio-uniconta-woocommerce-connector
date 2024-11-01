<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       Bechir
 * @since      1.0.0
 *
 * @package    Wedoio
 * @subpackage Wedoio/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wedoio
 * @subpackage Wedoio/public
 * @author
 */
class Wedoio_Public {

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $plugin_name The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $version The current version of this plugin.
   */
  private $version;

  private $api;

  /**
   * Initialize the class and set its properties.
   *
   * @param string $plugin_name The name of the plugin.
   * @param string $version The version of this plugin.
   * @since    1.0.0
   */
  public function __construct($plugin_name, $version) {
    global $api;
    $this->plugin_name = $plugin_name;
    $this->version = $version;

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
    } catch (Exception $e) {
      $this->error_log($e->getMessage());
    }

  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Wedoio_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Wedoio_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wedoio-public.css', array(), $this->version, 'all');

  }

  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Wedoio_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Wedoio_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wedoio-public.js', array('jquery'), $this->version, false);

  }

  /**
   * Handle the webhooks
   */
  public function webhook_handler() {

    $eula_accepted = esc_attr(get_option('uniconta_accept_eula'));
    if (!$eula_accepted) return __("You must accept the EULA before proceeding.");

    $request = $_SERVER['REQUEST_URI'];

    $log = array(
      "GET" => $_GET,
      "REQUEST" => $_REQUEST,
      "request" => $request
    );

    if (isset($_GET['wedoio-fetch-fields'])) {
      header('Access-Control-Allow-Origin: *');

      $type = isset($_GET['entity']) ? $_GET['entity'] : "products";
      $transform = false;
      $fields = array();

      switch ($type) {
        case "products":
          $fields = $this->fetch_product_fields();
          $transform = true;
          break;
        case "user":
          $fields = $this->fetch_user_fields();
          $transform = true;
          break;
        case "categories":
          $cats = $this->wp_get_product_categories();
          $fields = array();
          foreach ($cats as $id => $cat) {
            $fields[] = [
              "value" => $id,
              "title" => $cat
            ];
          }
          break;
        case "tags":
          $tags = $this->wp_get_product_tags();
          $fields = array();
          foreach ($tags as $id => $tag) {
            $fields[] = [
              "value" => $id,
              "title" => $tag
            ];
          }
          break;
        case "uniconta-categories":
          $fields = $this->fetch_uniconta_categories();
          break;
        case "uniconta-tags":
          $fields = $this->fetch_uniconta_tags();
          break;
        case "invitem":
          $fields = $this->fetch_invitem_fields();
          $transform = true;
          break;
        case "debtor":
          $fields = $this->fetch_debtor_fields();
          $transform = true;
          break;

      }

      if ($transform) {
        $fields_array = array();
        foreach ($fields as $key => $field) {
          $fields_array[] = [
            "title" => $field,
            "value" => $field
          ];
        }
        $fields = $fields_array;
      }

      print json_encode($fields);
      exit();
    }

    if (isset($_GET['wedoio-debug'])) {
      print '</pre>';
      exit();
    }

    if (preg_match("/uniconta-webhook/", $request) || isset($_GET['uniconta-webhook'])) {
      print '<pre>';

      if (isset($_GET['watch'])) {
        $logs = file_get_contents(__DIR__ . "/logs/" . date("d.m.Y") . ".log");
        print $logs;
      } else {
        $rowId = isset($_GET['RowId']) ? $_GET['RowId'] : false;
        $table = isset($_GET['Table']) ? $_GET['Table'] : false;
        $action = isset($_GET['Action']) ? $_GET['Action'] : false;
        $action = strtolower($action);

        $this->log("syncing $table $rowId");

        set_time_limit(0);

        Wedoio_Watchdog::log("Webhook", "webhook call : $action $table $rowId");

        if ($table == "Debtor") {
          switch ($action) {
            case "update":
            case "add":
              Wedoio_Debtor::syncDebtorFromRowId($rowId);
              break;
            case "delete":
              break;
          }
        }

        if ($table == "InvItem") {
          switch ($action) {
            case "update":
            case "add":
              Wedoio_InvItem::syncInvItemFromRowId($rowId);
              break;
            case "delete":
              break;
          }
        }

        if ($table == "InvItemStorage") {
          switch ($action) {
            case "update":
            case "add":
              $InvItem = new WedoioInvItem($rowId);
              $_Item = $InvItem->_Item;
              if ($_Item) {
                $api = new WedoioApi();
                $storages = $api->fetch("InvItemStorage", ['_Item' => $_Item]);
                foreach ($storages as $storage) {
                  $s = new WedoioInvItemStorage();
                  $s->import($storage);
                  $s->process();
                }
              }
              break;
            case "delete":
              break;
          }
        }

        if ($table == "InvVariant1") {
          switch ($action) {
            case "update":
            case "add":
              $variant = new WedoioInvVariant(1, $rowId);
              $variant->sync();
              break;
            case "delete":
              $attribute = new WedoioProductVariationAttribute("InvVariant1");
              $attribute->load_by_rowId($rowId);
              $attribute->delete();
              break;
          }
        }

        if ($table == "InvVariant2") {
          switch ($action) {
            case "update":
            case "add":
              $variant = new WedoioInvVariant(2, $rowId);
              $variant->sync();
              break;
            case "delete":
              $attribute = new WedoioProductVariationAttribute("InvVariant2");
              $attribute->load_by_rowId($rowId);
              $attribute->delete();
              break;

          }
        }

        if ($table == "InvVariant3") {
          switch ($action) {
            case "update":
            case "add":
              $variant = new WedoioInvVariant(3, $rowId);
              $variant->sync();
              break;
            case "delete":
              $attribute = new WedoioProductVariationAttribute("InvVariant3");
              $attribute->load_by_rowId($rowId);
              $attribute->delete();
              break;
          }
        }

        if ($table == "InvVariant4") {
          switch ($action) {
            case "update":
            case "add":
              $variant = new WedoioInvVariant(4, $rowId);
              $variant->sync();
              break;
            case "delete":
              $attribute = new WedoioProductVariationAttribute("InvVariant4");
              $attribute->load_by_rowId($rowId);
              $attribute->delete();
              break;
          }
        }

        if ($table == "InvVariant5") {
          switch ($action) {
            case "update":
            case "add":
              $variant = new WedoioInvVariant(5, $rowId);
              $variant->sync();
              break;
            case "delete":
              $attribute = new WedoioProductVariationAttribute("InvVariant5");
              $attribute->load_by_rowId($rowId);
              $attribute->delete();
              break;
          }
        }

        if ($table == "InvVariantDetail") {
          switch ($action) {
            case "update":
            case "add":
//                            $variant = new WedoioInvVariantDetail($rowId);
//                            $variant->sync();
              break;
            case "delete":
//                            $product = new WedoioProductVariation();
//                            $product->load_by_rowId($rowId);
//                            $product->delete();
              break;
          }
        }


        if ($table == "WorkInstallation") {
          switch ($action) {
            case "update":
            case "add":
              Wedoio_Debtor::syncWorkInstallationFromRowId($rowId);
              break;
            case "delete":
              break;
          }
        }

        $action = strtolower($action);

        if ($table == "DebtorInvoiceLocal") {
          switch ($action) {
            case "update":
            case "add":
              $invoiceId = $_GET['InvoiceId'];
              Wedoio_DebtorOrder::processInvoice($invoiceId);
              break;
            case "delete":
              break;
          }
        }

        if (get_option('use_plugin_ext_csp', false)) {

          if ($table == "DebtorPriceListClient") {
            switch ($action) {
              case "update":
              case "add":
                $debtorPriceList = new WedoioDebtorPriceList(null, $rowId);
                $debtorPriceList->apply(true);
                break;
              case "delete":
                break;
            }
          }

        }

        do_action("wedoio_webhook_handler");

        $this->log($log);
      }
      print '</pre>';
      exit;
    }
  }

  /**
   * Fetch invItemFields
   */
  public function fetch_product_fields() {
    $default = json_decode('{"0":"_sku","3":"_price","4":"_regular_price","5":"_sale_price","6":"_stock","7":"_stock_status","8":"_manage_stock","9":"post_title","10":"post_excerpt","11":"post_content","12":"product_cat","13":"_thumbnail_id","15":"_wc_review_count","16":"_wc_rating_count","17":"_wc_average_rating","18":"_product_image_gallery","19":"product_tag"}', true);
    $wc_product_data = array(
      'name' => '',
      'slug' => '',
      'date_created' => null,
      'date_modified' => null,
      'status' => false,
      'featured' => false,
      'catalog_visibility' => 'visible',
      'description' => '',
      'short_description' => '',
      'sku' => '',
      'price' => '',
      'regular_price' => '',
      'sale_price' => '',
      'date_on_sale_from' => null,
      'date_on_sale_to' => null,
      'total_sales' => '0',
      'tax_status' => 'taxable',
      'tax_class' => '',
      'manage_stock' => false,
      'stock_quantity' => null,
      'stock_status' => 'instock',
      'backorders' => 'no',
      'low_stock_amount' => '',
      'sold_individually' => false,
      'weight' => '',
      'length' => '',
      'width' => '',
      'height' => '',
      'upsell_ids' => array(),
      'cross_sell_ids' => array(),
      'parent_id' => 0,
      'reviews_allowed' => true,
      'purchase_note' => '',
      'attributes' => array(),
      'default_attributes' => array(),
      'menu_order' => 0,
      'post_password' => '',
      'virtual' => false,
      'downloadable' => false,
      'category_ids' => array(),
      'tag_ids' => array(),
      'shipping_class_id' => 0,
      'downloads' => array(),
      'image_id' => '',
      'gallery_image_ids' => array(),
      'download_limit' => -1,
      'download_expiry' => -1,
      'rating_counts' => array(),
      'average_rating' => 0,
      'review_count' => 0,
    );
    $wc_product_data = array_keys($wc_product_data);
    $fields = $default + $wc_product_data;
    return $fields;
  }

  /**
   * Fetch invItemFields
   */
  public function fetch_user_fields() {

    $uid = get_current_user_id();
    $meta = get_user_meta($uid);
    $fields = array_keys($meta);

    return $fields;
  }

  /**
   * Fetch invItemFields
   */
  public function fetch_invitem_fields() {
    $res = $this->api->sendDirect("EntityEnum/DataModel.json");
    $body = $res['body'];
    $modelClients = json_decode($body, true);
    $modelFields = array();
    $fields = array();
    foreach ($modelClients as $model) {
      $entity = $model['entity'];
      if ($entity == "InvItem") {
        $modelFields = $model['Fields'];
        break;
      }
    }

    foreach ($modelFields as $field) {
      $fields[] = $field['Name'];
    }

    $res = $this->api->send("TableFieldsClient.json");
    $body = $res['body'];
    $extraFields = json_decode($body, true);

    foreach ($extraFields as $field) {
      if ($field['TableId'] == 23) {
        $fields[] = $field['Name'];
      }
    }

    return $fields;
  }

  /**
   * Fetch invItemFields
   */
  public function fetch_debtor_fields() {
    $res = $this->api->sendDirect("EntityEnum/DataModel.json");
    $body = $res['body'];
    $modelClients = json_decode($body, true);
    $modelFields = array();
    $fields = array();
    foreach ($modelClients as $model) {
      $entity = $model['entity'];
      if ($entity == "Debtor") {
        $modelFields = $model['Fields'];
        break;
      }
    }

    foreach ($modelFields as $field) {
      $fields[] = $field['Name'];
    }

    $res = $this->api->send("TableFieldsClient.json");
    $body = $res['body'];
    $extraFields = json_decode($body, true);

    foreach ($extraFields as $field) {
      if ($field['TableId'] == 50) {
        $fields[] = $field['Name'];
      }
    }

    return $fields;
  }

  /**
   *
   */
  public function fetch_categories_fields() {
    $categories = wp_get_product_categories();
    return $categories;
  }

  /**
   * @return mixed
   */
  public function fetch_categories_tags() {
    $categories = wp_get_product_categories();
    return $categories;
  }

  public function fetch_uniconta_categories() {
    $categories_table = get_option('uniconta_categories_table');
    $categories = [];
    if ($categories_table) {
      $items = $companies = $this->api->send("UserTableData/$categories_table");
      $items = html_entity_decode($items['body']);
      $items = json_decode($items, true);
      $categories = [];

      foreach ($items as $item) {
        $categories[] = [
          "value" => $item['Key'],
          "title" => $item['Name']
        ];
      }
    }
//        $categories = json_decode(get_option("uniconta_categories_csv_list"),true);
    return $categories;
  }

  public function fetch_uniconta_tags() {
    $tags_table = get_option('uniconta_tags_table');
    $tags = [];
    if ($tags_table) {
      $items = $companies = $this->api->send("UserTableData/$tags_table");
      $items = utf8_decode($items['body']);
      $items = json_decode($items, true);
      $tags = [];

      foreach ($items as $item) {
        $tags[] = [
          "value" => $item['Key'],
          "title" => $item['Name']
        ];
      }
    }
//        $tags = json_decode(get_option("uniconta_tags_csv_list"),true);
    return $tags;
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

  /**
   * @param int $parent
   * @return array
   */
  function wp_get_product_tags($parent = 0) {
    $taxonomy = 'product_tag';
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
    $tags = array();
    $all_tags = get_terms($args);
    foreach ($all_tags as $tag) {
      $category_id = $tag->term_id;

      $prefix = "";
      if ($tag->category_parent != 0) {
        $prefix = "--";
      }

      $tags[$category_id] = $prefix . $tag->name;

      $sub_cats = $this->wp_get_product_tags($category_id);
      $tags += $sub_cats;
    }

    return $tags;
  }

  /**
   * Get an user with the rowID
   */
  public static function getUserRowId($id) {
    $rowId = get_user_meta($id, "uniconta-rowid", true);
    return $rowId;
  }

  /**
   * log files writer
   */
  public static function log($msg) {
//        $time = @date('[d/M/Y:H:i:s]');
//        $log = $time . " : " . print_r($msg,true). PHP_EOL;
//        file_put_contents(__DIR__."/logs/".date("d.m.Y").".log", $log , FILE_APPEND);
  }

  /**
   * log files writer
   */
  public static function error_log($msg) {
    $time = @date('[d/M/Y:H:i:s]');
    $log = $time . " : " . print_r($msg, true) . PHP_EOL;
    error_log($log);
//        file_put_contents(__DIR__."/logs/error.".date("d.m.Y").".log", $log , FILE_APPEND);
  }

}
