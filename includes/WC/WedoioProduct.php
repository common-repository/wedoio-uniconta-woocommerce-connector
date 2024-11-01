<?php
/**
 * Woocommerce product
 */

class WedoioProduct {
  public $pid;
  public $rowId;
  public $product;
  public $invitem;

  function __construct($pid = false) {
    $product = false;

    if ($pid) {
      $product = wc_get_product($pid);
    }

    if (!$product) {
      $product = new WC_Product_Simple();
    }

    $this->product = $product;
    $this->pid = $product->get_id();
    $this->rowId = $this->get_rowId();
  }

  /**
   * @param $rowid
   */
  function load_by_rowid($rowid) {
    $args = [
      "meta_query" => [
        [
          'key' => "_uniconta-rowid",
          'value' => $rowid,
          'compare' => '='
        ]
      ]
    ];

    $query = $this->query($args);

    $products = array();

    foreach ($query->posts as $product_id) {
      $products[] = $this->get_product($product_id);
      break;
    }

    $product = reset($products);

    if ($product) {
      $this->pid = $product->get_id();
      $this->rowId = $this->get_rowId();
    } else {
      $product = new WC_Product_Simple();
      $this->rowId = $rowid;
    }

    $this->product = $product;
  }

  /**
   * @param $rowid
   */
  function load_by_sku($sku) {
    $args = [
      "meta_query" => [
        [
          'key' => "_sku",
          'value' => $sku,
          'compare' => '='
        ]
      ]
    ];

    $query = $this->query($args);

    $products = array();

    foreach ($query->posts as $product_id) {
      $products[] = $this->get_product($product_id);
      break;
    }

    $product = reset($products);

    if ($product) {
      $this->pid = $product->get_id();
    } else {
      $product = new WC_Product_Simple();
    }

    $this->product = $product;
  }

  /**
   * Helper for the queries
   * @param array $args
   * @return WP_Query
   */
  function query($args = []) {
    $query_args = array(
      'fields' => 'ids',
      'post_type' => ['product', 'product_variation'],
      'post_status' => ['publish', 'pending', 'draft'],
      'meta_query' => array(),
    );

    if (!empty($args['sku'])) {
      if (!is_array($query_args['meta_query'])) {
        $query_args['meta_query'] = array();
      }

      $query_args['meta_query'][] = array(
        'key' => '_sku',
        'value' => $args['sku'],
        'compare' => '=',
      );
    }

    $query_args = array_replace($query_args, $args);

    return new WP_Query($query_args);
  }

  /**
   * @param $pid
   * @return false|null|WC_Product
   */
  function get_product($pid) {
    $product = wc_get_product($pid);
    return $product;
  }

  /**
   * Get the related Invitem
   * @return WedoioInvitem
   */
  function get_related_invitem($reset = false) {
    $invitem = false;

    if (!$this->invitem || $reset) {
      $rowId = $this->get_rowId();
      $invitem = new WedoioInvitem($rowId);
    } else {
      $invitem = $this->invitem;
    }

    $this->invitem = $invitem;
    return $invitem;
  }

  /**
   * Get related rowId
   * @return mixed
   */
  function get_rowId() {
    if ($this->pid) {
      return get_post_meta($this->pid, "_uniconta-rowid", true);
    }

    return $this->rowId;
  }

  /**
   * Sync the product from the rowid
   */
  function sync($images_sync = false) {
    $m = memory_get_usage();
    $invitem = $this->get_related_invitem();
    $this->invitem = $invitem;
    $product = $this->product;

    $start = time();

    if (!$invitem) {
      Wedoio_Watchdog::log("Product Sync", "InvItem not found");
      return;
    } else {
      $invitem_rowid = $invitem->RowId;
      Wedoio_Watchdog::log("Product Sync", "Synchronization of $invitem_rowid in progress");
    }

    if (count($invitem->user_fields_info) == 0) {

      $api = new WedoioApi();
      $api->send("Company/XXXXX");

      $invitem = new WedoioInvitem($invitem_rowid);
      $this->invitem = $invitem;

      if (count($invitem->user_fields_info) == 0) {
        Wedoio_Watchdog::log("Product Sync", "Aborted because the userfields are absent.");
        return;
      }
    }

    $mapping = $this->mapping();

    $sku = $mapping['_sku'];

    if (!$this->pid) {
      $this->load_by_sku($sku);
      $product = $this->product;
    }


    $product_id = $product->get_id();
    if ($product_id) {
      Wedoio_Watchdog::log("Product Sync", "Invitem $invitem_rowid Linked to Product $product_id.");
    } else {
      Wedoio_Watchdog::log("Product Sync", "Invitem $invitem_rowid not Linked yet.");
    }

    // We extract the categories and the tags from the mapping
    $categories = isset($mapping['product_cat']) ? $mapping['product_cat'] : [];
    $taxonomies = isset($mapping['product_tag']) ? $mapping['product_tag'] : [];

    unset($mapping['product_cat']);
    unset($mapping['product_tag']);

    // We decide what to do with the stock
    $manageStock = get_option('uniconta_manage_stock');
    if ($manageStock) {
      $api = new WedoioApi();
      global $InvItemStorages;
      $InvItemStorage = isset($InvItemStorages[$invitem->_Item]) ? $InvItemStorages[$invitem->_Item] : false;
      if (!$InvItemStorage) {
        $InvItemStorage = $api->fetch("InvItemStorage", [
          "_Item" => $invitem->_Item
        ]);

        $InvItemStorages[$invitem->_Item] = $InvItemStorage;
      }

      // We need to find the right stock for the InvItemStorage

      $qty = 0;
      if ($InvItemStorage) {

        foreach ($InvItemStorage as $storage) {

          $null_variant_value = get_option("uniconta_variant_null_value", '---o---');
          $variant1 = $storage->_Variant1;
          $variant1 = str_replace($null_variant_value, "", $variant1);

          $variant2 = $storage->_Variant2;
          $variant2 = str_replace($null_variant_value, "", $variant2);

          $variant3 = $storage->_Variant3;
          $variant3 = str_replace($null_variant_value, "", $variant3);

          $variant4 = $storage->_Variant4;
          $variant4 = str_replace($null_variant_value, "", $variant4);

          $variant5 = $storage->_Variant5;
          $variant5 = str_replace($null_variant_value, "", $variant5);

          if (
            $variant1 == null &&
            $variant2 == null &&
            $variant3 == null &&
            $variant4 == null &&
            $variant5 == null
          ) {
            // We need to check the warehouse too
            $qty += ($storage->_Qty - $storage->_QtyReserved);
          }
        }
      }

      $product->set_stock_quantity($qty);

      if (!$qty) {
        $product->set_stock_status("outofstock");
      } else {
        $product->set_stock_status("instock");
      }

      $product->set_manage_stock("yes");
    } else {
      // This way we don't touch the stock at all
//      $product->set_manage_stock("no");
    }

    // We control the status
    $status = $this->can_sync();

    if ($status === false) {
      return;
    }

    $product->set_status($status);

    if ($status == "pending") {
      $product->save();
      $completion = time() - $start;
      Wedoio_Watchdog::log("Product Sync", "Synchronization of $invitem_rowid completed in $completion s. Product set as pending.");
      return;
    }

    // We set the featured status if it's set
    if (isset($mapping['uniconta_featured'])) {
      $featured = boolval($mapping['uniconta_featured']);
      $product->set_featured($featured);
    }

    if (isset($mapping['post_title'])) {
      $title = $mapping['post_title'] ? wc_clean($mapping['post_title']) : false;
      if ($title) $product->set_name($title);
    }

    if (isset($mapping['post_content'])) {
      $desc = $mapping['post_content'] ? $mapping['post_content'] : "";
      $product->set_description($desc);
    }

    if (isset($mapping['post_excerpt'])) {
      $desc = $mapping['post_excerpt'] ? $mapping['post_excerpt'] : "";
      $product->set_short_description($desc);
    }

    if (isset($mapping['_price'])) {
      $product->set_price($mapping['_price']);
    }

    if (isset($mapping['_regular_price'])) {
      $product->set_regular_price($mapping['_regular_price']);
    }

    if (isset($mapping['_sale_price'])) {
      $product->set_sale_price($mapping['_sale_price']);
    }

    $product->save();

    // Now we need to set the meta
    $pid = $product->get_id();
    $this->product = $product;
    $this->pid = $pid;

    if (!$product_id && $pid) {
      Wedoio_Watchdog::log("Product Sync", "Invitem $invitem_rowid Linked to Product $pid.");
    }

    // We save the data on the product
    $mapping['_uniconta_data'] = json_encode($invitem->data, JSON_UNESCAPED_UNICODE);

    foreach ($mapping as $key => $value) {
      update_post_meta($pid, $key, $value);

      if (strpos($key, "ACF_") === 0) {
        try {
          $acf_field = str_replace("ACF_", "", $key);
          update_field($acf_field, $value, $pid);
        } catch (Exception $e) {
          Wedoio_Watchdog::log("Product Sync", "Error syncing ACF Field $key");
        }
      }
    }

    // We set the categories
    $categories_mapping = esc_attr(get_option('uniconta_categories_mapping'));
    $categories_mapping = htmlspecialchars_decode($categories_mapping);
    $categories_mapping = explode("\n", $categories_mapping);
    $categories_ids = array();

    foreach ($categories_mapping as $category_line) {
      $explode = explode("|", $category_line);
      $cat_id = $explode[0];
      $cat_id = trim($cat_id);
      $term = $explode[1];
      $term = trim($term);
      $categories_ids[$cat_id] = $term;
    }

    $cats = [];
    foreach ($categories as $category) {
      $category = trim($category);
      $c_id = array_search($category, $categories_ids);
      if ($c_id !== false) {
        $cats[] = $c_id;
      }
    }

    if ($cats) {
      $cats = implode(",", $cats);
      $cats = explode(",", $cats);
      $cats_tmp = array_unique($cats);
      $cats = array();
      foreach ($cats_tmp as $cat) {
        $cats[] = intval($cat);
      }
      wp_set_object_terms($pid, $cats, 'product_cat');
    }

    // We set the tags
    $tags_mapping = esc_attr(get_option('uniconta_tags_mapping'));
    $tags_mapping = htmlspecialchars_decode($tags_mapping);
    $tags_mapping = explode("\n", $tags_mapping);
    $tags_ids = array();

    foreach ($tags_mapping as $tag_line) {
      $explode = explode("|", $tag_line);
      $tag_id = $explode[0];
      $tag_id = trim($tag_id);
      $term = $explode[1];
      $term = trim($term);
      $tags_ids[$tag_id] = $term;
    }

    $tags = [];
    foreach ($taxonomies as $tag) {
      $tag = trim($tag);
      $t_id = array_search($tag, $tags_ids);
      if ($t_id !== false) {
        $tags[] = $t_id;
      }
    }

    if ($tags) {
      $tags = implode(",", $tags);
      $tags = explode(",", $tags);
      $tags_tmp = array_unique($tags);
      $tags = array();
      foreach ($tags_tmp as $tag) {
        $tags[] = intval($tag);
      }
      wp_set_object_terms($pid, $tags, 'product_tag');
    }

    $images_sync = apply_filters("wedoio_sync_images", true, $invitem, $product);
    $sync_currencies = apply_filters("wedoio_sync_currencies", true, $invitem, $product);
    $sync_variations = apply_filters("wedoio_sync_variations", true, $invitem, $product);

    if ($images_sync) $this->sync_images();
    if ($sync_currencies) $this->sync_currencies();
    if ($sync_variations) $this->sync_variations();

    do_action("wedoio_after_syncing_invitem", $invitem, $product);

    $completion = time() - $start;

    $m = memory_get_usage() - $m;
    $m = _wc_convert($m);
    Wedoio_Watchdog::log("Product Sync", "Synchronization of $invitem_rowid completed in $completion s. Memory Used : $m");

//        d($completion . " s");
  }

  /**
   * Synchronize the images from the invitem
   */
  function sync_images() {
    $invitem = $this->invitem;
    if (!$invitem) {
      $invitem = $this->get_related_invitem();
      $this->invitem = $invitem;
    }
    $primary_group = get_option("uniconta_invitem_group_primary", 0);
    $gallery_group = get_option("uniconta_invitem_group_gallery", 0);
    $pics = $invitem->fetch_attachments();


    foreach ($pics as $i => $pic) {
      $filename = $pic->_Text;
      if (!$filename) {
        $filename = $this->rowId . "_" . $i;
      }
      $img = array(
        "filename" => $filename,
        "data" => $pic->_Data,
        "extension" => $pic->_DocumentType,
        "userdoc" => $pic
      );
      if ($pic->Group == $primary_group) {
        $product['images']["featured"][] = $img;
      } else if ($pic->Group == $gallery_group) {
        $product['images']["gallery"][] = $img;
      }
    }
    $images = isset($product['images']) ? $product['images'] : array();
    $md5 = md5(json_encode($images));
    $pid = $this->pid;
    $product = $this->product;
    if ($pid) {
//            $current_md5 = get_post_meta($pid, "_uniconta_images_md5", true);
//            if ($md5 == $current_md5){
//                do_action("wedoio_after_syncing_invitem_images",$invitem->data);
//                return $pid;
//            }
    } else {
      do_action("wedoio_after_syncing_invitem_images", $invitem->data, $product);
      return;
    }
    $featured = false;
    if (isset($images['featured'])) {
      foreach ($images['featured'] as $image) {
        $attach_id = Wedoio_InvItem::setProductImage($image, $pid);
        $featured = $attach_id;
      }
    }


    if ($featured) {
      $current_featured = get_post_thumbnail_id($pid);
      if ($featured != $current_featured) {
        set_post_thumbnail($pid, $featured);
      }
    }
    $galleries = array();
    if (isset($images['gallery'])) {
      foreach ($images['gallery'] as $image) {
        $attach_id = Wedoio_InvItem::setProductImage($image, $pid);
        $galleries[] = $attach_id;
      }
    }
    if ($galleries) {
      $current_images = get_post_meta($pid, "_product_image_gallery", true);
      $current_images = explode(",", $current_images);
      sort($current_images);
      sort($galleries);
      $key_current = md5(json_encode($current_images));
      $key_new = md5(json_encode($galleries));

      if ($key_current != $key_new) {
        delete_post_meta($pid, "_product_image_gallery");
        add_post_meta($pid, '_product_image_gallery', implode(',', $galleries));
      }
    }
    do_action("wedoio_after_syncing_invitem_images", $invitem->data, $product);
  }

  function sync_variations() {

    $api = new WedoioApi();
    $invitem = $this->invitem;
    if (!$invitem) {
      $invitem = $this->get_related_invitem();
      $this->invitem = $invitem;
    }

    // We fetch the variations
    $item = $invitem->_Item;
    $variations_found = $api->fetch("InvVariantCombi", ['_Item' => $item]);

    if ($invitem->_UseVariants) {
      $variable = true;
    } else {
      $variable = false;
    }

    if ($variable) {
      if ($this->product->get_type() != "variable") {
        // We set the parent product type to variable
        $this->product = new WC_Product_Variable($this->pid);
      }

      $this->product->set_manage_stock("no");
      $this->product->save();
    } else {
      if ($this->product->get_type() != "simple") {
        // We set the parent product type to variable
        $this->product = new WC_Product_Simple($this->pid);
        $this->product->save();
      }
      return;
    }

    $this->invitem->product = $this;

    $existing_variations = $this->product->get_children();
    foreach ($existing_variations as $existing_variation) {
      $existing_product = wc_get_product($existing_variation);
      if ($existing_product) $existing_product->delete();
    }
    $existing_variations = [];

    $product_variations = array_flip($existing_variations);

    $current_parent_attributes = $this->product->get_attributes();
    // We separate the variations between the valid ones and the ones to delete
    $variations = [];

    $v_attributes = [];
    foreach ($variations_found as $variation) {
      if ($variation->_Variant1 === null) continue;
      $v = new WedoioInvVariantCombi();
      $v->import($variation);
      $v->parent = $this->get_related_invitem();
      $v->parent_product = $this;
      $vp = $v->sync();
      if ($vp) {
        unset($product_variations[$vp->get_id()]);
        $variations[] = $v;
        $v_product_attributes = $vp->get_attributes();
        $v_attributes = array_replace($v_attributes, $v_product_attributes);
      }

    }

    foreach ($v_attributes as $taxonomy => $v_attribute) {
      if (!$v_attribute) {
        unset($v_attributes[$taxonomy]);
      } else {
        $current_parent_attributes[$taxonomy] = $v_attribute;
      }
    }

    foreach ($current_parent_attributes as $taxonomy => $product_attribute) {
      if (!isset($v_attributes[$taxonomy])) {
        unset($current_parent_attributes[$taxonomy]);
      }
    }

//        $this->product->set_attributes($current_parent_attributes);
//        $this->product->save();

    $existing_variations = array_flip($product_variations);

//        d($existing_variations);

    // We remove the unmatching products
    foreach ($existing_variations as $existing_variation) {
      $existing_product = wc_get_product($existing_variation);
      if ($existing_product) $existing_product->delete();
    }

    // We need to remove the unused product attributes

//        $variable = false;

    // We need to find a match between the existing variations and the variations to be synced


//        if($variable){
//            foreach($variations as $variation){
//                $sync = $variation->sync();
//                if($sync) {
//                    $index = array_search($sync,$existing_variations);
//                    unset($existing_variations[$index]);
//                }
////                if($sync) $variable = true;
//            }
//        }
//
//        foreach($existing_variations as $existing_variation){
//            $product_variation = wc_get_product($existing_variation);
//            $product_variation->delete();
//        }
//
  }

  function sync_currencies() {
    $multicurrencies = get_option('use_plugin_woocommerce_multilingual_currencies', false);

    if ($multicurrencies) {

      $api = new WedoioApi();
      $invitem = $this->get_related_invitem();

      $currencies = $api->getEnum("Currencies");
      $default_currency = get_woocommerce_currency();

      $product = $this->product;
      $pid = $product->get_id();
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

      update_post_meta($pid, "_wcml_custom_prices_status", "1");
    } else {
      update_post_meta($pid, "_wcml_custom_prices_status", 0);
    }
  }

  function __set($name, $value) {
    if (method_exists($this, $name)) {
      $this->$name($value);
    } else {
      $this->$name = $value;
    }
  }

  function __get($name) {
    if (method_exists($this, $name)) {
      return $this->$name();
    } elseif (property_exists($this, $name)) {
      return $this->$name;
    }
    return null;
  }

  /**
   * Return a mapping
   * @return array
   */
  function mapping() {

    $invitem = $this->get_related_invitem();

    $mapping = [
      "post_title" => $invitem->_Name,
      "_sku" => $invitem->_Item,
      "_uniconta-rowid" => $invitem->RowId,
      "_price" => $invitem->_SalesPrice1,
      "_regular_price" => $invitem->_SalesPrice1
    ];

    $tmp_mapping = esc_attr(get_option('uniconta_products_field_mapping'));
    $tmp_mapping = explode("\n", $tmp_mapping);

    $product_mapping = [];

    foreach ($tmp_mapping as $line) {
      $explode = explode("|", $line);
      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = trim($explode[0]);
        $wp = trim($explode[1]);
        if ($wp == "product_cat" || $wp == "product_tag") {
          $product_mapping[$wp][] = $invitem->$uniconta;
        } else {
          $product_mapping[$wp] = $invitem->$uniconta;
        }
      }
    }

    $mapping = array_replace($mapping, $product_mapping);

    if (!$mapping['post_title']) {
      $mapping['post_title'] = $invitem->_Name;
    }

    $mapping = apply_filters("wedoio_after_building_product_mapping", $mapping, $invitem);

    return $mapping;
  }

  /**
   * @return bool
   */
  function can_sync() {

    $invitem = $this->get_related_invitem();
    $mapping = $this->mapping();

    $status = "publish";

    if (array_key_exists("xWebPublish", $invitem->data)) {
      if ($invitem->xWebPublish) {
        $xWebPublish = $invitem->xWebPublish ? (string)$invitem->xWebPublish : null;
        if (is_string($xWebPublish)) $xWebPublish = trim(strtolower($xWebPublish));
        if ($xWebPublish == "nej" || $xWebPublish == "no" || $xWebPublish == "nei" || !$xWebPublish) {
          $status = "pending";
        }
      } else {
        $status = "pending";
      }
    }

    if (array_key_exists('uniconta_publish', $mapping)) {
      $publish = boolval($mapping['uniconta_publish']);
      $status = $publish ? "publish" : "pending";
    }

    if (!$this->pid && $status == "pending") {
      // We skip if it's a new product to be created and the status is set to pending
      return false;
    }

    $skip = isset($mapping['skip']) ? $mapping['skip'] : false;
    if ($skip) {
      return false;
    }

    return $status;
  }

  function __call($method, $args) {
    return call_user_func([$this->product, $method], $args);
  }
}

function _wc_convert($size) {
  $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
  return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}
