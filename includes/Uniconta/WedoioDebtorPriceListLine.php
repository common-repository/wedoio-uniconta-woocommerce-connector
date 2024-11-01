<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioDebtorPriceListLine extends WedoioEntity {
  protected $product;
  protected $products;
  protected $variations;

  public function __construct($params = false) {
    parent::__construct("InvPriceListLineClient", $params);
    $this->variations = [];
    $this->prepare();
  }

  public function prepare() {

    if (!$this->RowId) return;

    if ($this->_Item) {
      $product = $this->find_product_by_item($this->_Item);
      if ($product) $this->product = $product;
    } else {
      // We find the group of products that are linked to this pricelist
      $query = [];

      if ($this->_ItemGroup) $query['_Group'] = $this->_ItemGroup;
      if ($this->_DiscountGroup) $query['_DiscountGroup'] = $this->_DiscountGroup;

      $api = new WedoioApi();
      $invitems = $api->fetch("InvItem", $query, true);

      $products = [];
      foreach ($invitems as $invitem) {
        $product = $this->find_product_by_item($invitem->_Item);
        $products[] = $product;
      }
      $this->products = $products;
    }
  }

  public function find_product_by_item($_Item) {
    global $wedoioProducts, $wedoioProcessCache;

    if (isset($wedoioProducts[$_Item])) {
      return $wedoioProducts[$_Item];
    } else {
      $wedoioProcessCache = true;
      $product = new WedoioProduct();
      $product->load_by_sku($_Item);
      if (!$product->pid) $product = false;
      $wedoioProducts[$_Item] = $product;
    }

    if (!$product) return false;

    $p = wc_get_product($product->pid);

    if ($p && $p->get_type() == "variable") {
      $variations = [];

      $children = $p->get_children();

      foreach ($children as $child_id) {
        $variation = wc_get_product($child_id);
        $attributes = $variation->get_attributes();

        foreach ($attributes as $taxonomy => &$attribute) {
          $term = get_term_by("slug", $attribute, $taxonomy);
          if ($term) {
            $attributes[$taxonomy] = $term->name;
          }
        }

        $variations[] = [
          "attributes" => $attributes,
          "variation_id" => $variation->get_id()
        ];
      }

      $this->variations = $variations;

      $candidates = [];

      foreach ($this->variations as $variation) {
        $candidates[$variation['variation_id']] = $variation;
      }

      $is_variation = false;

      for ($i = 1; $i <= 5; $i++) {
        $invariant = "_Variant" . $i;

        if ($this->$invariant) {
          $is_variation = true;
          $attribute = $this->$invariant;
          foreach ($candidates as $variation_id => $candidate) {
            if ($candidate['attributes']['pa_invvariant' . $i] != $attribute) {
              unset($candidates[$variation_id]);
            }
          }
        }
      }

      if ($is_variation) {
        $product = false;
      }

      if (count($candidates) == 1) {
        $candidate = reset($candidates);
        $product = new WedoioProductVariation($candidate['variation_id']);
      }
    }

    return $product;
  }

  public function import($entity) {
    parent::import($entity);
    $this->prepare();
  }

  public function sync() {
    if ($this->_Name) {
      $pricelist = new WedoioDebtorPriceList($this->_Name);
      $pid = $this->product->pid;

      $pricelist->apply_price_by_pid($pid);
    }
  }

  public function apply() {
    $qty = $this->_Qty ? $this->_Qty : 1;
    $pct = $this->_Pct;
    $price = $this->_Price;

    if ($this->RowId) {
      $post = [
        "wdm_group_price_type" => 1,
        "wdm_woo_group_qty" => $qty,
        "wdm_woo_group_price" => $price
      ];

      if ($pct) {
        $post["wdm_group_price_type"] = 2;
        $post["wdm_woo_group_price"] = $pct;
      }

      return $post;
    }

    return [];
  }

  public function bootstrap() {
    parent::bootstrap();
  }
}
