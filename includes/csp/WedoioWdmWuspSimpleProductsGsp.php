<?php

if (defined('CSP_PLUGIN_URL')) {
//    define('CSP_PLUGIN_URL',"");

  if (file_exists(CSP_PLUGIN_URL . '/includes/rules/wdm-csp-rule-group.php')) {

    include_once(CSP_PLUGIN_URL . '/includes/rules/wdm-csp-rule-group.php');

  }

  if (!class_exists('WedoioWdmWuspSimpleProductsGsp')) {
    class WedoioWdmWuspSimpleProductsGsp {

      public $post;

      /**
       * Gets the licensing information from database.
       * If return value is available then:
       * 1: Action for the Group Specific Pricing tab for each product.
       * 2: Action for saving the data of current selection in database.
       */
      public function __construct($post = []) {
        $default_post = [
          "wdm_woo_groupname" => [],
          "wdm_group_price_type" => [],
          "wdm_woo_group_qty" => [],
          "wdm_woo_group_price" => []
        ];

        $this->post = array_merge($default_post, $post);
      }

      //display Groups GUI

      /**
       * Shows Group Specific Pricing tab on Product create/edit page
       *
       * This tab shows options to add price for specific groups
       * while creating a product or editing the product.
       * Check if the groups plugin is active.
       * If yes : Display the GUI for Group Specific Pricing.
       * If no: Display a message to activate the groups plugin.
       */
      public function printGroupTabs() {
        /**
         * Check if Groups is active
         */
        global $cspFunctions;
        if ($cspFunctions->wdmIsActive('groups/groups.php')) {
          ?>
          <h3 class="wdm-heading"><?php _e('Group Based Pricing', CSP_TD) ?></h3>
          <div id="group_specific_pricing_tab_data">
            <!-- <button type="button" class="button" id="wdm_add_new_group_price_pair"><?php //_e('Add New Group-Price Pair', CSP_TD) ?></button> -->
            <div class="options_group wdm_group_pricing_tab_options">
              <table cellpadding="0" cellspacing="0"
                     class="wc-metabox-content wdm_simple_product_gsp_table" style="display: table;">
                <thead class="groupname_price_thead">
                <tr>
                  <th style="text-align: left">
                    <?php _e('Group Name', CSP_TD) ?>
                  </th>
                  <th style="text-align: left">
                    <?php _e('Discount Type', CSP_TD) ?>
                  </th>
                  <th>
                    <?php _e('Min Qty', CSP_TD) ?>
                  </th>
                  <th colspan=3>
                    <?php _e('Value', CSP_TD) ?>
                  </th>
                </tr>
                </thead>
                <tbody id="wdm_group_specific_pricing_tbody"></tbody>
              </table>
            </div>
          </div>
          <?php
        } else {
          ?><h3 class="wdm-heading"><?php _e('Group Based Pricing', CSP_TD) ?></h3>
          <div id="group_specific_pricing_tab_data">
          <?php _e("Activate the 'Groups' Plugin to enjoy the benefits of Group Specific Pricing.", CSP_TD) ?>
          </div><?php
        }
      }

      /**
       * Group Specific Tab Content
       * If the groups plugin is active,
       * Include the template for group specific tab.
       * Shows the tab content i.e. allows admin to add pair and
       * remove group-price pair
       */
      public function groupSpecificPricingTabOptions() {
        global $cspFunctions;
        /**
         * Check if Groups is active
         */
        if ($cspFunctions->wdmIsActive('groups/groups.php')) {
          include(trailingslashit(dirname(dirname(dirname(__FILE__)))) . 'templates/print_group_specific_pricing_tab_content.php');
        }
      }

      /**
       * Process meta
       *
       * Processes the custom tab options when a post is saved
       * If groups plugin is active:
       * Deletes the records which are in DB but not in current selection.
       * Add the group-pricing pairs in database for the current selection.
       * @param int $product_id Product Id.
       */
      public function processGroupPricingPairs($product_id) {
        global $wpdb, $cspFunctions;

        if ($cspFunctions->wdmIsActive('groups/groups.php')) {
          $group_product_table = $wpdb->prefix . 'wusp_group_product_price_mapping';

//                    self::removeGroupProductList($product_id, $group_product_table);

          self::addGroupProductList($product_id, $group_product_table);
        }
      }

      /**
       * Gets the group pricing pairs of current selection.
       * Adds the current selection data in database.
       * Deletes the records from database which are removed from current selection.
       * @param int $product_id Product Id.
       * @param string $group_product_table wusp_group_product_price_mapping
       * @global object $wpdb database object.
       */
      public function addGroupProductList($product_id, $group_product_table) {
        global $wpdb;
        $temp_group_qty_array = array();
        if (isset($this->post['wdm_woo_groupname']) && !empty($this->post['wdm_woo_group_qty'])) {
          //Collect all the updated and newly inserted CSP rules for the product
          $wdmSavedRules = array();

          foreach ($this->post['wdm_woo_groupname'] as $index => $wdm_woo_group_id) {
            $temp_group_qty_array = self::addGroupPriceMappingInDb($product_id, $index, $wdm_woo_group_id, $group_product_table, $temp_group_qty_array);

            $wdmSavedRules[] = new \rules\GroupBasedRule($product_id, $wdm_woo_group_id, $this->post['wdm_group_price_type'][$index], $this->post['wdm_woo_group_qty'][$index], $this->post['wdm_woo_group_price'][$index]);
          }//foreach ends
          do_action('wdm_rules_saved', 'group_specific_product_rules', $wdmSavedRules);
        } else {
          $wpdb->delete(
            $group_product_table,
            array(
              'product_id' => $product_id,
            ),
            array(
              '%d',
            )
          );
        }
      }

      /**
       * Sets the group pricing pairs for the current selection of pricing.
       * Insert, update and delete in database.
       * @param int $product_id Product Id.
       * @param int $index Index of the current selection pair.
       * @param int $group_id Group Id for the selection.
       * @param string $group_product_table wusp_group_product_price_mapping
       * @param array $temp_group_qty_array temporary group quantity array * initially empty
       * @return array $temp_group_qty_array group quantity array.
       */
      public function addGroupPriceMappingInDb($product_id, $index, $group_id, $group_product_table, $temp_group_qty_array) {
        if (isset($group_id)) {
          $groupQtyPair = $group_id . "-" . $this->post['wdm_woo_group_qty'][$index];
          if (!in_array($groupQtyPair, $temp_group_qty_array)) {
            array_push($temp_group_qty_array, $groupQtyPair);
            self::setGroupPricingPairs($group_product_table, $product_id, $group_id, $index);
          }
        }

        return $temp_group_qty_array;
      }

      /**
       * Sets the group-pricing pairs.
       * Insert the group-pricing pairs in database if not already present.
       * If present already update with the new values of current selection.
       * @param int $product_id Product Id.
       * @param int $index Index of the current selection pair.
       * @param int $group_id Group Id for the selection.
       * @param string $group_product_table wusp_group_product_price_mapping
       */
      public function setGroupPricingPairs($group_product_table, $product_id, $group_id, $index) {
        global $wpdb, $subruleManager;
        $qty = $this->post['wdm_woo_group_qty'][$index];
        $pricing = '';

        if (isset($this->post['wdm_woo_group_price'][$index]) && isset($this->post['wdm_group_price_type'][$index]) && isset($qty) && !($qty <= 0)) {
          $pricing = wc_format_decimal($this->post['wdm_woo_group_price'][$index]);
          self::insertGroupPricingPairs($group_product_table, $pricing, $product_id, $group_id, $index, $qty);
        }

        if (empty($pricing)) {
          $wpdb->delete(
            $group_product_table,
            array(
              'group_id' => $group_id,
              'product_id' => $product_id,
              'min_qty' => $qty,
            ),
            array(
              '%d',
              '%d',
              '%d',
            )
          );
          $subruleManager->deactivateSubrulesOfGroupForProduct($product_id, $group_id, $qty);
        }
      }

      /**
       * Checks if there is a record already present in database with same * group-id as the current selection.
       * If yes update the database with the values from the current
       * selection.
       * After updating deactivate the subrules associated with such
       * records.
       * If there isn't any record of same group-id insert it in database.
       * @param string $group_product_table wusp_group_product_price_mapping
       * @param string $pricing sanitized pricing.
       * @param int $product_id Product Id.
       * @param int $index Index of the current selection pair.
       * @param int $group_id Group Id for the selection.
       */
      public function insertGroupPricingPairs($group_product_table, $pricing, $product_id, $group_id, $index, $qty) {
        global $wpdb, $subruleManager;
        $price_type = $this->post['wdm_group_price_type'][$index];
        if (!empty($group_id) && !empty($pricing) && !empty($price_type)) {
          $result = $wpdb->get_results($wpdb->prepare("SELECT id FROM $group_product_table WHERE group_id = '%d' and min_qty = '%d' and product_id=%d", $group_id, $qty, $product_id));
          if (count($result) > 0) {
            $update_status = $wpdb->update($group_product_table, array(
              'group_id' => $group_id,
              'price' => $pricing,
              'flat_or_discount_price' => $price_type,
              'product_id' => $product_id,
              'min_qty' => $qty,
            ), array('group_id' => $group_id, 'product_id' => $product_id, 'min_qty' => $qty));

            if ($update_status) {
              $subruleManager->deactivateSubrulesOfGroupForProduct($product_id, $group_id, $qty);
            }
          } else {
            $wpdb->insert($group_product_table, array(
              'group_id' => $group_id,
              'price' => $pricing,
              'flat_or_discount_price' => $price_type,
              'product_id' => $product_id,
              'min_qty' => $qty,
            ), array(
              '%d',
              '%s',
              '%d',
              '%d',
              '%d',
            ));
          }
        }
      }

      /**
       * Deletes the records which are in DB but not in current selection.
       * Makes the new array of current selection.
       * Fetch records from the database.
       * Delete the records which are in DB but not in current selection.
       * Deletes the subrules associated with such records.
       * @param int $product_id Current Product Id
       * @param string $group_product_table wusp_group_product_price_mapping
       * @global object $wpdb Object responsible for executing db queries
       */
      public function removeGroupProductList($product_id, $group_product_table) {
        global $wpdb;
        global $subruleManager, $cspFunctions;

        $deleteGroups = array();
        $deleteQty = array();
        $deletedValues = array();
        $newArray = array();
        $user_names = '';
        $userType = 'group_id';
        if (isset($this->post['wdm_woo_groupname'])) {
          foreach ($this->post['wdm_woo_groupname'] as $index => $wdmSingleUser) {
            $newArray[] = array(
              'group_id' => $wdmSingleUser,
              'min_qty' => $this->post['wdm_woo_group_qty'][$index]
            );
          }

          $user_names = "('" . implode("','", $this->post['wdm_woo_groupname']) . "')";
          //$qty = "(" . implode(",", $this->post[ 'wdm_woo_group_qty' ]) . ")";

          $existing = $wpdb->get_results($wpdb->prepare("SELECT group_id, min_qty FROM {$group_product_table} WHERE product_id = %d", $product_id), ARRAY_A);

          $deletedValues = $cspFunctions->multiArrayDiff($newArray, $existing, $userType);

          foreach ($deletedValues as $key => $value) {
            $deleteGroups[] = $existing[$key][$userType];
            $deleteQty[] = $existing[$key]['min_qty'];
            unset($value);
          }

          $mapping_count = count($deletedValues);
          if ($mapping_count > 0) {
            foreach ($deleteGroups as $index => $singleGroup) {
              $query = "DELETE FROM $group_product_table WHERE group_id = %d AND min_qty = %d AND product_id = %d";
              $wpdb->get_results($wpdb->prepare($query, $singleGroup, $deleteQty[$index], $product_id));
            }
            $subruleManager->deactivateSubrulesForGroupsNotInArray($product_id, $deleteGroups, $deleteQty);
          }
        }
      }

      /**
       * Removes the record from the 'wusp_group_product_price_mapping' table based
       * product id, group id, min quantity.
       *
       * @param int $productId Product Id.
       * @param int $groupId Group Id.
       * @param int $minQty Minimum qty which is set.
       *
       * @return int|bool Returns the number of rows updated, or false on
       *                  error.
       */
      public static function deleteGroupMapping($productId, $groupId, $minQty) {
        global $wpdb;
        $group_product_table = $wpdb->prefix . 'wusp_group_product_price_mapping';

        return $wpdb->delete(
          $group_product_table,
          array(
            'product_id' => $productId,
            'group_id' => $groupId,
            'min_qty' => $minQty
          ),
          array(
            '%d',
            '%d',
            '%d'
          )
        );
      }

      /**
       * Removes the record from the 'wusp_group_product_price_mapping' table based
       * product id, group id, min quantity.
       *
       * @param int $productId Product Id.
       * @param int $groupId Group Id.
       * @param int $minQty Minimum qty which is set.
       *
       * @return int|bool Returns the number of rows updated, or false on
       *                  error.
       */
      public static function deleteAllGroupMapping($productId, $groupId) {
        global $wpdb;
        $group_product_table = $wpdb->prefix . 'wusp_group_product_price_mapping';

        $delete = $wpdb->delete(
          $group_product_table,
          array(
            'product_id' => $productId,
            'group_id' => $groupId
          ),
          array(
            '%d',
            '%d'
          )
        );
        return $delete;
      }

      /**
       * @param $productId
       */
      public static function fetchGroupMapping($productId, $groupId) {
        global $wpdb;
        $group_product_table = $wpdb->prefix . 'wusp_group_product_price_mapping';

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$group_product_table} WHERE product_id = %d AND group_id = %d", $productId, $groupId), ARRAY_A);
      }
    }
  }

}
