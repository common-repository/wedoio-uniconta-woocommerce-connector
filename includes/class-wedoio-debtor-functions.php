<?php
/**
 * Debtor entity functions
 */

class Wedoio_Debtor {

  /**
   * Synchronize addresses from a webhook update
   * @param $rowId
   */
  public static function syncWorkInstallationFromRowId($rowId) {
    $api = new WedoioApi();
    $workInstallation = $api->fetch("WorkInstallation", $rowId);
    $delivery_account = $workInstallation->_DCAccount;
    $workInstallations = $api->fetch("WorkInstallation", ["_DCAccount" => $delivery_account]);
    Wedoio_Debtor::syncWorkInstallations($workInstallations);
  }

  /**
   * Synchronize WorkInstallations
   * @param $workInstallations
   * @param array $restrict_users
   */
  public static function syncWorkInstallations($workInstallations, $restrict_users = []) {
    if (!get_option('use_plugin_ext_multiple_addresses', false)) {
      return;
    }

    ini_set('memory_limit', '256M');

    $api = new WedoioApi();

    if (is_object($workInstallations)) {
      $workInstallations = [$workInstallations];
    }

    $users_list = [];
    foreach ($restrict_users as $restrict_user) {
      $users_list[] = $restrict_user->ID;
    }

    $workinstallations_by_user = [];
    foreach ($workInstallations as $workInstallation) {
      $account = $workInstallation->_DCAccount;
      // We get the user with that email
      $user = Wedoio_Helper::findUserByAccount($account);
      if ($user) {
        $uid = $user->ID;
        if (array_search($uid, $users_list) === false) {
          $workinstallations_by_user[$uid][] = $workInstallation;
        }
      }
    }

    $workinstallations_by_user = apply_filters("wedoio_after_build_workinstallation_list_by_user", $workinstallations_by_user, $workInstallations);

    foreach ($workinstallations_by_user as $uid => $userWorkInstallations) {

      $addresses = array();

      foreach ($userWorkInstallations as $workInstallation) {
        $country = $workInstallation->_Country ? $workInstallation->_Country : 56;
        $address = [
          "shipping_first_name" => $workInstallation->_Name,
          "shipping_last_name" => "",
          "shipping_company" => "",
          "shipping_country" => $api->getCountryIso($country - 1),
          "shipping_address_1" => $workInstallation->_Address1,
          "shipping_address_2" => $workInstallation->_Address2,
          "shipping_city" => $workInstallation->_City,
          "shipping_state" => "",
          "shipping_postcode" => $workInstallation->_ZipCode,
        ];

        $addresses[] = $address;
      }

      // Remove the duplicates
      $keys = array();
      foreach ($addresses as $index => $address) {
        $key = md5(implode('_', $address));
        if ($key) {
          $keys[$index] = $key;
        } else {
          unset($addresses[$index]);
        }
      }

      $duplicates = array_diff_assoc($keys, array_unique($keys));
      foreach (array_keys($duplicates) as $index) {
        unset($addresses[$index]);
      }

      if ($addresses && $uid) {
        update_user_meta($uid, 'wc_other_addresses', $addresses);
      }
    }

    do_action("wedoio_after_syncing_workinstallations", $workInstallations);
  }

  /**
   * Synchronize a debtor given a rowId
   * @param $rowId
   */
  public static function syncDebtorFromRowId($rowId, $force = false) {
    $api = new WedoioApi();
    $debtor = $api->fetchDebtor($rowId);
    if ($debtor) {
      Wedoio_Debtor::syncDebtor($debtor, $force);
    }
  }

  /**
   * Sync a debtor from Uniconta
   * @param $debtor
   */
  public static function syncDebtor($debtor, $force = false) {
    global $WedoioSyncLock;
    if ($WedoioSyncLock === true) return;
    $WedoioSyncLock = true;

    $rowId = $debtor->RowId;
    Wedoio_Watchdog::log("Debtor", "Syncing Debtor $rowId");
    $user = Wedoio_Helper::findUserByRowId($rowId);

    if (!$user) {
      // We didn't found the rowID on the system. We check the email
      $contactEmail = $debtor->_ContactEmail ? $debtor->_ContactEmail : $debtor->_InvoiceEmail;
      $user = get_user_by_email($contactEmail);
    } else {
      $uid = $user->ID;
      $transient_key = "user_" . $uid . "_processing";
      $transient = get_transient($transient_key);

      if ($transient) {
        Wedoio_Watchdog::log("Debtor Sync", "The user is still processing. Skipping Debtor Update");
        return;
      }
    }

    $activeDebtor = true;

    $debtorObject = new WedoioEntity("Debtor");
    $debtorObject->import($debtor);

    if (isset($debtorObject->user_fields_info['xDebtorActive'])) {
      // We get the value
      $activeDebtor = $debtorObject->user_fields_info['xDebtorActive']['value'];
    }

    if (!$user) {
      if ($activeDebtor) {
        Wedoio_Debtor::createUserFromDebtor($debtor);
      } else {
        Wedoio_Watchdog::log("Debtor Sync", "User not created because xDebtorActive is false.");
        return;
      }
    } else {
      if (!$activeDebtor) {
        // We block the user
        $roles = $user->roles;
        // We need to check if the user is an administrator
        if (array_search("administrator", $roles) === false) {
          $user->set_role("");
          Wedoio_Watchdog::log("Debtor Sync", "User blocked because xDebtorActive is false.");
          $user->save();
        }
        return;
      } else {
        Wedoio_Debtor::updateUserFromDebtor($debtor, $force);
      }
    }

    do_action("wedoio_after_syncing_debtor", $debtor);
    $WedoioSyncLock = false;
  }

  /**
   * Create a new user from debtor information
   * @param $debtor
   */
  public static function createUserFromDebtor($debtor) {

    $uid = false;
    try {
      $mapping = Wedoio_Debtor::buildDebtorData($debtor);

      $rowId = $debtor->RowId;
      $name = isset($mapping['user_login']) ? $mapping['user_login'] : $debtor->_Name;
      $email = $debtor->_ContactEmail ? $debtor->_ContactEmail : $debtor->_InvoiceEmail;
      $emails = explode(';', $email);
      $email = reset($emails);

      if (!$email) throw new Exception("No Email on Uniconta");
      $uid = username_exists($name);

      if (!$uid) {
        $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
        $uid = wp_create_user($name, $random_password, $email);
      }

      update_user_meta($uid, "uniconta-rowid", $rowId);
      Wedoio_Debtor::updateUserFromDebtor($debtor, true);

    } catch (Exception $e) {
      error_log($e->getMessage());
    }
    return $uid;

  }

  /**
   * Update data for an user from debtor Information
   * @param $debtor
   */
  public static function updateUserFromDebtor($debtor, $force = false) {
    $rowId = $debtor->RowId;
    if (!$rowId) return;

    $account = $debtor->_Account;
    $user = Wedoio_Helper::findUserByRowId($rowId);
    $uid = $user->ID;

    if (!$uid) {
      // We didn't found the rowID on the system. We check the email
      $contactEmail = $debtor->_ContactEmail ? $debtor->_ContactEmail : $debtor->_InvoiceEmail;
      $user = get_user_by_email($contactEmail);
      $uid = $user->ID;
    }

    try {

      // xDebtorActive update.

      $user = Wedoio_Debtor::buildDebtorData($debtor);

      $md5 = md5(json_encode($user));

      $transient_name = "mapping_user_{$uid}";
      $transient = get_transient($transient_name);

//            $current_md5 = get_user_meta($uid, "_uniconta_md5", true);

      if (($transient != $md5) || $force) {
        wp_update_user($user);

        update_user_meta($uid, "uniconta-rowid", $rowId);
        update_user_meta($uid, "uniconta-account", $account);
        update_user_meta($uid, "_uniconta-data", json_encode($debtor));
        update_user_meta($uid, "_uniconta_md5", $md5);

        $u = new WP_User($uid);

//                $debtor_data = Wedoio_Debtor::buildUserData($u);
        $debtor_md5 = md5(json_encode($user));
        update_user_meta($uid, "_uniconta_debtor_md5", $debtor_md5);

        // Replace with update fields
        foreach ($user['_extra']['meta'] as $key => $value) {
          update_user_meta($uid, $key, $value);
        }

        if (isset($user['_extra']['ACF'])) {
          foreach ($user['_extra']['ACF'] as $key => $value) {
            update_field($key, $value, "user_$uid");
          }
        }

        set_transient($transient_name, $md5, 60);
      } else {
        Wedoio_Watchdog::log("Sync User", "Sync of Debtor $rowId (User $uid) skippped because no changes since last sync.");
        return;
      }

      if (get_option('use_plugin_ext_custom_roles', false)) {
        $user = new WP_User($uid);
        $roles = $user->roles;

        $payment = $debtor->_Payment;

        if (array_search("administrator", $roles) === false
          && array_search("shop_manager", $roles) === false
          && array_search("inspector", $roles) === false) {

          // We fetch the payment
          $role = "customer";

          if ($payment) {
            $api = new WedoioApi();
            $paymentTerm = $api->fetch("PaymentTerm", ['_Payment' => $payment]);

            $paymentTerm = reset($paymentTerm);

            if ($paymentTerm->_Days != 0) {
              $role = "credit_customer";
            }
          }

          if ($role) {
            $user->set_role($role);
            Wedoio_Watchdog::log("User roles", "User set to customer");
          }
//                    if ($payment === "Kontant") {
//                            $user->set_role("customer");
//                    } else {
//                        $user->set_role("credit_customer");
//                    }

        }
      } else {
        // If the user have no roles then we set it at least to customer
        Wedoio_Watchdog::log("User roles", "User per default set to customer");
        $user = new WP_User($uid);
        $roles = $user->roles;


        if (!$roles) {
          $user->set_role("customer");
          $user->save();
        }
      }


      // Adding group
      if (class_exists("Groups_Group")) {

        $pricelist = $debtor->_PriceList;

        $uid = $user->ID;
        $groups = self::_get_user_groups($uid);

        if (!in_array($pricelist, $groups)) {
          $group = Groups_Group::read_by_name($pricelist);

          if (!$group) {
            //We create a new one
            $group_id = Groups_Group::create([
              "name" => $pricelist
            ]);
            $group = Groups_Group::read($group_id);
          }

          // We need to remove all other pricelists
          $links = Wedoio_Links::getLinks(GROUP_PRICELIST);
          $pricelist_ids = [];

          foreach ($links as $link) {
            $pricelist_ids[] = $link->wp_id;
          }

          foreach ($groups as $id => $grp) {
            if (array_search($id, $pricelist_ids) !== false) {
              Groups_User_Group::delete($uid, $id);
            }
          }

          $create = Groups_User_Group::create([
            "group_id" => $group->group_id,
            "user_id" => $uid
          ]);
        }
      }

//            Wedoio_Debtor::syncMultipleAddresses($user, $debtor);

      // Sync the priceslists if necessary
//            if (get_option('use_plugin_ext_csp', false)) {
//                // We are getting the prices list
//                $priceslist_id = $debtor->_PriceList;
//                if ($priceslist_id) {
//                    Wedoio_Debtor::syncPriceList($priceslist_id);
//                }
//            }

      // Custom code from tpm is required for it to work properly

    } catch (Exception $e) {
      error_log($e->getMessage());
    }
  }

  /**
   * Build Data array to be sync in the user
   * @param $debtor
   */
  public static function buildDebtorData($debtor) {
    $api = new WedoioApi();
    $rowId = $debtor->RowId;
    $user = Wedoio_Helper::findUserByRowId($rowId);
    $uid = $user->ID;

    $api_debtor = $debtor;
    $debtor = new WedoioEntity("Debtor");
    $debtor->import($api_debtor);

    $name = $debtor->_ContactPerson ? $debtor->_ContactPerson : $debtor->_Name;
    $display_name = $debtor->_Name;
    $names = explode(' ', $name);
    $nickname = $debtor->_Name;
    $last_name = array_pop($names);
    $first_name = implode(" ", $names);
    $description = $debtor->_Text;
    $email = $debtor->_ContactEmail;

    // Gathering the field mapping option
    $mapping = esc_attr(get_option('uniconta_users_field_mapping'));
    $mapping = explode("\n", $mapping);
    $fieldsMapping = array();


    $user_keys = array(
      "first_name", "last_name", "nickname", "description", "user_email", "display_name"
    );

    foreach ($mapping as $line) {
      $explode = explode("|", $line);

      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = $explode[0];
        $wp = $explode[1];
        $fieldsMapping[$uniconta] = $wp;

        $wp_key = trim($wp);
        if (in_array($wp_key, $user_keys)) {

          if (isset($debtor->$uniconta)) {
            $$wp_key = $debtor->$uniconta;
          }
        }
      }
    }

    $user = array(
      "first_name" => $first_name,
      "last_name" => $last_name,
      "nickname" => $nickname,
      "description" => $description,
      "user_email" => $email,
      "display_name" => $display_name
    );

    $user['ID'] = $uid;

    $user['_extra'] = array();


    foreach ($fieldsMapping as $uniconta => $wp) {

      if ($debtor->$uniconta !== null) {
        $value = $debtor->$uniconta;
        $wp = trim($wp);

        if (strpos($uniconta, "Country") && $value) {
          $value = intval($value) - 1;
          $value = $api->getCountryIso($value);
        }

        $user['_extra']['meta'][$wp] = $value;

        if (strpos($wp, "ACF_") === 0) {
          $field_name = str_replace("ACF_", "", $wp);
          if ($value) $user['_extra']['ACF'][$field_name] = $value;
        }
      }
    }

    $email = $user['user_email'];
    $emails = explode(';', $email);
    $user['user_email'] = reset($emails);

    $user = apply_filters("wedoio_after_build_debtor_data", $user, $debtor);

    return $user;
  }

  /**
   * Interface with the plugin multiple addresses
   * @param $user
   */
  public static function syncMultipleAddresses($user, $debtor = null) {
    if (!get_option('use_plugin_ext_multiple_addresses', false)) {
      return;
    }

    $api = new WedoioApi();

    if (!is_object($user)) {
      // Assume it's the uid
      $user = new WP_User($user);
    }

    $uid = $user->ID;

    if (!$debtor) {
      $rowId = get_user_meta($uid, "uniconta-rowid", true);
      $debtor = $api->fetchDebtor($rowId);
    }

    if (!$debtor) return;

    $delivery_account = $debtor->_Account;
    $email = $user->data->user_email;

    $workInstallations = $api->fetch("WorkInstallation", ["_DCAccount" => $delivery_account]);
    Wedoio_Debtor::syncWorkInstallations($workInstallations, [$user]);
  }

  /**
   * Synchronize a pricelist from uniconta
   * @param $priceListID
   */
  public static function syncPriceList($name, $uid = false) {
    $api = new WedoioApi();
    $debtors = $api->fetchDebtor(array(
      "_PriceList" => $name
    ));

    $users = array();
    foreach ($debtors as $debtor) {
      $rowId = $debtor->RowId;
      $user = Wedoio_Helper::findUserByRowId($rowId);
      if ($user) {
        if (!$uid) {
          $users[] = $user->ID;
        } else if ($uid == $user->ID) {
          $users[] = $user->ID;
        }
      }
    }

    // Now we go trough the list and extract the products IDs and the price
    $priceslist = $api->fetchPriceList(array(
      "_PriceList" => $name
    ));

    $prices = array();

    foreach ($priceslist as $product) {
      $item = $product->_Item;
      $price = $product->_Price;
      $qty = $product->_Qty;
      $pct = $product->_Pct;

      $pid = wc_get_product_id_by_sku($item);

      if ($pid) {
        $prices[$pid][] = array(
          "price" => $price,
          "qty" => $qty,
          "pct" => $pct
        );
      }
    }

    // We apply the prices to the database now
    global $wpdb, $cspFunctions;
    global $post, $subruleManager;
    $wusp_pricing_table = $wpdb->prefix . 'wusp_user_pricing_mapping';

    foreach ($users as $uid) {

      $md5 = md5(json_encode($prices));
      $current_md5 = get_user_meta($uid, "_uniconta_price_list_md5", true);
      if ($md5 === $current_md5) continue;
      update_user_meta($uid, "_uniconta_price_list_md5", $md5);

      foreach ($prices as $pid => $pricesDetails) {
        // delete qty prices
        $query = "DELETE FROM $wusp_pricing_table WHERE user_id = %d AND product_id = %d";
        $wpdb->get_results($wpdb->prepare($query, $uid, $pid));

        foreach ($pricesDetails as $priceDetail) {
          $price = $priceDetail['price'];
          $qty = $priceDetail['qty'];
          $pct = $priceDetail['pct'];

          $price_type = $pct == 0 ? 1 : 2;

          $insert = $wpdb->insert($wusp_pricing_table, array(
            'user_id' => $uid,
            'price' => $price,
            'flat_or_discount_price' => $price_type,
            'product_id' => $pid,
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

    return;
  }

  /**
   * Sync a pricelist for an user
   * @param $user
   */
  public static function syncPriceListForUser($user) {
//        $api = new WedoioApi();
//
//        $uid = $user->ID;
//        $rowId = get_user_meta($uid, "uniconta-rowid", true);
//
//        if (get_option('use_plugin_ext_csp', false)) {
//            if ($rowId) {
//                $debtor = $api->fetchDebtor($rowId);
//                if ($debtor) {
//                    $priceList = $debtor->_PriceList;
//                    if ($priceList) {
//                        Wedoio_Debtor::syncPriceList($priceList, $uid);
//                    }
//                }
//            }
//        }
  }

  /**
   * Sync an user from wordpress to uniconta
   * @param $user
   */
  public static function syncUser($user, $force = false) {

    global $WedoioSyncLock;

    $enable_user_sync = get_option("uniconta_enable_user_sync", true);

    if(!$enable_user_sync){
      Wedoio_Watchdog::log("User", "User sync Prevented because disabled in the configuration.");
      return;
    }

    if ($WedoioSyncLock) {
      Wedoio_Watchdog::log("User", "User sync skipped to prevent infinite loops.");
      return;
    }

    $api = new WedoioApi();

    $uid = $user->ID;

    $relatedDebtor = get_user_meta($uid, '_RelatedDebtor', true);

    if (isset($relatedDebtor) && $relatedDebtor) {
      return;
    }

    $transient_key = "user_" . $uid . "_processing";

    $transient = get_transient($transient_key);

    try {

      if ($transient) {
        throw new Exception("User already in processing.");
      }

      set_transient($transient_key, true, 60);

      $user_meta = get_userdata($uid);
      $user_roles = $user_meta->roles;

      if (!$user_roles) {
        throw new Exception("User not synchronized because it does not have any roles set.");
      }

      $email = $user->data->user_email;
      $potential_account = str_pad($uid, 5, "0", STR_PAD_LEFT);
      Wedoio_Watchdog::log("Debtor", "Syncing user $uid");
      $rowId = get_user_meta($uid, "uniconta-rowid", true);

      $data = Wedoio_Debtor::buildUserData($user);

      $md5 = md5(json_encode($data));

      $transient_name = "mapping_debtor_{$rowId}";
      $transient = get_transient($transient_name);

      if ($transient == $md5 && !$force) {
        throw new Exception("Sync of user $uid skippped because no changes since last sync.");
      }

//            $current_md5 = get_user_meta($uid, "_uniconta_debtor_md5", true);

//            if ($current_md5 == $md5 && !$force) {
//                throw new Exception("Sync of user $uid skippped because no changes since last sync.");
//            }

      if ($rowId) {
        // We check if the entity exists
        $debtor = $api->fetchDebtor($rowId);
        if ($debtor) {
          $data['RowId'] = $rowId;
        }
      }

      if (!isset($data['RowId'])) {
        // We look for a debtor with the contact mail to our email
        $debtors = $api->fetchDebtor(["_ContactEmail" => $email]);
        $debtor = reset($debtors);
        if ($debtor) {
          $data['RowId'] = $debtor->RowId;
        }
      }

      if (!isset($data['RowId'])) {
        // We look for a debtor with the invoice mail equal to our email
        $debtors = $api->fetchDebtor(["_InvoiceEmail" => $email]);
        $debtor = reset($debtors);
        if ($debtor) {
          $data['RowId'] = $debtor->RowId;
        }
      }

      if (!isset($data['RowId'])) {
        // We look for a debtor with the invoice mail equal to our email
        $debtors = $api->fetchDebtor(["_Account" => $potential_account]);
        $debtor = reset($debtors);
        if ($debtor) {
          $data['RowId'] = $debtor->RowId;
        }
      }

      $creating = false;

      if (isset($data['RowId'])) {
        $rowId = $data['RowId'];
      } else {
        // We are creating a new Debtor
        set_transient($transient_key . "_creating", true, 60);
        $creating = true;
      }

      if (!isset($data['RowId']) && !isset($data['_Account'])) {
        // We add the account if it's a new Debtor and there is no _Account
        $data['_Account'] = str_pad($uid, 5, "0", STR_PAD_LEFT);
      }

      $data['UserFieldsByKey']['xDebtorActive'] = true;

      $debtor = $api->setDebtor($data);

      if ($creating) {
        $data['RowId'] = $debtor->RowId;
        $d = $api->setDebtor($data);
      }

      Wedoio_Watchdog::log("Debtor", "Data sent : " . json_encode($data));
//            update_user_meta($uid, "_uniconta_debtor_md5", $md5);

      if ($debtor->RowId) {
        $rowId = $debtor->RowId;
      } else {
        $debtor = $api->fetchDebtor($rowId);
      }

      if ($rowId) {
        update_user_meta($uid, "uniconta-rowid", $rowId);
        update_user_meta($uid, "_uniconta-data", json_encode($debtor));
        Wedoio_Watchdog::log("Debtor", "linking user $uid with debtor $rowId");
      } else {
        Wedoio_Watchdog::log("Debtor", "Error while syncing user $uid", "error");
      }

      Wedoio_Watchdog::log("Debtor", "linking user $uid with debtor $debtor->RowId");

//        $user_data = Wedoio_Debtor::buildDebtorData($debtor);
//        $user_md5 = md5(json_encode($user_data));
//        update_user_meta($uid, "_uniconta_md5", $user_md5);
//            $WedoioSyncLock = false;

      set_transient($transient_name, $md5, 300);

      do_action("wedoio_after_syncing_user", $user);
    } catch (Exception $e) {
      Wedoio_Watchdog::log("User Sync", $e->getMessage());
    }

    set_transient($transient_key . "_creating", false, 60);
    set_transient("user_" . $uid . "_processing", false, 60);
  }

  /**
   * Build Data to be sent to wedoio from an user
   * @param $user
   * @return array
   */
  public static function buildUserData($user) {
    $api = new WedoioApi();
    $uid = $user->ID;
    $email = $user->user_email;
    $user_data = get_user_meta($uid);

    $nickname = isset($user_data['nickname'][0]) ? $user_data['nickname'][0] : false;
    $display_name = isset($user->data->display_name) ? $user->data->display_name : false;

    $first_name = $user_data['first_name'][0];
    $last_name = $user_data['last_name'][0];
    $description = $user_data['description'][0];

    $name = $first_name . " " . $last_name;

//        d($name);

    $data = array(
      "_Text" => array($description),
      "_Name" => array($name),
      "_ContactEmail" => array($email),
      "_ContactPerson" => array($name)
    );

    // Gathering the field mapping option
    $mapping = esc_attr(get_option('uniconta_debtors_field_mapping'));
    $mapping = explode("\n", $mapping);
    $fieldsMapping = array();

    $user_keys = array(
      "first_name", "last_name", "nickname", "description", "user_email", "display_name"
    );

    foreach ($_POST as $key => $value) {
      if (!$user->data->$key && $value) {
        $user->data->$key = $value;
      }
    }

    foreach ($mapping as $line) {
      $explode = explode("|", $line);

      if (isset($explode[0]) && isset($explode[1])) {
        $uniconta = $explode[0];
        $uniconta = trim($uniconta);
        $wp = $explode[1];
        $wp = trim($wp);
        $fieldsMapping[$uniconta] = $wp;

        $value = null;
        if (isset($user->data->$wp) && $user->data->$wp) {
          $value = $user->data->$wp;
        } else {
          $value = get_user_meta($uid, $wp, true);
        }

        if (strpos($uniconta, "Country") && $value) {
          $index = $api->getCountry($value);
          if ($index !== false) {
            $value = $index + 1;
          }
        }

        if (strpos($wp, "ACF_") === 0) {
          $field_name = str_replace("ACF_", "", $wp);
          $value = get_field($field_name, "user_$uid");
        }

        if ($value != "") $data[$uniconta][] = $value;
      }
    }

    $curatedData = array();
    foreach ($data as $key => $values) {
      $value = end($values);
      if ($value) {
        $curatedData[$key] = $value;
      }
    }

    $data = $curatedData;

//        $user_roles = $user->roles;
//        $payment_mapping = esc_attr(get_option('uniconta_users_default_payment_terms_mapping'));
//        $payment_mapping = $explode("\n",$payment_mapping);
//        $payment_terms = [];
//        foreach($payment_mapping as $line){
//            $payment_term_mapping = explode("|",$line);
//
//        }

    $rowId = get_user_meta($user->get_id(), "uniconta-rowid", true);
    if (!$rowId) {
      // It's new so we set the payment term
      $default_payment_term = esc_attr(get_option('uniconta_users_default_payment_term'));
      if ($default_payment_term) {
        $data['_Payment'] = $default_payment_term;
      }
    }

    if (!isset($data['_Name'])) $data['_Name'] = $first_name . " " . $last_name;
    if (trim($data['_Name']) == "") $data['_Name'] = $name;

    if (!isset($data['_ContactPerson'])) $data['_ContactPerson'] = $first_name . " " . $last_name;

    $data = apply_filters("wedoio_after_build_user_data", $data, $user);

    return $data;
  }

  public static function _get_user_groups($user_id) {
    global $wpdb;

    $result = [];

    $user_group_table = _groups_get_tablename('user_group');
    $user_group = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $user_group_table WHERE user_id = %d",
      Groups_Utility::id($user_id)
    ));

    if ($user_group !== null) {

      foreach ($user_group as $item) {
        $group_id = $item->group_id;
        $group = Groups_Group::read($group_id);
        $result[$item->group_id] = $group->name;
      }
    }

    return $result;
  }


}
