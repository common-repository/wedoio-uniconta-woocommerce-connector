<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioInvVariantDetail extends WedoioEntity {
  protected $invariant_src;
  protected $parent;

  public function __construct($params = false) {
    parent::__construct("InvVariantDetail", $params);
  }

  function get_original_invItem() {
    $api = new WedoioApi();
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
    $product = new WedoioProductVariation();
    $product->load_by_rowid($this->RowId);
    return $product;
  }

  public function sync() {
  }
}
