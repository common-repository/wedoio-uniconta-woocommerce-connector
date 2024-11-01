<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioInvVariantCombi extends WedoioEntity {
  protected $invariant_src;
  protected $parent;

  public function __construct($params = false) {
    parent::__construct("InvVariantCombiClient", $params);
  }

  /**
   *
   */
  function get_variant_detail() {
    $api = new WedoioApi();

    $variant_detail = false;

    $sku = $this->_Item;

    global $InvVariantDetail;
    $variants = $InvVariantDetail[$sku] ?? false;

    if ($variants === false) {
      $query = ["_Item" => $this->_Item];
      $variants = $api->fetch("InvVariantDetail", $query, true);
      $InvVariantDetail[$sku] = $variants;
    }

    $detail = false;
    foreach ($variants as $variant) {
      if (
        $this->_Variant1 == $variant['_Variant1'] &&
        $this->_Variant2 == $variant['_Variant2'] &&
        $this->_Variant3 == $variant['_Variant3'] &&
        $this->_Variant4 == $variant['_Variant4'] &&
        $this->_Variant5 == $variant['_Variant5']
      ) {
        $detail = $variant;
      }
    }

    if ($detail) {
      $variant_detail = new WedoioInvVariantDetail();
      $variant_detail->import($detail);
    }

    return $variant_detail;
  }

  /**
   * @return bool|WedoioInvVariantDetail
   */
  function get_price_list($pricelist) {

    $api = new WedoioApi();

    $sku = $this->_Item;

    global $InvPricelistLine;

    $lines = $InvPricelistLine[$sku] ?? false;

    if ($lines === false) {
      $query = ["_Item" => $this->_Item];
      $query['_PriceList'] = $pricelist;
      $lines = $api->fetch("InvPriceListLine", $query, true);
      $InvPricelistLine[$sku] = $lines;
    }

    $priceline_found = false;
    foreach ($lines as $line) {
      if (
        $this->_Variant1 == $line['_Variant1'] &&
        $this->_Variant2 == $line['_Variant2'] &&
        $this->_Variant3 == $line['_Variant3'] &&
        $this->_Variant4 == $line['_Variant4'] &&
        $this->_Variant5 == $line['_Variant5']
      ) {
        $priceline_found = $line;
      }
    }

    if ($priceline_found) {
      $priceline = new WedoioEntity("InvPriceListLine");
      $priceline->import($priceline_found);
    } else {
      $priceline = false;
    }

    return $priceline;
  }

  /**
   * @return WedoioInvitem
   */
  function get_original_invItem() {
    $api = new WedoioApi();
    if ($this->parent) return $this->parent;
    $item = $this->_Item;
    $invitems_found = $api->fetch("InvItem", ['_Item' => $item], true);
    $invitem_found = reset($invitems_found);
    $parent = new WedoioInvitem();
    $parent->import($invitem_found);
    $this->parent = $parent;
    return $parent;
  }

  /**
   * Get the related Product from the system
   * @return WedoioProduct
   */
  function get_related_product() {

    if ($this->product) return $this->product;

    $product = new WedoioProductVariation();

    if ($this->RowId) {
      $product->load_by_rowid($this->RowId);
    }

    if (!$product->pid) {
      // We try to find a match with the data we have
      $parent_product = $this->get_related_parent_product();
      $existing_variations = $parent_product->get_children();

      $variations_attributes = $this->get_variant_attributes();
      $key = md5(implode("-", $variations_attributes));

      foreach ($existing_variations as $existing_variation) {
        $variation = new WedoioProductVariation($existing_variation);
        $variation_array = $variation->get_uniconta_variant_attributes();
        $variation_key = md5(implode("-", $variation_array));
        if ($key == $variation_key) {
          $product = $variation;
          break;
        }
      }

      if ($product->pid) {
        update_post_meta($product->pid, "_uniconta-rowid", $this->RowId);
      }
    }

    $this->product = $product;
    return $product;
  }

  /**
   * Get the related parent product
   * @return WedoioProductVariation
   */
  function get_related_parent_product() {
    if ($this->parent_product) return $this->parent_product;
    if (!$this->parent) $this->get_original_invItem();
    $parent = $this->parent;
    $parent_product = $parent->get_related_product();
    return $parent_product;
  }

  /**
   * Get the variations attributes
   */
  function get_variant_attributes() {

    $variations_attributes = [
      '_Variant1' => $this->_Variant1,
      '_Variant2' => $this->_Variant2,
      '_Variant3' => $this->_Variant3,
      '_Variant4' => $this->_Variant4,
      '_Variant5' => $this->_Variant5,
    ];

    // We need to convert the Variants keys into the correct values
    foreach ($variations_attributes as $variant => $value) {
      $invariantId = str_replace("_Variant", "", $variant);
      $invariant = new WedoioInvVariant($invariantId);
      $invariant->load_by_variant($value);
      $variations_attributes[$variant] = $invariant->_Name;
    }

    $this->clean_attributes_array($variations_attributes);
    return $variations_attributes;
  }

  /**
   * Convert the variant attributes into an array keyed by the product taxonomies
   */
  function get_product_attributes() {
    $variation_attributes = $this->get_variant_attributes();
    $product_attributes = [];

    foreach ($variation_attributes as $variant => $variation_attribute) {
      $invariant_number = str_replace("_Variant", "", $variant);
      $invariant = new WedoioInvVariant($invariant_number);
      $taxonomy = $invariant->get_variant_taxonomy();
      $product_attributes[$taxonomy] = $variation_attribute;
    }

    return $product_attributes;
  }

  /**
   * Synchronize the variation to woocommerce.
   * If we find a match then we don't actually need to set the attributes here. We will focus on the Pricelists and
   * the stock instead.
   * @return bool|int
   */
  public function sync() {

    $start = time();

    $product = $this->get_related_product();
    if (!$this->parent) $this->get_original_invItem();
    $parent = $this->parent;
    $parent_product = $this->get_related_parent_product();

    if (!$parent_product->pid) {
      // No Parent product created yet. Weird ...
      return false;
    }

    $s = time();

    $variant_rowid = $this->RowId;
    $parent_row_id = $parent->RowId;
    $parent_Item = $parent->_Item;
    Wedoio_Watchdog::log("Product Sync", "Synchronization of Variation $variant_rowid for $parent_Item in progress");

    // We get the variation attributes
    $variations_attributes = $this->get_variant_attributes();

    $current_parent_attributes = $parent_product->product->get_attributes();
    $product_attributes = $this->get_product_attributes();
    $new_product_attributes_options = [];

    // We clean the product_attributes
    foreach ($product_attributes as $taxonomy => $product_attribute) {
      if (!$product_attribute) unset($product_attributes[$taxonomy]);
    }

    // We create a new variation only if we didn't find any related product
//        if (!$product->pid) {

//            $delete = true;
//            foreach ($variations_attributes as $attribute) {
//                if ($attribute) {
//                    $delete = false;
//                    break;
//                }
//            }
//
//            if ($delete) {
//                // If there is not attributes, we just delete the product ... or skip it since normally we haven't found it yet
//                return false;
//            }

//        d($product_attributes);

    if (!$product_attributes) return false;

    // We load the respetive terms associated to the product_attribute
    foreach ($product_attributes as $taxonomy => $product_attribute) {
      $invvariant = WedoioInvVariant::load_by_taxonomy($taxonomy);
      if (!$invvariant) continue;
      if (!$product_attribute) continue;
      $invvariant->load_by_variant_name($product_attribute);
      $invvariant->sync();
      $term = $invvariant->get_related_term();
      $new_product_attributes_options[$taxonomy] = $term->term_id;
      // We change the product_attribute value to its slug as well
      $product_attributes[$taxonomy] = $term->term->slug;

      if (!isset($current_parent_attributes[$taxonomy])) {
        // We create the new parent product terms that were missing
        $product_attribute = new WC_Product_Attribute();
        $product_attribute->set_id(wc_attribute_taxonomy_id_by_name($term->taxonomy));
        $product_attribute->set_name($term->taxonomy);
        $product_attribute->set_visible(false);
        $product_attribute->set_options([$term->term_id]);
        $product_attribute->set_variation(true);
        $current_parent_attributes[$taxonomy] = $product_attribute;
      }
    }

    // We update the parent product terms if necessary
    foreach ($current_parent_attributes as $attribute_taxonomy => $current_parent_attribute) {
//                if(!isset($product_attributes[$attribute_taxonomy])) unset($current_parent_attributes[$attribute_taxonomy]);
      $taxonomy = $current_parent_attribute->get_taxonomy();
      $new_option = $new_product_attributes_options[$taxonomy];
      $new_options = [$new_option];
      $options = $current_parent_attribute->get_options();
      $options = array_merge($options, $new_options);
      $options = array_unique($options);
      rsort($options);
      $current_parent_attribute->set_options($options);
      wp_set_object_terms($parent_product->pid, $options, $taxonomy);
    }

    $parent_product->product->set_attributes($current_parent_attributes);
    $parent_product->product->save();

    $p = $product->product;

    $p->set_parent_id($parent_product->pid);
    $p->set_status("publish");
    $p->set_attributes($product_attributes);
    $p->save();

    WC_Product_Variable::sync($parent_product->pid);

    $this->product = $p;

    if (!is_wp_error($p)) {
      $pid = $p->get_id();
      update_post_meta($pid, "_uniconta-rowid", $this->RowId);
      update_post_meta($pid, "_uniconta_data", json_encode($parent->data));
//                return $pid;
    } else {
//            d($p);
    }
//        }
//
//        d("synced variation ".$p->get_id());
//        return $p;

    // We sync the stock
    $manageStock = get_option('uniconta_manage_stock');

    if ($manageStock) {

      $p = $product->product;
      $api = new WedoioApi();

//            d($this->data);

      global $InvItemStorages;
      $InvItemStorage = isset($InvItemStorages[$this->_Item]) ? $InvItemStorages[$this->_Item] : false;
      if (!$InvItemStorage) {
        $InvItemStorage = $api->fetch("InvItemStorage", [
          "_Item" => $this->_Item
        ], true);

        $InvItemStorages[$this->_Item] = $InvItemStorage;
      }

      // We need to find the right stock for the InvItemStorage

//            d($InvItemStorage);

      $qty = 0;
      if ($InvItemStorage) {
        foreach ($InvItemStorage as $storage) {
          if (
            $storage->_Variant1 == $this->_Variant1 &&
            $storage->_Variant2 == $this->_Variant2 &&
            $storage->_Variant3 == $this->_Variant3 &&
            $storage->_Variant4 == $this->_Variant4 &&
            $storage->_Variant5 == $this->_Variant5
          ) {
            $qty += ($storage->_Qty - $storage->_QtyReserved);
          }
        }
      }

      $p->set_manage_stock("yes");
      $p->set_stock_quantity($qty);

      if (!$qty) {
        $p->set_stock_status("outofstock");
      } else {
        $p->set_stock_status("instock");
      }

      $p->save();
    }

    // We sync the mapping
    $variant = $this->get_variant_detail();
    if ($variant) {
      $p = $product->product;

      foreach ($variant->user_fields_info as $field => $field_value) {
        try {
          $method = "set_" . $field;
          if (method_exists($p, $method)) {
            $value = $field_value['value'];
            if ($value) {
              $p->$method($value);
            }
          }
        } catch (Exception $e) {
          Wedoio_Watchdog::log("InVariantCombi Error", "$field : $value | " . $e->getMessage());
        }
      }

      $p->save();
    }

    // We sync the prices
    $variation_pricelist = get_option("variation_pricelist", "Standard Prisliste");

    if ($variation_pricelist) {
      $p = $product->product;
      $pricelist = $this->get_price_list($variation_pricelist);
      $price = $pricelist->_Price;

      if (!$price) {
        $price = $parent_product->product->get_price();
      }

      if ($price) {
        $p->set_price($price);
        $p->set_regular_price($price);
        $p->save();
      }
    }

    $sync_currencies = apply_filters("wedoio_sync_currencies", true, $parent, $product);

    if ($sync_currencies) $this->sync_currencies();

    $p = $product->product;

    $t = time() - $s;
//    Wedoio_Watchdog::log("Product Sync", "Synchronization of Variation $variant_rowid for $parent_Item completed in $t s");
    do_action("wedoio_after_syncing_invitem_variation", $this, $product, $parent);

    return $p;
  }

  function sync_currencies() {
    $multicurrencies = get_option('use_plugin_woocommerce_multilingual_currencies', false);

    $api = new WedoioApi();
    if (!$this->parent) $this->get_original_invItem();
    $invitem = $this->parent;

    $currencies = $api->getEnum("Currencies");
    $default_currency = get_woocommerce_currency();

    $pid = $this->product->get_id();
    $product = wc_get_product($pid);

    $sales = [];

    for ($i = 1; $i <= 3; $i++) {
      $salesprice_field = "_SalesPrice" . $i;
      $currency_field = "_Currency" . $i;
      $price = $invitem->$salesprice_field;
      $currency = $invitem->$currency_field;
      $currency = $currencies[$currency];
      $sales[$currency] = $price;

      if ($currency == $default_currency) {
        // Main Price
        $product->set_price($price);
        $product->set_regular_price($price);
        $product->save();
      } else {
        update_post_meta($pid, "_sale_price_dates_to_" . $currency, "");
        update_post_meta($pid, "_sale_price_dates_from_" . $currency, "");
        update_post_meta($pid, "_regular_price_" . $currency, $price);
        update_post_meta($pid, "_wcml_schedule_" . $currency, "0");
        update_post_meta($pid, "_sale_price_" . $currency, "");
        update_post_meta($pid, "_price_" . $currency, $price);
      }
    }

    if ($multicurrencies) {
      update_post_meta($pid, "_wcml_custom_prices_status", "1");
    } else {
      update_post_meta($pid, "_wcml_custom_prices_status", 0);
    }
  }

  public function clean_attributes_array(&$attributes) {
    $null_variant_value = get_option("uniconta_variant_null_value", '---o---');
    foreach ($attributes as $key => $variations_attribute) {
      if ($variations_attribute == $null_variant_value) {
        $attributes[$key] = null;
      }
    }
  }
}
