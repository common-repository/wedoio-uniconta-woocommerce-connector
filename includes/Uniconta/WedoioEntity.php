<?php
/**
 * Generic class for the WedoioEntities
 */

class WedoioEntity {
  public $entity_name;
  public $RowId;
  public $pid;
  public $data;
  public $user_fields_info;

  protected $product;

  public function __construct($entity_name, $params = false) {
    $this->entity_name = $entity_name;
    if ($params) {
      if (is_array($params)) {
        $this->load($params);
      } else {
        $this->load_by_id($params);
      }
    }
  }

  /**
   * @param $entity_name
   * @param $entity
   */
  public function import($entity) {
    $this->data = $entity;
    $this->bootstrap();
  }

  /**
   * We load the entity from the api
   * @param $params
   */
  public function load($params) {
    if ($this->entity_name) {
      $api = new WedoioApi();
      global $wedoio_cache;
      $cache_key = md5($this->entity_name . "_" . json_encode($params));

      $entity = $wedoio_cache[$cache_key] ?? false;

      if (!$entity) {
        $entity = $api->fetch($this->entity_name, $params);
        $wedoio_cache[$cache_key] = $entity;
      }
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

        // We need to check if the entity returned have the same rowId as the one asked
        foreach ($params as $key => $value) {
          if ($entity->$key != $value) return;
        }

        // Now we set it on the $data field
        $this->data = $entity;
        $this->bootstrap();
      }
    }
  }

  /**
   * We load an item by ID
   * @param $id
   */
  public function load_by_id($id) {
    $this->load($id);
  }

  /**
   * We load the entity by key value
   * @param $key
   * @param $value
   */
  public function load_by_key($key, $value) {
    $this->load([$key => $value]);
  }

  /**
   * We need to prepare the data and set it as an array for easier processing
   */
  public function bootstrap() {
    $data = $this->data;
    $data = json_decode(json_encode($data), true);
    $extract = array();

    if (isset($data['UserFields']) && isset($data['UserField'])) {
      foreach ($data['UserFields'] as $keyIndex => $keyDetails) {
        $keyName = $keyDetails['_Name'];
        $value = isset($data['UserField']['_data'][$keyIndex]) ? $data['UserField']['_data'][$keyIndex] : "";
        $extract[$keyName]['value'] = $value;
        $data[$keyName] = $value;
        $extract[$keyName]['keyInfo'] = $keyDetails;
      }
    }

    $this->RowId = $data['RowId'];
    $this->data = $data;
    $this->user_fields_info = $extract;
  }

  /**
   * Generate an array from the data in this entity. If some keys are provided, only those will be returned
   * @param array $keys
   * @return array
   */
  function to_array($keys = []) {

    $data = $this->data;
    $user_keys = array_keys($this->user_fields_info);

    if (!$keys) {
      $keys = array_keys($data);
      $keys = array_diff($keys, $user_keys);
    }

    $result = [];

    foreach ($keys as $key) {
      $user_key_index = array_search($key, $user_keys);
      if ($user_key_index === false) {
        $result[$key] = $this->$key;
      } else {
        if (!isset($result['UserField']['_data'])) $result['UserField'] = $data['UserField'];
        $result['UserField']['_data'][$user_key_index] = $this->$key;
      }
    }

    return $result;
  }

  /**
   * Generate a json using the keys provided or all the entity
   * @param array $keys
   * @return string
   */
  function to_json($keys = []) {
    return json_encode($this->to_array($keys));
  }

  /**
   * Get the related Product from the system
   * @return WedoioProduct
   */
  function get_related_product() {
//        if($this->product) return $this->product;
    $product = new WedoioProduct();
    $product->load_by_rowid($this->RowId);
    $this->product = $product;
    return $product;
  }

  function __set($name, $value) {
    if (method_exists($this, $name)) {
      $this->$name($value);
    } else if (isset($this->data[$name])) {
      $this->data[$name] = $value;
    } else {
      $this->$name = $value;
    }
  }

  function __get($name) {
    if (method_exists($this, $name)) {
      return $this->$name();
    } elseif (isset($this->data[$name])) {
      return $this->data[$name];
    } elseif (property_exists($this, $name)) {
      return $this->$name;
    }
    return null;
  }
}
