<?php
/**
 * Created by PhpStorm.
 * User: gb
 * Date: 1/19/2019
 * Time: 1:35 AM
 */

class WedoioInvItemText extends WedoioEntity {
  protected $parent;

  public function __construct($params = false) {
    parent::__construct("InvItemText", $params);
  }

  public function sync() {

    return false;
  }
}
