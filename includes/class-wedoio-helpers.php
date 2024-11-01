<?php
/**
 * Wedoio helper functions
 */

class Wedoio_Helper {

  /**
   * Find a Woocommerce product by InvItem RowID
   * @param $rowId
   */
  public static function findProductByRowId($rowId) {
    $args = array(
//            "post_type" => "product",
      "meta_key" => "_uniconta-rowid",
      "meta_value" => $rowId
    );

    $query = new WC_Product_Query($args);

    $products = $query->get_products();
    $product = reset($products);
    if (isset($product->id)) {
      $product->ID = $product->id;
    }

    return $product;
  }

  /**
   * Find a wordpress User by RowID
   * @param $rowId
   * @return mixed
   */
  public static function findUserByRowId($rowId) {
    $users = get_users(
      array(
        "meta_key" => "uniconta-rowid",
        "meta_value" => $rowId
      )
    );
    $user = reset($users);
    return $user;
  }

  /**
   * Find Users by account
   * @param $account
   * @return mixed
   */
  public static function findUserByAccount($account) {
    $users = get_users(
      array(
        "meta_key" => "uniconta-account",
        "meta_value" => $account
      )
    );
    $user = reset($users);

    if (!$user) {
      $api = new WedoioApi();
      $debtors = $api->fetchDebtor(["_Account" => $account]);
      $debtor = reset($debtors);
      if ($debtor) {
        $user = self::findUserByRowId($debtor->RowId);
        if ($user) {
          update_user_meta($user->ID, "uniconta-account", $debtor->_Account);
        }
      }
    }
    return $user;
  }

  /**
   * Get the right extension from a doc type provided
   * @param $type
   */
  public static function getExtension($type) {
    switch ($type) {
      case 0:
        return ".bmp";
      case 1:
        return ".gif";
      case 2:
        return ".ief";
      case 3:
        return ".jpg";
      case 4:
        return ".jfif";
      case 5:
        return ".svg";
      case 6:
        return ".tiff";
      case 7:
        return ".png";
      case 8:
        return ".pdf";
      default:
        return ".png";
    }
  }

  public static function extractFields($fieldKeyName, $fieldKeyValues, $entity) {
    $extract = array();

    if (isset($entity->$fieldKeyName) && isset($entity->$fieldKeyValues)) {
      foreach ($entity->$fieldKeyName as $keyIndex => $keyDetails) {
        $keyName = $keyDetails->_Name;
        $extract[$keyName]['value'] = $entity->$fieldKeyValues->_data[$keyIndex];
        $extract[$keyName]['keyInfo'] = $keyDetails;
      }
    }

    $extractKeyName = $fieldKeyName . "Extract";
    $entity->$extractKeyName = $extract;

    return $entity;
  }
}
