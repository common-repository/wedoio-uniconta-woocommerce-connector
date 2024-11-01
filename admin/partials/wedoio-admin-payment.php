<?php
/**
 * This page will present the administration for the hooks
 */
?>
<table class="form-table">
  <tr>
    <th scope="row">Payment</th>
    <td valign="top">
    </td>
  </tr>
  <?php
  $api = new WedoioApi();
  $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
  $all_gateways = $available_gateways;
  //        d($available_gateways);
  $available_gateways = array_keys($available_gateways);
  //
  //    $accounts = $api->fetch("GLAccount?_AccountType=10..17");
  //    d($accounts);
  //    $payments = $api->fetch("PaymentTerm");
  //    d($payments);
  $payment_options = $this->getPaymentTerms();
  ?>
  <tr valign="top">
    <th scope="row"><?php print __("Uniconta Invoice Number serie") ?></th>
    <td>
      <?php
      $uniconta_invoice_numberserie = esc_attr(get_option('uniconta_invoice_numberserie'));
      $options = $this->getNumberseries();
      ?>
      <select
        name="uniconta_invoice_numberserie"
        style="width: 200px">
        <?php foreach ($options as $id => $name) : ?>
          <option
            value="<?php print $id ?>" <?php print $id == $uniconta_invoice_numberserie ? "selected" : "" ?>><?php print $name . " ($id) " ?></option>
        <?php endforeach; ?>
      </select>
    </td>
  </tr>
  <?php foreach ($available_gateways as $gateway): ?>
    <tr valign="top">
      <th scope="row"><?php print $all_gateways[$gateway]->title ?></th>
      <td>
        <?php
        $payment = esc_attr(get_option('uniconta_' . $gateway . '_payment'));
        ?>
        <select
          name="uniconta_<?php print $gateway ?>_payment"
          style="width: 200px">
          <?php foreach ($payment_options as $id => $name) : ?>
            <option
              value="<?php print $id ?>" <?php print $id == $payment ? "selected" : "" ?>><?php print $name . " ($id) " ?></option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>
    <?php if ($gateway == 'epay_dk') : ?>
      <?php for ($i = 1; $i <= 32; $i++) : ?>
        <?php $cardname = Bambora_Online_Classic_Helper::get_card_name_by_id($i); ?>
        <tr valign="top">
          <th scope="row"><?php print $all_gateways[$gateway]->title . " ($cardname)" ?></th>
          <td>
            <?php
            $payment = esc_attr(get_option('uniconta_' . $gateway . '_payment_' . $i));
            ?>
            <select
              name="uniconta_<?php print $gateway ?>_payment_<?php print $i ?>"
              style="width: 200px">
              <?php foreach ($payment_options as $id => $name) : ?>
                <option
                  value="<?php print $id ?>" <?php print $id == $payment ? "selected" : "" ?>><?php print $name . " ($id) " ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      <?php endfor ?>
    <?php endif; ?>
  <?php endforeach; ?>
</table>
