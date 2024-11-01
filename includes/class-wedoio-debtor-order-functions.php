<?php
/**
 * Debtor Orders functions
 */

class Wedoio_DebtorOrder {

  /**
   * Sync an order on Uniconta
   * @param $order
   */
  public static function syncDebtorOrder($order_id, $force = false) {
    $api = new WedoioApi();
    $post = get_post($order_id);

    // If not an order, bye !!
    if ($post->post_type != 'shop_order') {
      return;
    }

    Wedoio_Watchdog::log("DebtorOrder", "Syncing Order $order_id");

    // Now we create the order and sync it
    $order = wc_get_order($order_id);

    if ($order->get_status() == "cancelled") {
      Wedoio_Watchdog::log("DebtorOrder", "Skipping because status is cancelled");
      return;
    }

    $invoices = self::getOrderDebtorInvoices($order_id);
    if ($invoices) {
      Wedoio_Watchdog::log("DebtorOrder", "Skipping because the order have been invoiced in Uniconta");
      return;
    }

    $order_meta = get_post_meta($order_id);
    $orderRowId = get_post_meta($order_id, "_uniconta-rowid", true);
    $is_invoiced = get_post_meta($order_id, "_uniconta-is-invoiced", true);
    $wedoio_lock = get_post_meta($order_id, "_wedoio_lock", true);

    $transient_name = "wedoio-order-sync-" . $order_id;
    $transient = get_transient($transient_name);

    // We are not synchronizing previously synced orders
    $prevent_double_sync = get_option("uniconta_prevent_double_order_sync", false);
    if ($orderRowId && !$force && $prevent_double_sync) {
      Wedoio_Watchdog::log("DebtorOrder", "Order already synced. order RowId : $orderRowId");
      return;
    }

    if (!$wedoio_lock || $force) $wedoio_lock = time() - 1;

    if ($transient) {
      Wedoio_Watchdog::log("DebtorOrder", "Order already in synchronization");
      return;
    } else {
      set_transient($transient_name, true, 60);
    }

//        if (time() < $wedoio_lock) {
//            Wedoio_Watchdog::log("DebtorOrder", "Order already in synchronization");
//            return;
//        }

    update_post_meta($order_id, "_wedoio_lock", time() + 60);

    $use_anonymous = get_option("uniconta_use_anonymous_debtor_for_orders", false);

    if (!$use_anonymous) {
      $customer_id = $order->get_customer_id();
      $user = new WP_User($customer_id);

      //We get the row id for the user
      $rowId = Wedoio_Public::getUserRowId($customer_id);

      if ($rowId) {
        // We get the Debtor on Uniconta
        $account = false;
        $retry = 3;
        while (!$account && $retry >= 0) {
          $debtor = $api->fetchDebtor($rowId);
          $account = $debtor->_Account;
          if (!$debtor) {
            Wedoio_Debtor::syncUser($user);
            $rowId = Wedoio_Public::getUserRowId($customer_id);
            krumo($rowId);
          }
          $retry--;
        }
      } else {

        if ($customer_id) {
          Wedoio_Debtor::syncUser($user);

//            d($user);
          $rowId = Wedoio_Public::getUserRowId($customer_id);

//            d($rowId);
          if (!$rowId) {
            Wedoio_Watchdog::log("DebtorOrder", "Error Syncing the user $customer_id. No RowId found.");
          }
        } else {
          $account = get_option('uniconta_anonymous_account');
        }
      }

    } else {
      $account = get_option('uniconta_anonymous_account');
    }


    if (!$account) {
      update_post_meta($order_id, "_wedoio_lock", time());
      set_transient($transient_name, false);
      Wedoio_Watchdog::log("DebtorOrder", "No account found to create the order.");
      return;
    }

    $country = $order->get_shipping_country();
    $country = $api->getCountry($country);
    $country += 1;

    $currency = $order->get_currency();
    $currencies = $api->getEnum("Currencies");
    $currency_code = array_search($currency, $currencies);

    $debtorOrder_data = [
      "_DCAccount" => $account,
      "_DeliveryName" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
      "_DeliveryAddress1" => $order->get_shipping_address_1(),
      "_DeliveryAddress2" => $order->get_shipping_address_2(),
      "_DeliveryCity" => $order->get_shipping_city(),
      "_DeliveryCountry" => $country,
      "_DeliveryZipCode" => $order->get_shipping_postcode(),
      "_Remark" => $order->get_customer_note(),
      "_OurRef" => $order_id,
      "_Currency" => $currency_code,
      "_DeleteOrder" => 1
//            "OrderTotal" => $order->get_total()
    ];

    $payment_method = isset($order_meta['_payment_method']) ? get_post_meta($order_id, '_payment_method', true) : false;
    $payment_field = esc_attr(get_option('uniconta_' . $payment_method . '_payment'));

    if ($payment_method) {
      switch ($payment_method) {
        case "quickpay":
          $transaction_id = isset($order_meta['_transaction_id']) ? get_post_meta($order_id, "_transaction_id", true) : false;
          if ($transaction_id) $debtorOrder_data['_ReferenceNumber'] = $transaction_id;
          break;
        case "epay_dk":
          $transaction_id = isset($order_meta['_transaction_id']) ? get_post_meta($order_id, "_transaction_id", true) : false;
          if ($transaction_id) $debtorOrder_data['_ReferenceNumber'] = $transaction_id;

          $payment_id = isset($order_meta[Bambora_Online_Classic_Helper::BAMBORA_ONLINE_CLASSIC_PAYMENT_TYPE_ID]) ? get_post_meta($order_id, Bambora_Online_Classic_Helper::BAMBORA_ONLINE_CLASSIC_PAYMENT_TYPE_ID, true) : false;
          if ($payment_id) {
            $payment_card_field = esc_attr(get_option('uniconta_' . $payment_method . '_payment_' . $payment_id));
            if ($payment_card_field) $payment_field = $payment_card_field;
          }
      }
    }

    if ($payment_field) {
      $debtorOrder_data['_Payment'] = $payment_field;
    }

    if ($is_invoiced) {
      update_post_meta($order_id, "_wedoio_lock", time());
      set_transient($transient_name, false);
      Wedoio_Watchdog::log("DebtorOrder", "Skip Syncing the order $order_id because it have been invoiced");
      return;
    }

    $api->send("Company/" . time());
    if ($orderRowId) {
      // We fetch all the debtorOrderlines

      $debtorOrder = $api->send("DebtorOrder/$orderRowId");
      $debtorOrder = json_decode($debtorOrder['body']);
      $orderNumber = $debtorOrder->_OrderNumber;
      if ($orderNumber) {
        $debtorOrderLines = $api->fetch("DebtorOrderLine?_OrderNumber=$orderNumber");

        // And we delete them
        foreach ($debtorOrderLines as $line) {
          $rowId = $line->RowId;
          $api->send("DebtorOrderLine/$rowId", ['method' => "DELETE"]);
        }
      }
      $debtorOrder_data['RowId'] = $orderRowId;
    }

    $debtorOrder_data = apply_filters("wedoio_after_building_debtororder_data", $debtorOrder_data, $order);

    if (!$debtorOrder_data) {
      update_post_meta($order_id, "_wedoio_lock", time());
      set_transient($transient_name, false);
      Wedoio_Watchdog::log("DebtorOrder", "Skip Syncing the order $order_id because data to send is empty.");
      return;
    }

    $debtorOrder = $api->setDebtorOrder($debtorOrder_data);

    if ($orderRowId) {
      $debtorOrder = $api->fetch("DebtorOrder", $orderRowId);
    }

    if (!$debtorOrder) {
      set_transient($transient_name, false);
      update_post_meta($order_id, "_wedoio_lock", time());
      return;
    }

    // We set the RowId on the order
    $orderRowId = $debtorOrder->RowId;
    $orderNumber = $debtorOrder->_OrderNumber;

    update_post_meta($order_id, "_uniconta-rowid", $orderRowId);
    update_post_meta($order_id, "_uniconta-orderNumber", $orderNumber);

    // We get the line items
    $items = $order->get_items();
    $products = array();

    if (!$debtorOrder) return;

    Wedoio_Watchdog::log("DebtorOrder", "Linking Order $order_id with DebtorOrder $orderRowId");

    $variation_attributes = WedoioInvVariant::getVariantTaxonomies();

    foreach ($items as $line_item_id => $item) {

      $product = $item->get_product();
      $_item = $product->get_sku();

      if (!$_item) continue;

      $debtorOrderLine = [
        'debtorOrder' => $debtorOrder->RowId,
        '_Qty' => $item->get_quantity(),
        '_Storage' => "1",
        '_Item' => $_item,
        '_Text' => $product->get_name(),
        '_Price' => ($item->get_total() / $item->get_quantity()),
        '_DoInvoice' => true
      ];

//      $InvItem = new WedoioInvitem($_item);
//      if ($InvItem) {
//        $debtorOrderLine['_Warehouse'] = $InvItem->_Warehouse;
//        $debtorOrderLine['_Location'] = $InvItem->_Location;
//      }

      if ($product->is_type('variation')) {
//                $attributes = $product->get_attributes();
        $meta_data = $item->get_meta_data();

        $attributes = [];
        $variants = [];
        foreach ($meta_data as $meta) {
          $meta->key = rawurldecode((string)$meta->key);
          $meta->value = rawurldecode((string)$meta->value);

          if (isset($variation_attributes[$meta->key])) {
            $variant_key = $variation_attributes[$meta->key];
            $term = get_term_by("slug", $meta->value, $meta->key);
            $term_id = $term->term_id;
            $_Variant = get_term_meta($term_id, "_Variant", true);
            $variants[$variant_key] = $_Variant ?? $term->name;
          }
        }

        $debtorOrderLine = array_merge($debtorOrderLine, $variants);
      }

      $debtorOrderLine = apply_filters("wedoio_after_building_debtororderline_data", $debtorOrderLine, $item, $order);

      if ($debtorOrderLine) {
        $api->setDebtorOrderLine($debtorOrderLine);
      }

    }

    Wedoio_Watchdog::log("DebtorOrder", "Syncing Order $order_id completed");
    do_action("wedoio_after_syncing_debtororder", $order);

    update_post_meta($order_id, "_wedoio_lock", time());
    set_transient($transient_name, false);
  }

  public static function getOrderDebtorInvoices($order_id) {
    $debtorInvoices = get_post_meta($order_id, "_uniconta_debtorinvoices", true);
    if ($debtorInvoices) {
      $debtorInvoices = unserialize($debtorInvoices);
      return $debtorInvoices;
    }

    return false;
  }

  /**
   * We get the numberSerie to find the corresponding Invoices missing and we process them
   */
  public static function processInvoices($start = false, $end = false) {
    $api = new WedoioApi();
    // We get the number Serie 3
    $numberSerieId = get_option("uniconta_invoice_numberserie", false);
    if (!$numberSerieId) return;

    $NumberSerie = $api->fetch("NumberSerie", $numberSerieId);

    $last_invoice_created = $end !== false ? $end : $NumberSerie->_Next - 1;
    $last_invoice_processed = $start !== false ? $start : get_option("uniconta_last_invoice_processed", $last_invoice_created - 1);

    if ($last_invoice_created > $last_invoice_processed) {
      $max_invoices_by_cron = 10;
      $invoices_processed = 0;

      for ($i = $last_invoice_processed + 1; $i <= $last_invoice_created; $i++) {
//                $invoice = new WedoioDebtorInvoice($i);
//                $invoice->set_related_order_to_completed();
        self::processInvoice($i);
        update_option("uniconta_last_invoice_processed", $i);
        $invoices_processed++;
        if ($invoices_processed == $max_invoices_by_cron) break; // We process only [max invoices by cron]
      }
    }
  }

  public static function processInvoice($debtorInvoiceId) {
    $api = new WedoioApi();
    $debtorInvoice = new WedoioDebtorInvoice(['_InvoiceNumber' => $debtorInvoiceId]);
    $debtorInvoiceRowId = $debtorInvoice->RowId;

    $order_id = $debtorInvoice->_OurRef;
    $order = wc_get_order($order_id);

    $m = get_post_meta($order_id);

    if (!$order) return;

    Wedoio_Watchdog::log("DebtorInvoice", "Processing Invoice $debtorInvoiceId");

    $debtorInvoice->set_invoice_data_on_order();
    $processedInvoices = self::getOrderDebtorInvoices($order_id);

    if (!is_array($processedInvoices)) $processedInvoices = [];

    $invoiceData = isset($processedInvoices[$debtorInvoiceId]) ? $processedInvoices[$debtorInvoiceId] : [];

    $defaultData = [
      "invoice" => $debtorInvoice->data,
      "payment_captured" => false,
      "invoice_pdf" => ""
    ];

    $invoiceData = array_merge($defaultData, $invoiceData);

    if(class_exists("WC_QuickPay_API_Payment")){
      if (!$invoiceData['payment_captured']) {
        $quickpay_payment = new WC_QuickPay_API_Payment();
        $transaction_id = get_post_meta($order_id, "_transaction_id", true);
        $amount = $debtorInvoice->_Total;

        try {
          $quickpay_payment->get($transaction_id);
          $can_capture = $quickpay_payment->is_action_allowed("capture");
          $capture = $quickpay_payment->capture($transaction_id, $order, $amount);
          $invoiceData['payment_captured'] = true;
          $invoiceData['capture_data'] = $capture;

          $order->add_order_note(__('QuickPay Payment', 'woo-quickpay') . ': ' . sprintf(__('Payment Captured For Uniconta InvoiceID %s.', 'woo-quickpay'), $debtorInvoiceId));
        } catch (Exception $e) {
//        d($e->getMessage());
        }
      }
    }

    if (!$invoiceData['invoice_pdf']) {
      $debtorInvoiceFile = $api->fetch("DebtorInvoice/$debtorInvoiceRowId/file");
      $file = wp_upload_bits("uniconta-$order_id-invoice-$debtorInvoiceId.pdf", null, base64_decode($debtorInvoiceFile->_Data));

      if (!$file['error']) {
        $invoiceData['invoice_pdf'] = $file['url'];
      }
    }

    $processedInvoices[$debtorInvoiceId] = $invoiceData;
    update_post_meta($order_id, "_uniconta_debtorinvoices", serialize($processedInvoices));

    // We check if the order exists in Uniconta. If it does not it means that it have been completely invoiced
    $orderNumber = $debtorInvoice->_OrderNumber;
    if ($orderNumber) {
      $originalDebtorOrder = $api->fetch("DebtorOrder?_OrderNumber=" . $orderNumber);
      if (!$originalDebtorOrder) {
        // We complete the order itself
        $debtorInvoice->set_related_order_to_completed();
      }
    }
  }

}
