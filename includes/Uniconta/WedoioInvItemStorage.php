<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioInvItemStorage extends WedoioEntity {

  protected $InvItem;

  public function __construct($params = false) {
    parent::__construct("InvItemStorage", $params);
    $this->prepare();
  }

  public function prepare() {
    $item = $this->_Item;
    if ($item) {
      $InvItem = new WedoioInvitem(['_Item' => $item]);
      $this->InvItem = $InvItem;
    }
  }

  public function get_related_product() {

    $variant = false;
    if ($this->_Variant1 ||
      $this->_Variant2 ||
      $this->_Variant3 ||
      $this->_Variant4 ||
      $this->_Variant5) {
      $variant = true;
    }

    $product = $this->InvItem->get_related_product();

    global $storage_matched;
    if (!$storage_matched) $storage_matched = [];

    if ($variant) {
      $p = $product->product;
      $children = $p->get_children();

      // @Todo Cache this so we don't have to load them all the time

      $variation_attributes = WedoioInvVariant::getVariantTaxonomies();

      foreach ($children as $child_id) {
        $variation = wc_get_product($child_id);
        $variation_id = $variation->get_id();

        if (in_array($variation_id, $storage_matched)) continue;
        $attributes = $variation->get_attributes();

        $product_variants = [
          "_Variant1" => null,
          "_Variant2" => null,
          "_Variant3" => null,
          "_Variant4" => null,
          "_Variant5" => null,
        ];

        foreach ($attributes as $taxonomy => &$attribute) {
          $term = get_term_by("slug", $attribute, $taxonomy);
          if ($term) {

            $variant_name = $variation_attributes[$taxonomy];
//            $variant_name = str_replace("pa_invvariant", "_Variant", $taxonomy);
            $key = get_term_meta($term->term_id, "_Variant", true);
            $product_variants[$variant_name] = $key;
          }
        }

        if ($this->_Variant1 == $product_variants['_Variant1'] &&
          $this->_Variant2 == $product_variants['_Variant2'] &&
          $this->_Variant3 == $product_variants['_Variant3'] &&
          $this->_Variant4 == $product_variants['_Variant4'] &&
          $this->_Variant5 == $product_variants['_Variant5']) {

          $product = new WedoioProductVariation($variation->get_id());
          $storage_matched[] = $variation_id;
          return $product;
        }
      }
      return false;
    }

    return $product;
  }

  /**
   * We load the entity from the api
   * @param $params
   */
  public function load($params) {
    if ($this->entity_name) {
      $api = new WedoioApi();
      $entity = $api->fetch($this->entity_name, $params, true);
      // IF there is multiple returned entities then it's not the one we are looking for

      if (!is_array($params)) {
        $id = $params;
        $params = [];
        $params['RowId'] = $id;
      } else {
        $entity = reset($entity);
      }

      if ($entity) {
        if (is_array($entity)) return;

        // Now we set it on the $data field
        $this->data = $entity;
        $this->bootstrap();
      }
    }
  }

  public function process() {

    $product = $this->get_related_product();
    // We decide what to do with the stock

    if ($product->pid) {
      $manageStock = get_option('uniconta_manage_stock');
      $p = $product->product;

      if ($p->is_type("variable")) {
        $manageStock = false;
      }

      if ($manageStock) {
        $mapping['_stock'] = $this->_Qty - $this->_QtyReserved;

        $p->set_stock_quantity($mapping['_stock']);

        if ($mapping['_stock'] !== null) {
          if ($mapping['_stock']) {
            $p->set_stock_status("outofstock");
          } else {
            $p->set_stock_status("instock");
          }
        }

        $p->set_manage_stock("yes");
        $p->save();
      } else {
//        $p->set_manage_stock("no");
//        $p->save();
      }
    }

    return false;
  }

  public function import($entity) {
    parent::import($entity);
    $this->prepare();
  }
}
