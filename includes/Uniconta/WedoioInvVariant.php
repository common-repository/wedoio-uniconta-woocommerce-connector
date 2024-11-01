<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioInvVariant extends WedoioEntity {
  protected $invariant_number;

  public function __construct($invVariant_number = 1, $params = false) {
    parent::__construct("InvVariant" . $invVariant_number, $params);
    $this->invariant_number = $invVariant_number;
  }

  /**
   * Load the InvVariatn by key
   * @param $key
   */
  public function load_by_variant($key) {
    $api = new WedoioApi();
    if ($key) {
//      $invvariants_found = $api->fetch("InvVariant" . $this->invariant_number, ['_Variant' => $key], true);
      $invvariant_found = $this->fetch_invvariant_by_key($this->invariant_number, $key);
//      $invvariant_found = reset($invvariants_found);
      $this->import($invvariant_found);
    }
  }

  public function load_by_variant_name($name) {
    $api = new WedoioApi();
    if ($name) {
//      $invvariants_found = $api->fetch("InvVariant" . $this->invariant_number, ['_Name' => $name], true);
      $invvariant_found = $this->fetch_invvariant_by_name($this->invariant_number, $name);
//      $invvariant_found = reset($invvariants_found);
      $this->import($invvariant_found);
    }
  }

  /**
   * Returns the related term corresponding to this RowId
   */
  public function get_related_term() {
    $term = new WedoioProductVariationAttribute("InvVariant" . $this->invariant_number);
    $term->load_by_rowId($this->RowId);
    return $term;
  }

  /**
   * We Sync the InvVariant and update the terms in woocommerce
   */
  public function sync() {
    $term = $this->get_related_term();
    if (!$this->_Name) return;
    $term->name = $this->_Name;

    if (!$term->term_id) {
      // If there is a no term ID, that means that we are going to create a new Term. So we set the data first
      $term->RowId = $this->RowId;
    } else {
      update_term_meta($term->term_id, "_Variant", $this->_Variant);
    }

    // And Finally we save it
    $saved_term = $term->save();
    $saved_term_tid = $saved_term['term_tid'] ?? false;

    if ($saved_term_tid) {
      update_term_meta($saved_term_tid, "uniconta_key", $this->_Variant);
      update_term_meta($saved_term_tid, "_Variant", $this->_Variant);
    }
  }

  /**
   * If we receive a delete action from uniconta. We need to delete the related term
   */
  public function delete() {
    $term = $this->get_related_term();
    $term->delete();
  }

  /**
   * Return the name of the taxonomy associated to this invariant
   */
  public function get_variant_taxonomy() {
    $variant_taxonomies = self::getVariantTaxonomies();
    $invariant_number = $this->invariant_number;
    $taxonomy = array_search("_Variant" . $invariant_number, $variant_taxonomies);
    return $taxonomy;
  }

  /**
   * @param $taxonomy
   */
  public static function load_by_taxonomy($taxonomy) {
    $variant_taxonomies = self::getVariantTaxonomies();
    $variant = $variant_taxonomies[$taxonomy];
    $invariant = false;
    if ($variant) {
      $variant_number = str_replace("_Variant", "", $variant);
      $invariant = new WedoioInvVariant($variant_number);
    }
    return $invariant;
  }

  /**
   * @return array
   */
  public static function getVariantTaxonomies() {
    $variations_taxonomies = [];
    $company = WedoioApi::getCompany();

    for ($i = 0; $i <= 5; $i++) {
      $variant_name = $company['_Variant' . $i];
      if ($variant_name) {
        $variations_taxonomies["_Variant" . $i] = wc_attribute_taxonomy_name($variant_name);
      }
    }

    $variations_taxonomies_option = get_option("wedoio_variation_taxonomies", []);
    $variations_taxonomies = array_replace($variations_taxonomies, $variations_taxonomies_option);

    $variations_taxonomies = array_flip($variations_taxonomies);

    return $variations_taxonomies;
  }

  public static function get_variant_null_value() {
    $null_variant_value = get_option("uniconta_variant_null_value", '---o---');
    return $null_variant_value;
  }

  function fetch_invvariant_by_key($variant_number, $variant_key) {
    $api = new WedoioApi();
    $invvariants = get_transient('uniconta_invvariants_by_key');
    if (!$invvariants) {
      $invvariants = $this->fetch_invvariants();
      for ($i = 1; $i <= 5; $i++) {
        $invvariantXs = $invvariants[$i];
        $invvariantXArray = array();
        foreach ($invvariantXs as $invvariantX) {
          $invvariantXArray[$invvariantX->_Variant] = $invvariantX;
        }
        $invvariants[$i] = $invvariantXArray;
      }
      set_transient('uniconta_invvariants_by_key', $invvariants, 14400);
    }
    return $invvariants[$variant_number][$variant_key] ?? false;
  }

  function fetch_invvariant_by_name($variant_number, $variant_name) {
    $api = new WedoioApi();
    $invvariants = get_transient('uniconta_invvariants_by_name');
    if (!$invvariants) {
      $invvariants = $this->fetch_invvariants();
      for ($i = 1; $i <= 5; $i++) {
        $invvariantXs = $invvariants[$i];
        $invvariantXArray = array();
        foreach ($invvariantXs as $invvariantX) {
          $invvariantXArray[$invvariantX->_Name] = $invvariantX;
        }
        $invvariants[$i] = $invvariantXArray;
      }
      set_transient('uniconta_invvariants_by_name', $invvariants, 14400);
    }
    return $invvariants[$variant_number][$variant_name] ?? false;
  }

  function fetch_invvariants() {
    $api = new WedoioApi();
    $invvariants = get_transient('uniconta_invvariants');
    if (!$invvariants) {
      $invvariants = array();
      for ($i = 1; $i <= 5; $i++) {
        $invvariantXs = $api->fetch('InvVariant' . $i);
        $invvariants[$i] = $invvariantXs;
      }
      set_transient('uniconta_invvariants', $invvariants, 14400);
    }

    return $invvariants;
  }
}
