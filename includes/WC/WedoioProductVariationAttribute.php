<?php
/**
 * Manage the product variation Attributes
 */

class WedoioProductVariationAttribute {

  protected $variant_name;
  protected $taxonomy;
  protected $_Variant;
  protected $term;
  protected $name;
  protected $RowId;
  protected $term_id;

  function __construct($variant_name, $term_id = false) {
    $this->variant_name = $variant_name;
    $this->check_taxonomy();

    $term = false;

    if ($term_id) {
      $term = get_term($term_id);
      $this->RowId = $this->get_rowId();
    }

    $this->term = $term;
    $this->bootstrap();
  }

  function bootstrap() {
    if ($this->term) {
      $this->term_id = $this->term->term_id;
      $this->name = $this->term->name;
      $this->RowId = $this->get_rowId();
    }
  }

  /**
   * Earl check if the taxonomy have already been created or not
   */
  function check_taxonomy() {
    $variant_name = $this->variant_name;
    $variant_taxonomies = WedoioInvVariant::getVariantTaxonomies();
    $variant_taxonomies = array_flip($variant_taxonomies);

    $variations_names = [
      "InvVariant1" => $variant_taxonomies['_Variant1'] ? $variant_taxonomies['_Variant1'] : false,
      "InvVariant2" => $variant_taxonomies['_Variant2'] ? $variant_taxonomies['_Variant2'] : false,
      "InvVariant3" => $variant_taxonomies['_Variant3'] ? $variant_taxonomies['_Variant3'] : false,
      "InvVariant5" => $variant_taxonomies['_Variant5'] ? $variant_taxonomies['_Variant5'] : false,
      "InvVariant4" => $variant_taxonomies['_Variant4'] ? $variant_taxonomies['_Variant4'] : false
    ];
    $taxonomy = $variations_names[$variant_name];
    if (!taxonomy_exists($taxonomy)) {
      $taxonomy = false;
    }


    if (!$taxonomy) {
      $company = WedoioApi::getCompany();
      $variant_number = str_replace("InvVariant", "", $variant_name);
      $variant_name = $company['_Variant' . $variant_number] ? $company['_Variant' . $variant_number] : "InvVariant" . $variant_number;
      if (!wc_attribute_taxonomy_id_by_name($variant_name)) {
        wc_create_attribute(['name' => $variant_name]);
      }
      $taxonomy = wc_attribute_taxonomy_name($variant_name);
    }

    $this->taxonomy = $taxonomy;
  }

  /**
   * Get the related InvVariant
   * @return WedoioInvVariant
   */
  function get_related_InvVariant() {
    $RowId = $this->get_rowId();
    $variant_number = str_replace("InvVariant", "", $this->variant_name);
    $invVariant = new WedoioInvVariant($variant_number, $RowId);
    return $invVariant;
  }

  /**
   * Helper function to get the term knowing the term_id
   * @param $term_id
   * @return array|null|WP_Error|WP_Term
   */
  function get_term($term_id) {
    $term = get_term($term_id);
    return $term;
  }

  /**
   * Get related rowId
   * @return mixed
   */
  function get_rowId() {
    return get_term_meta($this->term_id, "_uniconta-rowid", true);
  }

  function load_by_term_id($term_id) {
    $term = $this->get_term($term_id);
    $this->term = $term;

    $this->bootstrap();
  }

  /**
   * Load a term knowing it's uniconta rowId
   * @param $rowid
   */
  function load_by_rowId($rowid) {
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

    $terms = array();

    $terms_found = $query->get_terms();

    foreach ($terms_found as $term_id) {
      $terms[] = $this->get_term($term_id);
      break;
    }

    $term = reset($terms);
    $this->term = $term;
    $this->bootstrap();
  }

  /**
   * Save a term
   */
  function save() {
    $name = $this->name;
    $term = $this->term;

    if ($name) {
      if ($term) {
        // We update the term with the name.
        if ($name != $term->name) {
          $term = wp_update_term($this->term_id, $this->taxonomy, ['name' => $name]);
        }
      } else {
        // We try to load the term using the name
        $term = get_term_by("name", $name, $this->taxonomy);
        if (!$term) {
          $term = wp_insert_term($name, $this->taxonomy);

          if (!is_wp_error($term)) {
            $term_id = $term['term_id'];
            $term = get_term($term_id);
          } else {
            $term = false;
          }
        }
      }

      $this->term = $term;

      if ($term) {
        $this->term_id = $term->term_id;
        if ($this->RowId) {
          update_term_meta($this->term_id, "_uniconta-rowid", $this->RowId);
        }
      } else {
//        d($term->get_error_message());
      }
    }
  }

  /**
   * Delete a term
   */
  function delete() {
    $term_id = $this->term_id;
    if ($term_id) {
      wp_delete_term($this->term_id, $this->taxonomy);
    }
  }

  /**
   * Helper for the queries
   * @param array $args
   */
  function query($args = []) {
    $query_args = array(
      'fields' => 'ids',
      'hide_empty' => false,
      'taxonomy' => $this->taxonomy,
      'meta_query' => array(),
    );

    $query_args = array_replace($query_args, $args);

    return new WP_Term_Query($query_args);
  }

  /**
   * We sync an attribute from Uniconta. If the corresponding InvVariant does not exist, we delete the term
   */
  function sync() {
    $invVariant = $this->get_related_InvVariant();

    if (isset($invVariant->_Name)) {
      $name = $invVariant->_Name;
      $this->name = $name;
      $this->_Variant = $invVariant->_Variant;
      $this->save();
    } else {
      // Means the inVariant didn't load. If it is not and a term exists actually. It means the InvVariant is deleted and so will be the Term
      $this->delete();
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
}
