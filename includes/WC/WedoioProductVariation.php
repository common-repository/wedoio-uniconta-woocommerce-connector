<?php
/**
 * Woocommerce product variation
 */

class WedoioProductVariation extends WedoioProduct {

  protected $parent;

  public function __construct($pid = false) {
    $product = false;

    if ($pid) {
      $product = wc_get_product($pid);
    }

    if (!$product) {
      $product = new WC_Product_Variation();
    }

    $this->product = $product;
    $this->pid = $product->get_id();
    $this->rowId = $this->get_rowId();

    if ($this->pid) {
      $this->get_parent();
    }
  }

  /**
   * Return an Uniconta Variation Array
   */
  public function get_uniconta_variant_attributes() {
    $variation_array = [
      '_Variant1' => NULL,
      '_Variant2' => NULL,
      '_Variant3' => NULL,
      '_Variant4' => NULL,
      '_Variant5' => NULL,
    ];

    $variations_taxonomies = WedoioInvVariant::getVariantTaxonomies();

    foreach ($variations_taxonomies as $taxonomy => $variant) {
      $attribute = $this->product->get_attribute($taxonomy);
      if ($attribute) {
        $variation_array[$variant] = $attribute;
      }
    }

    return $variation_array;
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
      $product = new WC_Product_Variation();
    }

    $this->product = $product;
    $this->get_parent();
  }

  public function set_parent_id($pid) {
    $this->product->set_parent_id($pid);
  }

  public function get_parent() {
    $parent_pid = $this->product->get_parent_id();
    $parent = new WedoioProduct($parent_pid);
    $this->parent = $parent;
    return $parent;
  }

  /**
   * Delete the product
   */
  public function delete() {
    $this->product->delete();
    // We trigger the parent product sync
    $this->parent->sync();
  }

}
