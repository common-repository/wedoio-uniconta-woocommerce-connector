<?php
/**
 * The file that defines the core plugin class
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 * @link       Bechir
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/includes
 */

/**
 * The core plugin class.
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/includes
 * @author
 */
class Wedoio {

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   * @since    1.0.0
   * @access   protected
   * @var      Wedoio_Loader $loader Maintains and registers all hooks for the plugin.
   */
  protected $loader;

  /**
   * The unique identifier of this plugin.
   * @since    1.0.0
   * @access   protected
   * @var      string $plugin_name The string used to uniquely identify this plugin.
   */
  protected $plugin_name;

  /**
   * The current version of the plugin.
   * @since    1.0.0
   * @access   protected
   * @var      string $version The current version of the plugin.
   */
  protected $version;

  /**
   * Define the core functionality of the plugin.
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the admin area and
   * the public-facing side of the site.
   * @since    1.0.0
   */
  public function __construct() {
    if (defined('PLUGIN_NAME_VERSION')) {
      $this->version = PLUGIN_NAME_VERSION;
    } else {
      $this->version = '1.0.0';
    }
    $this->plugin_name = 'wedoio';
    $this->load_dependencies();
    $this->set_locale();
    $this->define_admin_hooks();
    $this->define_public_hooks();
  }

  /**
   * Load the required dependencies for this plugin.
   * Include the following files that make up the plugin:
   * - Wedoio_Loader. Orchestrates the hooks of the plugin.
   * - Wedoio_i18n. Defines internationalization functionality.
   * - Wedoio_Admin. Defines all hooks for the admin area.
   * - Wedoio_Public. Defines all hooks for the public side of the site.
   * Create an instance of the loader which will be used to register the hooks
   * with WordPress.
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies() {
    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-loader.php';
    /**
     * The class responsible for defining internationalization functionality
     * of the plugin.
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-i18n.php';
    /**
     * The class responsible for defining all actions that occur in the admin area.
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'admin/class-wedoio-admin.php';
    /**
     * The class responsible for defining all actions that occur in the public-facing
     * side of the site.
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'public/class-wedoio-public.php';

    /**
     * The wedoio Helper functions
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-helpers.php';

    /**
     * Krumo
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'admin/krumo/class.krumo.php';

    /**
     * Guzzle
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'vendor/autoload.php';

    /**
     * The wedoio api helper
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-api.php';

    /**
     * The Uniconta entities classes
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioEntity.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioInvitem.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioInvItemStorage.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioDebtorOrder.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioDebtorOrderLine.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioDebtorInvoice.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioInvVariant.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioInvVariantDetail.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioInvVariantCombi.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioTableChangeEvent.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioDebtorPriceList.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Uniconta/WedoioDebtorPriceListLine.php';

    /**
     * The woocommerce entities classes
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/WC/WedoioProduct.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/WC/WedoioProductVariation.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/WC/WedoioProductVariationAttribute.php';

    /**
     * CSP helper
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/csp/WedoioWdmWuspSimpleProductsUsp.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/csp/WedoioWdmWuspSimpleProductsGsp.php';

    /**
     * The wedoio api operations functions
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-operations.php';

    /**
     * The wedoio InvItem operations functions
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-invitem-functions.php';

    /**
     * The wedoio debtor operations functions
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-debtor-functions.php';

    /**
     * The wedoio debtor order operations functions
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-debtor-order-functions.php';


    /**
     * The class responsible for the batch operations
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-batch-api.php';

    /**
     * The class responsible for the watchdog
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-watchdog.php';

    /**
     * The class responsible for the links
     */
    require_once plugin_dir_path(dirname(__FILE__)) .
      'includes/class-wedoio-links.php';

    $this->loader = new Wedoio_Loader();
  }

  /**
   * Define the locale for this plugin for internationalization.
   * Uses the Wedoio_i18n class in order to set the domain and to register the hook
   * with WordPress.
   * @since    1.0.0
   * @access   private
   */
  private function set_locale() {
    $plugin_i18n = new Wedoio_i18n();
    $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
  }

  /**
   * Register all of the hooks related to the admin area functionality
   * of the plugin.
   * @since    1.0.0
   * @access   private
   */
  private function define_admin_hooks() {
    $plugin_admin =
      new Wedoio_Admin($this->get_plugin_name(), $this->get_version());
    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    $this->loader->add_action('admin_menu', $plugin_admin, 'wedoio_add_admin_page');
    $this->loader->add_action('user_register', $plugin_admin, 'wedoio_registration_save');
    $this->loader->add_action('profile_update', $plugin_admin, 'wedoio_user_update_profile');
    $this->loader->add_action('admin_init', $plugin_admin, 'wedoio_plugin_settings');
    $this->loader->add_action('woocommerce_update_order', $plugin_admin, 'wedoio_order_create');

    // Cron
    $this->loader->add_filter("cron_schedules", $plugin_admin, "wedoio_add_cron_interval");
    $this->loader->add_action('wedoio_cron_userdocs', $plugin_admin, 'wedoio_cron_userdocs');
    $this->loader->add_action('wedoio_cron_pricelist', $plugin_admin, 'wedoio_cron_pricelist');
    $this->loader->add_action('wedoio_cron_invoice', $plugin_admin, 'wedoio_cron_invoice');
    $this->loader->add_action('wedoio_cron_batch', $plugin_admin, 'wedoio_cron_batch');
    $this->loader->add_action('wedoio_cron_batch_clean', $plugin_admin, 'wedoio_cron_batch_clean');

    $this->loader->add_action('admin_post_wedoio_sync_products', $plugin_admin, 'syncProducts');

    $this->loader->add_action('wp_ajax_wedoio_product_autocomplete', $plugin_admin, 'wp_ajax_wedoio_product_autocomplete');
    $this->loader->add_action('wp_ajax_wedoio_fetch_fields', $plugin_admin, 'wp_ajax_wedoio_fetch_fields');
    $this->loader->add_action('wp_ajax_wedoio_batch_process', $plugin_admin, 'wp_ajax_wedoio_batch_process');
    $this->loader->add_action('wp_ajax_wedoio_batchapi_process', $plugin_admin, 'wp_ajax_wedoio_batchapi_process');

    $this->loader->add_action('wp_ajax_check_service_status', $plugin_admin, 'check_service_status');
    $this->loader->add_action('wp_ajax_load_organisations_options', $plugin_admin, 'load_organisations_options');

    add_shortcode("wedoio-partial-invoices", ["Wedoio_Admin", "wedoio_partial_invoices_shortcode"]);
  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   * @since    1.0.0
   * @access   private
   */
  private function define_public_hooks() {
    $plugin_public =
      new Wedoio_Public($this->get_plugin_name(), $this->get_version());
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    $this->loader->add_action('wp_loaded', $plugin_public, "webhook_handler");
  }

  /**
   * Run the loader to execute all of the hooks with WordPress.
   * @since    1.0.0
   */
  public function run() {
    $this->loader->run();
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   * @return    string    The name of the plugin.
   * @since     1.0.0
   */
  public function get_plugin_name() {
    return $this->plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   * @return    Wedoio_Loader    Orchestrates the hooks of the plugin.
   * @since     1.0.0
   */
  public function get_loader() {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   * @return    string    The version number of the plugin.
   * @since     1.0.0
   */
  public function get_version() {
    return $this->version;
  }

}
