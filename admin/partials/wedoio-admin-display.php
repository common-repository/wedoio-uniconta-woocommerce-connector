<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 * @since      1.0.0
 * @package    Wedoio
 * @subpackage Wedoio/admin/partials
 */
?>

<!--<script crossorigin src="https://unpkg.com/react@16/umd/react.development.js"></script>-->
<!--<script crossorigin src="https://unpkg.com/react-dom@16/umd/react-dom.development.js"></script>-->
<!--<link rel="stylesheet" href="https://unpkg.com/react-select@1.2.1/dist/react-select.css">-->

<?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : "general"; ?>

<div class="wrap">
    <?php settings_errors() ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=wedoio&tab=general"
           class="nav-tab <?php echo $active_tab == "general" ? "nav-tab-active" : "" ?>">General</a>
        <a href="?page=wedoio&tab=mapping"
           class="nav-tab <?php echo $active_tab == "mapping" ? "nav-tab-active" : "" ?>">Mapping</a>
        <a href="?page=wedoio&tab=plugin-extension"
           class="nav-tab <?php echo $active_tab == "plugin-extension" ? "nav-tab-active" : "" ?>">Plugins Extension</a>
<!--        <a href="?page=wedoio&tab=crons"-->
<!--           class="nav-tab --><?php //echo $active_tab == "crons" ? "nav-tab-active" : "" ?><!--">Crons</a>-->
        <a href="?page=wedoio&tab=master_syncs"
           class="nav-tab <?php echo $active_tab == "master_syncs" ? "nav-tab-active" : "" ?>">Master Syncs</a>
        <a href="?page=wedoio&tab=logs"
           class="nav-tab <?php echo $active_tab == "logs" ? "nav-tab-active" : "" ?>">Logs</a>
        <a href="?page=wedoio&tab=hooks"
           class="nav-tab <?php echo $active_tab == "hooks" ? "nav-tab-active" : "" ?>">Hooks</a>
        <a href="?page=wedoio&tab=payment"
           class="nav-tab <?php echo $active_tab == "hooks" ? "nav-tab-active" : "" ?>">Payment</a>
    </h2>

    <form method="post" action="options.php" enctype="multipart/form-data" style="position:relative;">

        <?php //settings_fields($this->plugin_name); ?>
        <?php //do_settings_sections($this->plugin_name); ?>

        <?php if ($active_tab == "general") : ?>
            <?php settings_fields($this->plugin_name . "_general"); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Wedoio token</th>
                    <td><input type="text"
                               name="wedoio_token"
                               value="<?php echo esc_attr(get_option('wedoio_token')); ?>"
                               style="width: 420px"/>
                        <p style="max-width:420px;line-height:1em;">
                            <small>
                                You can get your Wedoio API-Key here: <a href="https://wedoio.com/user/login">GET
                                    KEY</a>
                            </small>
                        </p>
                    </td>
                    <td rowspan="5">
                    </td>
                    <td rowspan="5" style="max-width:400px;vertical-align:top;padding-right:100px;">
                        <div style="margin-bottom:20px;text-align:center;"><img
                                    src="<?php print plugins_url('wedoio-uniconta-woocommerce-connector') ?>/assets/uniconta-logo.png" height="30px"
                                    alt="Home"><img
                                    src="<?php print plugins_url('wedoio-uniconta-woocommerce-connector') ?>/assets/woocommerce-logo.jpg" height="70px"
                                    alt="Home"></div>
                        <p>
                            Manage your WooCommerce web shop directly from your Uniconta.
                            Enter your Wedoio key, Uniconta credentials, and select the company you want to integrate to
                            your WooCommerce Web Shop.
                            We are constantly developing the solution, and are open to any suggestion, that will help
                            you and other users of this solution, to always have the best and most agile
                            Uniconta-WooCommerce solution possible.
                            Let us know if you have any great ideas, perhaps your idea will be the next we develop.
                            If you have any ideas or needs, that would not suit a standard plugin as this,
                            do reach out anyway, and we can discuss a custom fitting of the solution.
                            <br>
                            <br>
                            <b>Thanks for flying with us</b>
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Uniconta Username</th>
                    <td><input type="text"
                               name="uniconta_username"
                               value="<?php echo esc_attr(get_option('uniconta_username')); ?>"
                               style="width: 420px"/>
                        <p style="max-width:420px;line-height:1em;">
                            <small>
                                Use your Uniconta account username
                            </small>
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Uniconta Password</th>
                    <td><input type="password"
                               name="uniconta_password"
                               value="<?php echo esc_attr(get_option('uniconta_password')); ?>"
                               style="width: 420px"/>
                        <p style="max-width:420px;line-height:1em;">
                            <small>
                                Use your Uniconta account password
                            </small>
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Uniconta Company no</th>
                    <td class="organisations-select-wrapper">
                        <?php
                        $uniconta_company = esc_attr(get_option('uniconta_company'));
                        $options = [];
                        $options[""] = "N/A";
                        if($uniconta_company){
                            $options[$uniconta_company] = $uniconta_company;
                        }
                        ?>
                        <select
                                name="uniconta_company"
                                style="width: 420px"
                                class="organisations-select">
                            <?php foreach ($options as $id => $name) : ?>
                                <option value=<?php print $id ?> <?php print $id == $uniconta_company ? "selected" : "" ?>><?php print $name . " ($id) " ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div class="organisations-select-loading" style="display:none"><?php print __("Loading"); ?></div>
                        <div class="organisations-select-error" style="display:none"><?php print __("Error"); ?></div>

                        <p style="max-width:420px;line-height:1em;">
                            <small>
                                If your Uniconta account holds more companies, then select witch company to connect
                            </small>
                        </p>

                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Uniconta Anonymous Debtor Account</th>
                    <td><input type="text"
                               name="uniconta_anonymous_account"
                               value="<?php echo esc_attr(get_option('uniconta_anonymous_account')); ?>"
                               style="width: 420px"/>
                        <p style="max-width:420px;line-height:1em;">
                            <small>
                                Debtor account that will be used to handle Anonymous customers. Orders from Users that
                                do
                                not wish to create their own account in your store, will be booked on the anonymous
                                debtor
                                account in Uniconta
                            </small>
                        </p>

                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Wedoio API Status</th>
                    <td>
                        <?php
                        $status = $this->wedoio_status_check();
                        print $status
                        ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"></th>

                    <?php
                        $eula_accepted = esc_attr(get_option('uniconta_accept_eula'));
                    ?>
                    <td>

                        <input type="checkbox"
                               style="vertical-align: top<?php if($eula_accepted): ?>;display:none;<?php endif; ?>"
                               name="uniconta_accept_eula"
                               value="eula"
                            <?php echo esc_attr(get_option('uniconta_accept_eula')) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;">
                            <small><?php print __("By using this plugin, User have read and accept Wedoio Integration Aps EULA - End User License Agreement. Wedoio Integration recommend you to keep a full backup of all your systems, including Uniconta ERP, in order to prevent loss of Data") ?>
                                <a target="_blank"
                                   href="https://wedoio.net/eula-end-user-license-aregement/"><?php print __("Read more") ?></a>
                            </small>
                        </p>
                    </td>
                </tr>

              <tr valign="top">
                <th scope="row">Prevent syncing already synced orders</th>

                <?php
                $preventDoubleOrderSync = esc_attr(get_option('uniconta_prevent_double_order_sync'));
                ?>
                <td>

                  <input type="checkbox"
                         style="vertical-align: top;"
                         name="uniconta_prevent_double_order_sync"
                         value="uniconta_prevent_double_order_sync"
                    <?php echo esc_attr(get_option('uniconta_prevent_double_order_sync')) ? "checked" : "" ?>
                  />
                </td>
              </tr>

              <tr valign="top">
                <th scope="row">Use anonymous debtor for all orders</th>

                <?php
                $use_anonymous = esc_attr(get_option('uniconta_use_anonymous_debtor_for_orders'));
                ?>
                <td>

                  <input type="checkbox"
                         style="vertical-align: top;"
                         name="uniconta_use_anonymous_debtor_for_orders"
                         value="uniconta_use_anonymous_debtor_for_orders"
                    <?php echo esc_attr(get_option('uniconta_use_anonymous_debtor_for_orders')) ? "checked" : "" ?>
                  />
                </td>
              </tr>

              <tr valign="top">
                <th scope="row">Sync users from wordpress</th>

                <?php
                $user_sync = esc_attr(get_option('uniconta_enable_user_sync'),true);
                ?>
                <td>

                  <input type="checkbox"
                         style="vertical-align: top;"
                         name="uniconta_enable_user_sync"
                         value="uniconta_enable_user_sync"
                    <?php echo esc_attr(get_option('uniconta_enable_user_sync',true)) ? "checked" : "" ?>
                  />
                </td>
              </tr>

            </table>
        <?php endif; ?>

        <?php if ($active_tab == "plugin-extension") : ?>
            <?php settings_fields($this->plugin_name . "_plugin_ext"); ?>
            <h3>Plugin Extension</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Manage Products stock</th>
                    <td><input type="checkbox"
                               style="vertical-align: top"
                               name="uniconta_manage_stock"
                               value="manage_stock"
                            <?php echo esc_attr(get_option('uniconta_manage_stock')) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;">
                            <small><?php print __("When enabled, product stock will be updated in WooCommerce, letting you  show your customers if the product is in stock or not, and also show the Quantity on stock.") ?>
                                <a target="_blank"
                                   href="http://help.wedoio.com/uniconta-woocommerce-plugin/plugin-extentions-tab/manage-products-stock"><?php print __("Read more") ?></a>
                            </small>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Use the plugin CSP</th>
                    <td><input type="checkbox"
                               style="vertical-align: top"
                               name="use_plugin_ext_csp"
                               value="use_csp"
                            <?php echo esc_attr(get_option('use_plugin_ext_csp')) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;">
                            <small><?php print __("With Customer Specific Pricing plugin enabled, wedoio WooCommerce-Uniconta plugin will syncronize the individual prices for each customer in Uniconta.") ?>
                                <a target="_blank"
                                   href="http://help.wedoio.com/uniconta-woocommerce-plugin/plugin-extentions-tab/customer-specific-pricing"><?php print __("Read more") ?></a>
                            </small>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Use the plugin Custom roles</th>
                    <td><input type="checkbox"
                               style="vertical-align: top"
                               name="use_plugin_ext_custom_roles"
                               value="use_custom_roles"
                            <?php echo esc_attr(get_option('use_plugin_ext_custom_roles', false)) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;">
                            <small><?php print __("If the user role is administrator ,shop_manager or inspector it will be skipped, no role will be set. If not, then the _Payment value on the debtor will be checked. If this value is Kontant then the user role will be customer else it will be credit_customer. Make sure that those roles exist before hand using the Custom roles plugin.") ?>
                                <a target="_blank"
                                   href="http://help.wedoio.com/uniconta-woocommerce-plugin/plugin-extentions-tab/woocommerce-user-roles-extention"><?php print __("Read more") ?></a>
                            </small>
                        </p>

                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Use the plugin Multiple addresses</th>
                    <td><input type="checkbox"
                               style="vertical-align: top"
                               name="use_plugin_ext_multiple_addresses"
                               value="use_multiple_addresses"
                            <?php echo esc_attr(get_option('use_plugin_ext_multiple_addresses', false)) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;">
                            <small><?php print __("If the user role is administrator ,shop_manager or inspector it will be skipped, no role will be set. If not, then the _Payment value on the debtor will be checked. If this value is Kontant then the user role will be customer else it will be credit_customer. Make sure that those roles exist before hand using the Custom roles plugin.") ?>
                                <a target="_blank"
                                   href="http://help.wedoio.com/uniconta-woocommerce-plugin/plugin-extentions-tab/multiple-delivery-addresses"><?php print __("Read more") ?></a>
                            </small>
                        </p>

                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?= __("Use multicurrencies")?></th>
                    <td><input type="checkbox"
                               style="vertical-align: top"
                               name="use_plugin_woocommerce_multilingual_currencies"
                               value="use_plugin_woocommerce_multilingual_currencies"
                            <?php echo esc_attr(get_option('use_plugin_woocommerce_multilingual_currencies', false)) ? "checked" : "" ?>
                        />
                        <p style="max-width:420px;line-height:1em;margin-left:10px;padding-top:0;display:inline-block;margin-top:-5px;"></p>

                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == "mapping") : ?>

            <h3 class="nav-tab-wrapper mapping-selector">
                <a href="#"
                   class="nav-tab nav-tab-active"
                   data-target="products-tab"><?php print __("Products", "wedoio") ?></a>
                <a href="#"
                   class="nav-tab"
                   data-target="users-tab"><?php print __("Users", "wedoio") ?></a>
                <a href="#"
                   class="nav-tab"
                   data-target="debtors-tab"><?php print __("Debtors", "wedoio") ?></a>
                <a href="#"
                   class="nav-tab"
                   data-target="categories-tab"><?php print __("Categories", "wedoio") ?></a>
                <a href="#"
                   class="nav-tab"
                   data-target="tags-tab"><?php print __("Tags", "wedoio") ?></a>
            </h3>

            <table class="form-table">
                <?php settings_fields($this->plugin_name . "_mapping"); ?>
                <tr id="products-tab" class="mapping-tab active" valign="top">
                    <td>
                        <div class="root-mapper" data-original=".mapping-products"
                             data-src-wp-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=products"
                             data-src-uniconta-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=invitem">
                            <span class="loading-icon"><i class="icon-refresh"></i></span>
                        </div>
                        <textarea
                                class="mapping-products"
                                rows="15"
                                name="uniconta_products_field_mapping"
                                style="display:none;width: 420px"><?php echo esc_attr(get_option('uniconta_products_field_mapping',
                                '_Qty|stock
xProductName|post_title
xWebStatus|uniconta_publish
xFeatured|uniconta_featured
xShortDescription|post_excerpt
xLongDescription|post_content
xCategory1|product_cat
xCategory2|product_cat
xCategory3|product_cat
xCategory4|product_cat
xCategory5|product_cat
xCategory6|product_cat
Producent|product_cat
price|_SalesPrice1')); ?></textarea>

                        <?php
                        $options = array(
                            0 => "None"
                        );
                        $options += $this->fetch_images_groups();

                        $primary_group = get_option("uniconta_invitem_group_primary", 0);
                        $gallery_group = get_option("uniconta_invitem_group_gallery", 0);

                        ?>
                        <h3><?php print __("Uniconta Product Cover Image Group") ?></h3>
                        <select name="uniconta_invitem_group_primary">
                            <?php foreach ($options as $value => $option) : ?>
                                <option value="<?php print $value ?>" <?php print ($value == $primary_group) ? "selected" : "" ?>><?php print $option ?></option>
                            <?php endforeach; ?>
                        </select>

                        <h3><?php print __("Uniconta Gallery Image Group") ?></h3>
                        <select name="uniconta_invitem_group_gallery">
                            <?php foreach ($options as $value => $option) : ?>
                                <option value="<?php print $value ?>" <?php print ($value == $gallery_group) ? "selected" : "" ?>><?php print $option ?></option>
                            <?php endforeach; ?>
                        </select>


                    </td>
                </tr>

                <tr id="users-tab" class="mapping-tab" valign="top" style="display:none">
                    <td>
                        <div class="root-mapper" data-original=".mapping-users"
                             data-src-wp-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=user"
                             data-src-uniconta-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=debtor">
                            <span class="loading-icon"><i class="icon-refresh"></i></span>
                        </div>

                        <textarea
                                class="mapping-users react-original-mapping"
                                rows="15"
                                name="uniconta_users_field_mapping"
                                style="width: 420px"><?php echo esc_attr(get_option('uniconta_users_field_mapping',
                                'billing_company|_Name
billing_address_1|_Address1
billing_address_2|_Address2
billing_city|_City
billing_phone|_Phone
billing_postcode|_ZipCode
billing_country|_Country
shipping_company|_DeliveryName
shipping_address_1|_DeliveryAddress1
shipping_address_2|_DeliveryAddress2
shipping_city|_DeliveryCity
shipping_postcode|_DeliveryZipCode
shipping_country|_DeliveryCountry
billing_vat|_LegalIdent
_debtor_group|_Group
_debtor_currency|_Currency
_debtor_pricelist|_PriceList
_debtor_layoutgroup|_LayoutGroup
_debtor_payment|_Payment')); ?>
                        </textarea>

                        <h3><?= __("Default Payment Term (ON Create)") ?></h3>

                        <input  type="text"
                                class=""
                                rows="5"
                                name="uniconta_users_default_payment_term"
                                style="width: 420px"
                                value="<?php echo esc_attr(get_option('uniconta_users_default_payment_term')); ?>" />
                        <br>
                        <div style="max-width:420px;line-height:1em;padding-top:0;display:inline-block;margin-top:-5px;">
                            <p>
                                <small>
                                   <?= __("Set The payment terms mapping in the form [user_role]|[payment_term].") ?>
                                </small>
                            </p>
                        </div>

                    </td>
                </tr>

              <tr id="debtors-tab" class="mapping-tab" valign="top" style="display:none">
                <td>
                  <div class="root-mapper" data-original=".mapping-debtors"
                       data-src-wp-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=user"
                       data-src-uniconta-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=debtor">
                    <span class="loading-icon"><i class="icon-refresh"></i></span>
                  </div>

                  <textarea
                    class="mapping-debtors react-original-mapping"
                    rows="15"
                    name="uniconta_debtors_field_mapping"
                    style="width: 420px"><?php echo esc_attr(get_option('uniconta_debtors_field_mapping',
"billing_company|_Name
billing_address_1|_Address1
billing_address_2|_Address2
billing_city|_City
billing_phone|_Phone
billing_postcode|_ZipCode
billing_country|_Country
shipping_company|_DeliveryName
shipping_address_1|_DeliveryAddress1
shipping_address_2|_DeliveryAddress2
shipping_city|_DeliveryCity
shipping_postcode|_DeliveryZipCode
shipping_country|_DeliveryCountry
billing_vat|_LegalIdent
_debtor_group|_Group
_debtor_currency|_Currency
_debtor_pricelist|_PriceList
_debtor_layoutgroup|_LayoutGroup
_debtor_payment|_Payment")); ?>
                        </textarea>

                  <h3><?= __("Default Payment Term (ON Create)") ?></h3>

                  <input  type="text"
                          class=""
                          rows="5"
                          name="uniconta_debtors_default_payment_term"
                          style="width: 420px"
                          value="<?php echo esc_attr(get_option('uniconta_debtors_default_payment_term')); ?>" />
                  <br>
                  <div style="max-width:420px;line-height:1em;padding-top:0;display:inline-block;margin-top:-5px;">
                    <p>
                      <small>
                        <?= __("Set The payment terms mapping in the form [debtor_role]|[payment_term].") ?>
                      </small>
                    </p>
                  </div>

                </td>
              </tr>

                <tr id="categories-tab" class="mapping-tab" valign="top" style="display:none">
                    <td>
                        <div class="react-wrapper">
                            <div class="root-mapper" data-inverse="true" data-multiple-wp="true"
                                 data-original=".mapping-categories"
                                 data-src-wp-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=categories"
                                 data-src-uniconta-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=uniconta-categories">
                                <span class="loading-icon"><i class="icon-refresh"></i></span>
                            </div>

                            <textarea
                                    class="mapping-categories react-original-mapping"
                                    rows="15"
                                    name="uniconta_categories_mapping"
                                    style="width: 420px"><?php echo esc_attr(get_option('uniconta_categories_mapping',
                                    '')); ?></textarea>

                            <?php
                                $options = $this->getUnicontaTablesClient();
                                $categorie_table = esc_attr(get_option('uniconta_categories_table'));
                            ?>

                            <h3><?php print __("Uniconta categories table") ?></h3>
                            <select name="uniconta_categories_table">
                                <?php foreach ($options as $value => $option) : ?>
                                    <option value="<?php print $value ?>" <?php print ($value == $categorie_table) ? "selected" : "" ?>><?php print $option ?></option>
                                <?php endforeach; ?>
                            </select>
                            <br>
                            <br>

<!--                            <h3>Uniconta Categories CSV</h3>-->
<!--                            <input type="file" name="uniconta_categories_csv"/>-->
                            <?php //echo get_option('uniconta_categories_csv'); ?>
                            <?php //echo get_option('uniconta_categories_csv_list'); ?>
                            <!--                            <div class="react-mapping-helper" data-src="wedoio_fetch_fields&entity=categories"-->
                            <!--                                 data-refreshRate="0"></div>-->
                        </div>
                    </td>
                </tr>

                <tr id="tags-tab" class="mapping-tab" valign="top" style="display:none">
                    <td>
                        <div class="react-wrapper">
                            <div class="root-mapper" data-inverse="true" data-multiple-wp="true"
                                 data-original=".mapping-tags"
                                 data-src-wp-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=tags"
                                 data-src-uniconta-url="<?php print site_url() ?>/uniconta-webhook?wedoio-fetch-fields&entity=uniconta-tags">
                                <span class="loading-icon"><i class="icon-refresh"></i></span>
                            </div>

                            <textarea
                                    class="mapping-tags react-original-mapping"
                                    rows="15"
                                    name="uniconta_tags_mapping"
                                    style="width: 420px"><?php echo esc_attr(get_option('uniconta_tags_mapping',
                                    '')); ?></textarea>

                            <?php
                            $options = $this->getUnicontaTablesClient();
                            $tag_table = esc_attr(get_option('uniconta_tags_table'));
                            ?>

                            <h3><?php print __("Uniconta tags table") ?></h3>
                            <select name="uniconta_tags_table">
                                <?php foreach ($options as $value => $option) : ?>
                                    <option value="<?php print $value ?>" <?php print ($value == $tag_table) ? "selected" : "" ?>><?php print $option ?></option>
                                <?php endforeach; ?>
                            </select>

                            <?php //echo get_option('uniconta_categories_csv_list'); ?>
                            <!--                            <div class="react-mapping-helper" data-src="wedoio_fetch_fields&entity=categories"-->
                            <!--                                 data-refreshRate="0"></div>-->
                        </div>
                    </td>
                </tr>

            </table>
        <?php endif; ?>

        <?php if ($active_tab == "api") : ?>

            <div style="display:none">
                <?php
                krumo("init");
                ?>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row">Uniconta Company</th>
                    <td valign="top"><?php print esc_attr(get_option('uniconta_company')); ?></td>
                </tr>
                <tr>
                    <th scope="row">Wedoio API Status</th>
                    <td>
                        <?php
                        $status = $this->wedoio_status_check();
                        print $status
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Api explorator</th>
                    <td valign="top">
                        <ul>
                            <li><input style="min-width:500px" type="text" placeholder="Query" class="api-query"/>
                                <a class="api-send">Send</a>
                            </li>
                            <li>
                                <div class="react-mapping-helper api-displayer api-helper" data-direct="true"
                                     data-src="wedoio_fetch_fields&entity=api"
                                     data-refreshRate="0"></div>
                            </li>
                        </ul>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == "crons") : ?>
            <?php settings_fields($this->plugin_name . "_crons"); ?>

            <?php
            $next_cron_userdocs = wp_next_scheduled('wedoio_cron_userdocs');
            $next_cron_pricelist = wp_next_scheduled('wedoio_cron_pricelist');
            $next_cron_stock = wp_next_scheduled('wedoio_cron_stock');
            $next_cron_invoice = wp_next_scheduled('wedoio_cron_invoice');

            Wedoio_Admin::activate_crons();
            ?>
            <div style="max-width:768px;margin-top:50px;margin-left:50px;">
                <p>
                    Crons synchronize different data between Uniconta and WooCommerce on a schedule. The crons run in
                    different intervals, depending on the severity, and keeps the system's data up to date. You can run a
                    synchronization "on demand" by checking the "Run cron on submit" for the function that you want to
                    use.
                </p>
            </div>
            <table class="form-table">
                <tr>
                    <th scope="row">Product Cover Images and Product Gallery Images Cron</th>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_userdocs_active" <?php print get_option("wedoio_cron_userdocs_active", 1) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_userdocs_active"></label>
                        </div>
                    </td>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_userdocs_execute" <?php print get_option("wedoio_cron_userdocs_execute", 0) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_userdocs_execute">Run cron on submit</label>
                        </div>
                    </td>
                    <td valign="top">
                        <b>Last Execution</b>
                        <?php
                        $last_fetch = get_option("wedoio_cron_userdocs_last_fetch");
                        print $last_fetch ? date("d/m/Y H:i:s", $last_fetch) : "--" ?>
                        / <b>Next
                            Execution</b> <?php print $next_cron_userdocs ? date("d/m/Y H:i:s", $next_cron_userdocs) : "--" ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pricelist</th>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_pricelist_active" <?php print get_option("wedoio_cron_pricelist_active", 1) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_pricelist_active"></label>
                        </div>
                    </td>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_pricelist_execute" <?php print get_option("wedoio_cron_pricelist_execute", 0) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_pricelist_execute">Run cron on submit</label>
                        </div>
                    </td>
                    <td valign="top">
                        <b>Last Execution</b>
                        <?php
                        $last_fetch = get_option("wedoio_cron_pricelist_last_fetch");
                        print $last_fetch ? date("d/m/Y H:i:s", $last_fetch) : "--" ?>
                        / <b>Next
                            Execution</b> <?php print $next_cron_pricelist ? date("d/m/Y H:i:s", $next_cron_pricelist) : "--" ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Product Stock</th>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_stock_active" <?php print get_option("wedoio_cron_stock_active", 1) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_stock_active"></label>
                        </div>
                    </td>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_stock_execute" <?php print get_option("wedoio_cron_stock_execute", 0) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_stock_execute">Run cron on submit</label>
                        </div>
                    </td>
                    <td valign="top">
                        <b>Last Execution</b>
                        <?php
                        $last_fetch = get_option("wedoio_cron_stock_last_fetch");
                        print $last_fetch ? date("d/m/Y H:i:s", $last_fetch) : "--" ?>
                        / <b>Next
                            Execution</b> <?php print $next_cron_stock ? date("d/m/Y H:i:s", $next_cron_stock) : "--" ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php print __("Invoice") ?></th>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_invoice_active" <?php print get_option("wedoio_cron_invoice_active", 1) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_invoice_active"></label>
                        </div>
                    </td>
                    <td valign="top">
                        <div class="checkbox">
                            <input type="checkbox"
                                   name="wedoio_cron_invoice_execute" <?php print get_option("wedoio_cron_invoice_execute", 0) ? "checked" : ""; ?>/>
                            <label for="wedoio_cron_invoice_execute">Run cron on submit</label>
                        </div>
                    </td>
                    <td valign="top">
                        <b>Last Execution</b>
                        <?php
                        $last_fetch = get_option("wedoio_cron_invoice_last_fetch");
                        print $last_fetch ? date("d/m/Y H:i:s", $last_fetch) : "--" ?>
                        / <b>Next
                            Execution</b> <?php print $next_cron_invoice ? date("d/m/Y H:i:s", $next_cron_invoice) : "--" ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php if ($active_tab == "master_syncs") : ?>
            <?php settings_fields($this->plugin_name . "_master_sync"); ?>
            <div style="max-width:768px;margin-top:50px;margin-left:50px;">
                <div></div>
                <h2>Master Synchronization</h2>
                <p>Master Syncronization are mainly used when you start to connect Uniconta and WooCommerce the first
                    time. It will syncronize all the Products or Debtors from Uniconta to WooCommerce. For the Products
                    you can choose to also synchronize product images stored with the products in Uniconta. For more
                    information, have a look at our help pages here: <a
                            href="http://help.wedoio.com/uniconta-woocommerce-plugin/master-syncronization-tab/master-sync">Read More</a></p>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row">Products Master Sync</th>
                    <td valign="top">
                        <?php
                        $id = md5("invitem-batch-cron-".time());
                        WedoioBatchApi::generateButton($id, "invitem", "Product Synchronization");
                        ?>
                    </td>

                    <td>
                        <span class="checkbox" style="margin-left:30px">
                            <input type="checkbox"
                                   name="master_sync_for_images" <?php print get_option("master_sync_for_images", 0) ? "checked" : ""; ?>/>
                            <label for="master_sync_for_images">Synchronize product images</label>
                        </span>
                    </td>
                    <td><p><?php
                            $last_invitem_sync = get_option("last_master_sync_invitem", false);
                            $last_invitem_sync = $last_invitem_sync ? date("d/m/Y H:i:s", $last_invitem_sync) : "n/a";
                            print __("Last Debtor Sync : ") . $last_invitem_sync; ?></p>
                    </td>

                </tr>

                <!--                <tr>-->
                <!--                    <th>Batch Test</th>-->
                <!--                    <td>-->
                <!--                        --><?php
                //                            $id = wp_create_nonce("invitem-batch-" . time());
                //                            WedoioBatchApi::generateButton($id,"test");
                //                            ?>
                <!--                    </td>-->
                <!--                </tr>-->

                <tr>
                    <th>Debtor Master Sync</th>
                    <td>
                        <?php
                        $id = md5("debtor-batch-cron-".time());
                        WedoioBatchApi::generateButton($id, "debtor", "Debtor Synchronization");
                        ?>
                        <div style="max-width:420px;line-height:1em;padding-top:0;display:inline-block;margin-top:-5px;">
                            <p>
                                <small>
                                    <?php print __("Depending of the configuration of your website, this sync can generate emails. Make sure to check your settings before starting the synchronization") ?>                            </small>
                            </p>
                        </div>
                    </td>
                    <td></td>
                    <td><p><?php
                            $last_debtors_sync = get_option("last_master_sync_debtor", false);
                            $last_debtors_sync = $last_debtors_sync ? date("d/m/Y H:i:s", $last_debtors_sync) : "n/a";
                            print __("Last Debtor Sync : ") . $last_debtors_sync; ?></p></td>
                </tr>

            </table>
        <?php endif; ?>

        <?php if ($active_tab == "logs") : ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Logs</th>
                    <td valign="top">
                        <?php
                        $logs = Wedoio_Watchdog::renderLogs();
                        print $logs;?>
                    </td>
                </tr>
            </table>

            <style>
                .wedoio-watchdog-table{
                    margin-bottom:50px;
                }

                .wedoio-watchdog-table th{
                    text-align:center;
                    padding:5px;
                }
            </style>
        <?php endif; ?>

        <?php if ($active_tab == "hooks") : ?>
            <?php settings_fields($this->plugin_name . "_hooks"); ?>
            <?php
                include_once plugin_dir_path(dirname(__FILE__)) .
                    '/partials/' . "wedoio-admin-". $active_tab . '.php';
            ?>
        <?php endif; ?>

        <?php if ($active_tab == "payment") : ?>
            <?php settings_fields($this->plugin_name . "_payment"); ?>
            <?php
            include_once plugin_dir_path(dirname(__FILE__)) .
                '/partials/' . "wedoio-admin-". $active_tab . '.php';
            ?>
        <?php endif; ?>

        <?php submit_button(); ?>

        <a href="https://wedoio.com" style="position: absolute;right: 50px;bottom:50px;">
            <img src="<?php print plugins_url('wedoio-uniconta-woocommerce-connector') ?>/assets/logo.png" alt="Home">
        </a>

    </form>

    <!--    <h2>Custom actions</h2>-->
    <!---->
    <!--    <form action="--><?php //echo admin_url( 'admin-post.php' ); ?><!--">-->
    <!--        --><?php
    //        if(isset($_GET['products_synced'])){
    //            print("Products Synced");
    //        }
    //        ?>
    <!---->
    <!--        <input type="hidden" name="action" value="wedoio_sync_products">-->
    <!--        --><?php //submit_button( 'Sync Products' ); ?>
    <!--    </form>-->

</div>

