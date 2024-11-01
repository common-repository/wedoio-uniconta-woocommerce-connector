<?php
/**
 * Class to manage the DebtorOrder
 */


class WedoioDebtorOrder extends WedoioEntity {

  protected $lines;

  public function __construct($params = false) {
    parent::__construct("DebtorOrder", $params);
  }

  /**
   * Return the related order associated with this DebtorOrder
   */
  public function get_related_order() {
    if ($this->RowId) {

    }
  }

  public function bootstrap() {
    parent::bootstrap();

    $api = new WedoioApi();

    if ($this->RowId) {
      $_OrderNumber = $this->_OrderNumber;
      $debtorOrderLines = $api->fetch("DebtorOrderLine?_OrderNumber=$_OrderNumber");

      $lines = [];
      foreach ($debtorOrderLines as $debtorOrderLine) {
        $line = new WedoioDebtorOrderLine();
        $line->load($debtorOrderLine);
        $lines[] = $line;
      }
    }
  }
}
