<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

define("GROUP_PRICELIST", "group-pricelist");

class WedoioDebtorPriceList extends WedoioEntity {
  protected $lines;
  protected $products;
  protected $invitems;
  protected $group;

  public $odata;

  public function __construct($name = null, $params = [], $odata = false) {

    if ($name) {
      $params['_Name'] = $name;
    }
    parent::__construct("DebtorPriceList", $params);
    $this->bootstrapCSP();
    $this->lines = false;
    $this->group = false;
    $this->odata = $odata;
    $this->prepare();
  }

  public function import($entity) {
    parent::import($entity);
    $this->lines = false;
    $this->group = false;
    $this->prepare();
  }

  public function prepare() {
    $this->fetch_lines();
    $this->get_group_id();
  }

  public function get_group_id() {
    $group = false;
    if ($this->group) {
      $group = $this->group;
    }
    if (class_exists("Groups_Group")) {
      // Gotta check first if a link have been created
      if ($this->RowId && !$group) {
        $link = Wedoio_Links::getLinks(GROUP_PRICELIST, ['uniconta_id' => $this->RowId]);
        if ($link) {
          $link = reset($link);
          $group_id = $link->wp_id;

          // We try to fetch the group by id
          if ($group_id) {
            $group = Groups_Group::read($group_id);

            if ($group) {
              // We check the name for the group
              if ($group->name != $this->_Name) {
                // We update the group
                Groups_Group::update([
                  "group_id" => $group_id,
                  "name" => $this->_Name
                ]);
                $group = Groups_Group::read($group_id);
              }

              $this->group = $group;
            } else {
              // We remove the link if the group have been deleted
              Wedoio_Links::unlink(GROUP_PRICELIST, ['uniconta_id' => $this->RowId]);
            }
          }
        }
      }

      if ($this->_Name && !$group) {
        $group = Groups_Group::read_by_name($this->_Name);

        if (!$group) {
          $group_id = Groups_Group::create([
            "name" => $this->_Name
          ]);
          $group = Groups_Group::read($group_id);
        } else {
          $group_id = $group->group_id;
        }

        $link = Wedoio_Links::getLinks(GROUP_PRICELIST, ["wp_id" => $group_id, 'uniconta_id' => $this->RowId]);

        if (!$link) {
          $insert = Wedoio_Links::link(GROUP_PRICELIST, $this->RowId, $group_id);
        }

        $this->group = $group;
      }
    }
    return $group;
  }

  public function fetch_lines($force = false) {

    $lines = false;

    if ($this->lines === false || $force) {
      $lines = [];

      if ($this->lines !== false) $lines = $this->lines;

      if (!$lines && $this->_Name) {
        $api = new WedoioApi();

        if ($this->odata) {
          $pricelist_lines = $api::odata_call("InvPriceListLineClient");

          $items = [];
          foreach ($pricelist_lines as $pricelist_line) {
            if ($pricelist_line['_PriceList'] == $this->_Name) {
              $items[] = $pricelist_line;
            }
          }
        } else {
          $items = $api->fetch("InvPriceListLineClient", [
            "_PriceList" => $this->_Name
          ]);
        }

        foreach ($items as $item) {
          $line = new WedoioDebtorPriceListLine();
          $line->import($item);

          if ($line->product) {
            $lines[$line->RowId] = $line;
            $pid = $line->product->pid;
            $this->products[$pid][] = $line;
            $this->invitems[$line->_Item] = $pid;
          } elseif ($line->products) {
            // Means we are not targeting a specific product. We need to fetch the list of items affected
            $lines[$line->RowId] = $line;
            $products = $line->products;
            foreach ($products as $product) {
              $pid = $product->pid;
              $this->products[$pid][] = $line;
              $this->invitems[$line->_Item] = $pid;
            }
          }
        }

//                $linkedPriceList = $this->_LinkToPricelist;
//
//                if($linkedPriceList){
//                    $items = $api->fetch("InvPriceListLineClient",[
//                        "_PriceList" => $linkedPriceList
//                    ]);
//
//                    foreach($items as $item){
//                        $line = new WedoioDebtorPriceListLine();
//                        $line->import($item);
//
//                        if($line->product){
//                            $lines[$line->RowId] = $line;
//                            $pid = $line->product->pid;
//
//                            if(!isset($this->products[$pid])){
//                                $this->products[$pid][] = $line;
//                            }
//
//                            if(!isset($this->invitems[$line->_Item])){
//                                $this->invitems[$line->_Item] = $pid;
//                            }
//                        }
//                    }
//                }

        $this->lines = $lines;
      }
    }

    return $lines;
  }

  public function apply_price_by_pid($pid) {
    $lines = isset($this->products[$pid]) ? $this->products[$pid] : [];

    $group = $this->group;
    if ($group) {
      $group_id = $group->group_id;
      $this->reset_prices($pid);

      $post = [];

      foreach ($lines as $line) {
        $line_post = $line->apply();

        if ($line_post) {
          $line_post = apply_filters("wedoio_after_creating_pricelist_post", $line_post, $line, $this);

          $post['wdm_woo_groupname'][] = $group_id;
          $post['wdm_group_price_type'][] = $line_post['wdm_group_price_type'];
          $post['wdm_woo_group_qty'][] = $line_post['wdm_woo_group_qty'];
          $post['wdm_woo_group_price'][] = $line_post['wdm_woo_group_price'];
        }
      }

      if ($pid == 924) {
//        d($post);
      }


      $csp = new WedoioWdmWuspSimpleProductsGsp($post);
      $csp->processGroupPricingPairs($pid);
    }
  }

  public function apply_price_by_item($_Item) {
    $pid = isset($this->invitems[$_Item]) ? $this->invitems[$_Item] : false;
    $this->apply_price_by_pid($pid);
  }

  public function apply_price_on_products($products = []) {

    if (!$products) {
      $products = $this->products ?? [];
      $products = array_keys($products);
    }

    $transient_name = "wedoio_pricelists_{$this->RowId}";
    $previous_products = get_transient($transient_name);
    if (!$previous_products) $previous_products = [];

    foreach ($products as $product) {
      if ($product) {
        $this->apply_price_by_pid($product);

        $index = array_search($product, $previous_products);
        if ($index !== false) unset($previous_products[$index]);
      }
    }

    // Normally if there is a previous product not processed it means the line have been removed
    foreach ($previous_products as $product) {
      $this->reset_prices($product);
    }

    set_transient($transient_name, $products, 0);
  }

  public function apply_price_on_invitems($invitems = []) {
    if (!$invitems) {
      $invitems = array_keys($this->invitems);
    }

    foreach ($invitems as $invitem) {
      $this->apply_price_by_item($invitem);
    }
  }

  public function apply($sync_inherited = false, $deep = 0) {


    $transient_key = "apply_price_list_{$this->RowId}";

    $processing = get_transient($transient_key);
    if ($processing) {
      Wedoio_Watchdog::log("DebtorPriceList", "Already processing the pricelist {$this->_Name}");
    } else {
      set_transient($transient_key, true, 120);
    }

    Wedoio_Watchdog::log("DebtorPriceList", "Synchronizing Pricelist " . $this->_Name);

    $this->apply_price_on_products();

    if ($sync_inherited) {
      $api = new WedoioApi();
      if ($deep >= 2) {
        return;
      }
      // We get all the $pricelists that inherit this one
      $items = $api->fetch("DebtorPriceList");

      foreach ($items as $item) {
        if ($item->_LinkToPricelist == $this->_Name) {
          $pricelist = new WedoioDebtorPriceList($item->_Name, [], $this->odata);
//                    $pricelist->import_lines($this);
          $pricelist->apply(true, $deep + 1);
        }
      }
    }

    do_action("wedoio_after_syncing_pricelist", $this);

    Wedoio_Watchdog::log("DebtorPriceList", "Synchronizing Pricelist " . $this->_Name . " completed.");

    set_transient($transient_key, false, 120);
  }

  public function import_lines($pricelist) {
    if (!$this->products) $this->products = [];
    if (!$this->invitems) $this->invitems = [];

    $products = array_replace($this->products, $pricelist->products);
    $invitems = array_replace($this->invitems, $pricelist->invitems);

    $this->products = $products;
    $this->invitems = $invitems;
  }

  public function reset_prices($pid) {
    if ($this->group) {
      $groupId = $this->group->group_id;
      WedoioWdmWuspSimpleProductsGsp::deleteAllGroupMapping($pid, $groupId);
      do_action("wedoio_after_removing_pricelist_prices", $pid, $this);
    }
  }

  public function bootstrap() {
    parent::bootstrap();
  }

  public static function bootstrapCSP() {
    if (get_option('use_plugin_ext_csp', false)) {
      //including file for the working of Customer Specific Pricing on Simple Products
      include_once CSP_PLUGIN_URL . '/includes/user-specific-pricing/class-wdm-wusp-simple-products-usp.php';
      new WuspSimpleProduct\WdmWuspSimpleProductsUsp();

      //including file for the working of Customer Specific Pricing on Variable Products
      include_once CSP_PLUGIN_URL . '/includes/user-specific-pricing/class-wdm-wusp-variable-products-usp.php';
      new WuspVariableProduct\WdmWuspVariableProductsUsp();

      //including file for the working of Group Based Pricing on Simple Products
      include_once CSP_PLUGIN_URL . '/includes/group-specific-pricing/class-wdm-wusp-simple-products-gsp.php';
      new WuspSimpleProduct\WgspSimpleProduct\WdmWuspSimpleProductsGsp();

      //including file for the working of Group Based Pricing on Variable Products
      include_once CSP_PLUGIN_URL . '/includes/group-specific-pricing/class-wdm-wusp-variable-products-gsp.php';
      new WuspVariableProduct\WgspVariableProduct\WdmWuspVariableProductsGsp();

      //including file for the working of Role Based Pricing on Simple Products
      include_once CSP_PLUGIN_URL . '/includes/role-specific-pricing/class-wdm-wusp-simple-products-rsp.php';
      new WuspSimpleProduct\WrspSimpleProduct\WdmWuspSimpleProductsRsp();

      //including file for the working of Role Based Pricing on Variable Products
      include_once CSP_PLUGIN_URL . '/includes/role-specific-pricing/class-wdm-wusp-variable-products-rsp.php';
      new WuspVariableProduct\WrspVariableProduct\WdmWuspVariableProductsRsp();

      //including file for the working of Customer Specific Price on Products
      include_once CSP_PLUGIN_URL . '/includes/class-wdm-usp-product-price-commons.php';
      include_once CSP_PLUGIN_URL . '/includes/class-wdm-apply-usp-product-price.php';
      new WuspSimpleProduct\WuspCSPProductPrice();


      //csp for order creation from backend
      include_once CSP_PLUGIN_URL . '/includes/dashboard-orders/class-wdm-customer-specific-pricing-new-order.php';
      new cspNewOrder\WdmCustomerSpecificPricingNewOrder();

      include_once CSP_PLUGIN_URL . '/includes/class-wdm-single-view-tabs.php';
      new SingleView\WdmShowTabs();

      include_once CSP_PLUGIN_URL . '/includes/class-wdm-wusp-ajax.php';
      new cspAjax\WdmWuspAjax();

      include_once CSP_PLUGIN_URL . '/includes/class-wdm-product-tables.php';
      new WdmCSP\WdmCspProductTables();
    }
  }
}
