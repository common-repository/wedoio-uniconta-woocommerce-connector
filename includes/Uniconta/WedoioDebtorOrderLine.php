<?php
/**
 * Class to manage the DebtorOrderLines
 */


class WedoioDebtorOrderLine extends WedoioEntity {

  public function __construct($params = false) {
    parent::__construct("DebtorOrderLine", $params);
  }
}
