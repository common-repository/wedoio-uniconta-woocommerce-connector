<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

define("TABLEID_DEBTOR", 50);
define("TABLEID_INVITEM", 23);
define("TABLEID_INVITEMSTORAGE", 134);
define("TABLEID_CONTACT", 60);
define("TABLEID_EMPLOYEE", 95);
define("TABLEID_INVVARIANT1", 261);
define("TABLEID_INVVARIANT2", 262);
define("TABLEID_INVVARIANT3", 263);
define("TABLEID_INVVARIANT4", 264);
define("TABLEID_INVVARIANT5", 265);
define("TABLEID_INVVARIANTDETAIL", 156);

class WedoioTableChangeEvent extends WedoioEntity {
  protected $hook_type;

  public function __construct($params = false) {
    parent::__construct("TableChangeEvent", $params);
  }

  public function bootstrap() {
    parent::bootstrap();
    // We had the type of hook
    $hook_type = "unknown";
    switch ($this->_TableId) {
      case TABLEID_DEBTOR:
        $hook_type = "Debtor";
        break;
      case TABLEID_INVITEM:
        $hook_type = "InvItem";
        break;
      case TABLEID_INVITEMSTORAGE:
        $hook_type = "InvItemStorage";
        break;
      case TABLEID_INVVARIANT1:
        $hook_type = "InvVariant1";
        break;
      case TABLEID_INVVARIANT2:
        $hook_type = "InvVariant2";
        break;
      case TABLEID_INVVARIANT3:
        $hook_type = "InvVariant3";
        break;
      case TABLEID_INVVARIANT4:
        $hook_type = "InvVariant4";
        break;
      case TABLEID_INVVARIANT5:
        $hook_type = "InvVariant5";
        break;
      case TABLEID_INVVARIANTDETAIL:
        $hook_type = "InvVariantDetail";
        break;
    }

    $this->hook_type = $hook_type;
  }
}
