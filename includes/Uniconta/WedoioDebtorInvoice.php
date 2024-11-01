<?php
/**
 * Class to manage the DebtorInvoices
 */


class WedoioDebtorInvoice extends WedoioEntity {

  public function __construct($params = false) {
    parent::__construct("DebtorInvoice", $params);
  }

  public function set_invoice_data_on_order() {
    $ref = $this->_OurRef;
    if ($ref) {
      $order = wc_get_order($ref);
      if ($order) {
        $_OrderNumber = $this->_OrderNumber;
        $invoice = $this->RowId;

        update_post_meta($order->get_id(), "_uniconta-is-invoiced", true);
        update_post_meta($order->get_id(), "_uniconta-invoicenumber", $this->_Number);
        update_post_meta($order->get_id(), "_uniconta-invoiceid", $this->RowId);
      }
    }
  }

  /**
   * We get the Order with the Id in _ReferenceNumber and we set it as completed
   */
  public function set_related_order_to_completed() {
    $ref = $this->_OurRef;
    if ($ref) {
      $order = wc_get_order($ref);
      if ($order) {
        $_OrderNumber = $this->_OrderNumber;
        $invoice = $this->RowId;

        update_post_meta($order->get_id(), "_uniconta-is-invoiced", true);
        update_post_meta($order->get_id(), "_uniconta-invoicenumber", $this->_Number);
        update_post_meta($order->get_id(), "_uniconta-invoiceid", $this->RowId);

        $order->set_status("completed", __("Set as completed because the DebtorOrder $_OrderNumber has been invoiced ($invoice)"));
        $order->save();
      }
    }
  }
}
