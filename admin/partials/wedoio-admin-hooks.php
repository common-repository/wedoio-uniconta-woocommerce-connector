<?php
/**
 * This page will present the administration for the hooks
 */
?>

<table class="form-table">
    <tr>
        <th scope="row">Hooks</th>
        <td valign="top">

        </td>
    </tr>

    <?php
    $hook_options = [
        __("Inactive"),
        __("Active")
    ];

    $hooks_status = $this->wedoio_hooks_status_check();
    ?>

    <tr valign="top">
        <th scope="row"><?php print __("Debtors") ?></th>
        <td>
            <select
                name="uniconta_hook_debtors"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['Debtor'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("The Debtors Hook is responsible of the update of the users from uniconta when a debtor is modified.") ?>
                </small>
            </p>
        </td>
    </tr>


    <tr valign="top">
        <th scope="row"><?php print __("InvItems") ?></th>
        <td>
            <select
                name="uniconta_hook_invitems"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvItem'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("The Invitem Hook is responsible of the update of the products from uniconta when they are modified.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvItemStorage") ?></th>
        <td>
            <select
                    name="uniconta_hook_invitemstorage"
                    style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvItemStorage'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("The InvitemStorage Hook is responsible of the update of the products stock from uniconta when they are modified.") ?>
                </small>
            </p>
        </td>
    </tr>


    <tr valign="top">
        <th scope="row"><?php print __("NumberSerie") ?></th>
        <td>
            <select
                    name="uniconta_hook_numberserie"
                    style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['NumberSerie'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>

                </small>
            </p>
        </td>
    </tr>


    <tr valign="top">
        <th scope="row"><?php print __("InvVariant1") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariant1"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariant1'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("A hook to catch changes in the Variation Attributes.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvVariant2") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariant2"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariant2'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("A hook to catch changes in the Variation Attributes.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvVariant3") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariant3"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariant3'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("A hook to catch changes in the Variation Attributes.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvVariant4") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariant4"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariant4'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("A hook to catch changes in the Variation Attributes.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvVariant5") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariant5"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariant5'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("A hook to catch changes in the Variation Attributes.") ?>
                </small>
            </p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php print __("InvVariantDetail") ?></th>
        <td>
            <select
                name="uniconta_hook_invvariantdetail"
                style="width: 100px">
                <?php foreach ($hook_options as $id => $name) : ?>
                    <option value=<?php print $id ?> <?php print $hooks_status['InvVariantDetail'] == 1 ? "selected" : "" ?>><?php print $name ?></option>
                <?php endforeach; ?>
            </select>
            <p style="max-width:420px;line-height:1em;">
                <small>
                    <?php print __("This hook is triggered when a modification occurs in uniconta concerning the Item Variations. It will update the original product.") ?>
                </small>
            </p>
        </td>
    </tr>

</table>
