<?php
/**
 * InvItem / Products Synchronization functions
 */

class Wedoio_InvItem {

  /**
   * Sync Invitem from Item
   * @param $Item
   */
  public static function syncInvItemFromItem($Item) {
    $api = new WedoioApi();
    $invItems = $api->fetch("InvItem", array("_Item" => $Item));
    $invItem = reset($invItems);
//    d($invItem);
    if ($invItem) {
      Wedoio_InvItem::syncInvItem($invItem);
    }
  }

  /**
   * Sync Invitem from RowID
   * @param $rowId
   */
  public static function syncInvItemFromRowId($rowId) {
    $api = new WedoioApi();
    $invItem = $api->fetch("InvItem", $rowId);
    if ($invItem) {
      Wedoio_InvItem::syncInvItem($invItem);
    }
  }

  /**
   * Sync an invitem images from a rowId
   * @param $rowId
   */
  public static function syncInvItemImagesFromRowId($rowId) {
    $api = new WedoioApi();
    $invItem = $api->fetch("InvItem", $rowId);
    if ($invItem) {
      Wedoio_InvItem::syncInvItem($invItem);
    }
  }

  /**
   * Synchronize an InvItem with a woocommerce product
   * @param $invItem
   */
  public static function syncInvItem($invItem, $syncImages = true) {
//        WedoioApi::extractFields("UserFields","UserField",$invItem);
    $rowId = $invItem->RowId;

    $product = new WedoioProduct();
    $product->load_by_rowid($rowId);
//    d($product);
    $product->sync();
    return $product->pid;
  }

  /**
   * Synchronize the InvItem Images with the woocommerce product
   * @param $invItem
   */
  public static function syncInvItemImages($invItem) {
    $api = new WedoioApi();
    $rowId = $invItem->RowId;

    $wpProduct = Wedoio_Admin::findProductByRowId($rowId);
    $pid = false;
    if ($wpProduct->ID) {
      $pid = $wpProduct->ID;
    }

    if (!$pid) return false;

    ini_set('memory_limit', '256M');

    $pics = $api->fetch("UserDocsClient/0/file", array(
      "_TableRowId" => $rowId
    ));

    $primary_group = get_option("uniconta_invitem_group_primary", 0);
    $gallery_group = get_option("uniconta_invitem_group_gallery", 0);

    foreach ($pics as $pic) {
      $img = array(
        "filename" => $pic->_Text,
        "data" => $pic->_Data,
        "extension" => $pic->_DocumentType
      );

      if ($pic->Group == $primary_group) {
        $product['images']["featured"][] = $img;
      } else if ($pic->Group == $gallery_group) {
        $product['images']["gallery"][] = $img;
      }
    }

    $images = isset($product['images']) ? $product['images'] : array();

    $md5 = md5(json_encode($images));
    if ($pid) {
      $current_md5 = get_post_meta($pid, "_uniconta_images_md5", true);
      if ($md5 == $current_md5) {
        do_action("wedoio_after_syncing_invitem_images", $invItem);
        return $pid;
      }
    } else {
      do_action("wedoio_after_syncing_invitem_images", $invItem);
      return;
    }

    $featured = false;
    foreach ($images['featured'] as $image) {
//      d($image);
      $attach_id = Wedoio_InvItem::setProductImage($image, $pid);
      $featured = $attach_id;
    }
    if ($featured) {
      set_post_thumbnail($pid, $featured);
    }

    $galleries = array();
    foreach ($images['gallery'] as $image) {
//      d($image);
      $attach_id = Wedoio_InvItem::setProductImage($image, $pid);
      $galleries[] = $attach_id;
    }
    if ($galleries) {
      delete_post_meta($pid, "_product_image_gallery");
      add_post_meta($pid, '_product_image_gallery', implode(',', $galleries));
    }

    do_action("wedoio_after_syncing_invitem_images", $invItem);
  }

  /**
   * Set a woocommerce product from data
   * @param $product
   */
  public static function setProduct($product, $invItem) {
    $id = isset($product['ID']) ? $product['ID'] : false;

    if ($id) {
      $product_obj = self::getProduct($id);
    } else {
      $product_obj = new WC_Product_Simple();
    }

    // Gathering the field mapping option
    $mapping = esc_attr(get_option('uniconta_products_field_mapping'));
    $mapping = explode("\n", $mapping);
    $fieldsMapping = array();


    foreach ($mapping as $line) {
      $explode = explode("|", $line);
      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = $explode[0];
        $wp = $explode[1];
        $fieldsMapping[$uniconta] = $wp;
      }
    }

    foreach ($fieldsMapping as $uniconta => $wp) {

      $wp = trim($wp);

      if (isset($product[$uniconta])) {
        $value = $product[$uniconta];
        $product[$wp] = $value;
        $product['meta'][$wp] = $value;
      }

      if (isset($product['user_fields'][$uniconta])) {
        $value = $product['user_fields'][$uniconta]['value'];
        $product[$wp] = $value;
        $product['meta'][$wp] = $value;
      }
    }

    // We check if the uniconta_sync_status is set
    if (array_key_exists('uniconta_sync_status', $product['meta'])) {
      $sync_status = boolval($product['meta']['uniconta_sync_status']);
      if ($sync_status === false) {
        Wedoio_Watchdog::log("Product Sync", "Sync has been skipped because the uniconta_sync status has been set to false");
        return $id;
      }
    }

    $product = apply_filters("wedoio_after_building_product_data", $product, $invItem);

    if (!$id) {
      $id = wp_insert_post($product);
    } else {
      $id = wp_update_post($product);
    }


    if (isset($product['meta']) && $id) {
      $metas = $product['meta'];
      foreach ($metas as $key => $value) {
        update_post_meta($id, $key, $value);
      }
    }

    if (array_key_exists('uniconta_featured', $product['meta'])) {
      $featured = boolval($product['meta']['uniconta_featured']);
      $p = wc_get_product($id);
      $p->set_featured($featured);
      $p->save();
    }

    // We set the status of the product depending of the field xWebPublish
    if (isset($product['user_fields']['xWebPublish']['value'])) {
      $xwebpublish = strtolower($product['user_fields']['xWebPublish']['value']);
      $xwebpublish = trim($xwebpublish);
      if ($xwebpublish == "nej" || $xwebpublish == "no" || $xwebpublish == "nei" || $xwebpublish == "") {
        $publish = "pending";
      } else {
        $publish = "publish";
      }

      $p = wc_get_product($id);
      $p->set_status($publish);
      $p->save();
    }

    if (array_key_exists('uniconta_publish', $product['meta'])) {
      $publish = boolval($product['meta']['uniconta_publish']);
      $publish = $publish ? "publish" : "pending";
      $p = wc_get_product($id);
      $p->set_status($publish);
      $p->save();
    }

    $categories_mapping = esc_attr(get_option('uniconta_categories_mapping'));
    $categories_mapping = explode("\n", $categories_mapping);

    $tags_mapping = esc_attr(get_option('uniconta_tags_mapping'));
    $tags_mapping = explode("\n", $tags_mapping);

    $categories_ids = array();
    $tags_ids = array();

    foreach ($categories_mapping as $category_line) {
      $explode = explode("|", $category_line);
      $cat_id = $explode[0];
      $cat_id = trim($cat_id);
      $term = $explode[1];
      $term = trim($term);
      $categories_ids[$cat_id] = $term;
    }

    foreach ($tags_mapping as $tag_line) {
      $explode = explode("|", $tag_line);
      $tag_id = $explode[0];
      $tag_id = trim($tag_id);
      $term = $explode[1];
      $term = trim($term);
      $tags_ids[$tag_id] = $term;
    }

    $cats = array();
    $tags = array();

    foreach ($mapping as $line) {
      $explode = explode("|", $line);
      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = $explode[0];
        $uniconta = trim($uniconta);
        $wp = $explode[1];
        $wp = trim($wp);

        if ($wp == "product_cat") {
          $value = $product['user_fields'][$uniconta]['value'];
          $value = trim($value);
          if (array_search($value, $categories_ids)) {
            $cats_ids = explode(",", $value);
            $cats[] = array_search($value, $categories_ids);
          }
        }

        if ($wp == "product_tag") {
          $value = $product['user_fields'][$uniconta]['value'];
          $value = trim($value);
          if (array_search($value, $tags_ids)) {
            $tags[] = array_search($value, $tags_ids);
          }
        }
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
      wp_set_object_terms($id, $cats, 'product_cat');
    }

    if ($tags) {
      $tags = implode(",", $tags);
      $tags = explode(",", $tags);
      $tags_tmp = array_unique($tags);
      $tags = array();
      foreach ($tags_tmp as $tag) {
        $tags[] = intval($tag);
      }
      wp_set_object_terms($id, $tags, 'product_tag');
    }

//        if (isset($product['product_tag'])) {
//            $terms = explode(",", $product['product_tag']);
//            wp_set_object_terms($id, $terms, 'product_tag');
//        }

//        update_post_meta($id, "_uniconta_md5", $md5);

    return $id;
  }

  /**
   * Set a image data from the images on Uniconta
   * @param $data
   * @param $post_id
   * @return mixed
   */
  public static function setProductImage($data, $post_id) {
    // We check first if the image exist in the system already

    $userdoc = $data['userdoc'] ?? false;
    $tablerowid = $userdoc->_TableRowId;

//    d($data);
    if (!$data['data']) {
      $url = $userdoc->_Url;
//      d($userdoc);
//      d($url);
      if (isset($url)) {
        $file = file_get_contents($url);
        $file = base64_encode($file);
        if (isset($file)) {
          $data['data'] = $file;
          $userdoc->_Data = $file;
        } else {
          return;
        }
      } else {
        return;
      }
    }

    $ext = Wedoio_Helper::getExtension($data['extension']);
    $filename = md5($tablerowid . $userdoc->_Data) . $ext;

//    d($userdoc);


    if ($userdoc) {
      $existing_images = self::find_existing_images($filename, $post_id);

      if ($existing_images) {
        $existing_image = reset($existing_images);
        $attachment_id = $existing_image->post_id;
        return $attachment_id;
      }

      // We fetch the image data
//            $api = new WedoioApi();
//            $rowId = $userdoc->RowId;
//            $image = $api->fetch("UserDocsClient/$rowId/file", array(
//                "_TableRowId" => $userdoc->_TableRowId
//            ));
//            $data['data'] = $image->_Data;
    }

    if (!$data['data']) return;

    $upload_dir = wp_upload_dir();
    $image_data = base64_decode($data['data']);

    if (wp_mkdir_p($upload_dir['path'])) {
      $file = $upload_dir['path'] . '/' . $filename;
    } else {
      $file = $upload_dir['basedir'] . '/' . $filename;
    }
    file_put_contents($file, $image_data);

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

    return $attach_id;
  }

  public static function find_existing_images($filename, $post_id) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%$filename%'";
    $results = $wpdb->get_results($query);
    return $results;
  }

  /**
   * Get all the products from the system
   * @param array $args
   * @return array
   */
  public static function getAllProducts($args = []) {
    $query = new WP_Query(array('post_type' => 'product', "posts_per_page" => -1));
    $products = $query->get_posts();
    return $products;
  }

  /**
   * Return the product data given a pid
   * @param $pid
   * @return array
   */
  public static function getProduct($pid) {
    $product = wc_get_product($pid);
    $product_data = self::getProductData($product);
    return $product_data;
  }

  /**
   * Return some product data given a product object
   * @param $product
   * @param string $context
   * @return array
   */
  public static function getProductData($product, $context = 'view') {
    $data = array(
      'id' => $product->get_id(),
      'name' => $product->get_name($context),
      'slug' => $product->get_slug($context),
      'permalink' => $product->get_permalink(),
      'type' => $product->get_type(),
      'status' => $product->get_status($context),
      'featured' => $product->is_featured(),
      'catalog_visibility' => $product->get_catalog_visibility($context),
      'description' => 'view' === $context ? wpautop(do_shortcode($product->get_description())) : $product->get_description($context),
      'short_description' => 'view' === $context ? apply_filters('woocommerce_short_description', $product->get_short_description()) : $product->get_short_description($context),
      'sku' => $product->get_sku($context),
      'price' => $product->get_price($context),
      'regular_price' => $product->get_regular_price($context),
      'sale_price' => $product->get_sale_price($context) ? $product->get_sale_price($context) : '',
      'price_html' => $product->get_price_html(),
      'on_sale' => $product->is_on_sale($context),
      'purchasable' => $product->is_purchasable(),
      'total_sales' => $product->get_total_sales($context),
      'virtual' => $product->is_virtual(),
      'downloadable' => $product->is_downloadable(),
      'download_limit' => $product->get_download_limit($context),
      'download_expiry' => $product->get_download_expiry($context),
      'external_url' => $product->is_type('external') ? $product->get_product_url($context) : '',
      'button_text' => $product->is_type('external') ? $product->get_button_text($context) : '',
      'tax_status' => $product->get_tax_status($context),
      'tax_class' => $product->get_tax_class($context),
      'manage_stock' => $product->managing_stock(),
      'stock_quantity' => $product->get_stock_quantity($context),
      'in_stock' => $product->is_in_stock(),
      'backorders' => $product->get_backorders($context),
      'backorders_allowed' => $product->backorders_allowed(),
      'backordered' => $product->is_on_backorder(),
      'sold_individually' => $product->is_sold_individually(),
      'weight' => $product->get_weight($context),
      'dimensions' => array(
        'length' => $product->get_length($context),
        'width' => $product->get_width($context),
        'height' => $product->get_height($context),
      ),
      'shipping_required' => $product->needs_shipping(),
      'shipping_taxable' => $product->is_shipping_taxable(),
      'shipping_class' => $product->get_shipping_class(),
      'shipping_class_id' => $product->get_shipping_class_id($context),
      'reviews_allowed' => $product->get_reviews_allowed($context),
      'average_rating' => 'view' === $context ? wc_format_decimal($product->get_average_rating(), 2) : $product->get_average_rating($context),
      'rating_count' => $product->get_rating_count(),
      'related_ids' => array_map('absint', array_values(wc_get_related_products($product->get_id()))),
      'upsell_ids' => array_map('absint', $product->get_upsell_ids($context)),
      'cross_sell_ids' => array_map('absint', $product->get_cross_sell_ids($context)),
      'parent_id' => $product->get_parent_id($context),
      'purchase_note' => 'view' === $context ? wpautop(do_shortcode(wp_kses_post($product->get_purchase_note()))) : $product->get_purchase_note($context),
      'variations' => array(),
      'grouped_products' => array(),
      'menu_order' => $product->get_menu_order($context),
      'meta_data' => $product->get_meta_data(),
    );

    return $data;
  }
}
