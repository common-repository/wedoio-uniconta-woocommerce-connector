<?php
/**
 * Bootstrap class for the Uniconta entity Invitem
 */

class WedoioInvitem extends WedoioEntity {
  public $attachments;
  public $variants;

  public function __construct($params = false) {
    parent::__construct("InvItem", $params);
  }

  /**
   * Fetch attachments for the invitem
   */
  public function fetch_attachments() {
    $api = new WedoioApi();
    ini_set('memory_limit', '256M');
    $attachments = $api->fetch("UserDocsClient/0/file", array(
      "_TableRowId" => $this->RowId
    ));

    $this->attachments = [];

    foreach ($attachments as $attachment) {
      if ($attachment->_TableRowId == $this->RowId) {
        $this->attachments[] = $attachment;
      }
    }

    return $attachments;
  }

  public function fetch_variants() {
    $api = new WedoioApi();

    $variants = $api->fetch("InvVariantCombi", [
      "_Item" => $this->_Item
    ]);
    $this->variants = $variants;
    return $variants;
  }

  public function mapping() {
    $invitem = $this;

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

    return $mapping;
  }

  /**
   * Get the related Product from the system
   * @return WedoioProduct
   */
  function get_related_product() {
    if ($this->product) {
      $pid = $this->product->pid;
      $this->product = new WedoioProduct($pid);
      return $this->product;
    }
    $product = new WedoioProduct();
    $product->load_by_rowid($this->RowId);
    $this->product = $product;
    return $product;
  }
}
