=== Uniconta WooCommerce Connector ===
Contributors: Wedoio
Tags: Uniconta, Woocommerce, Wedoio, Connector, Integration, Synchronizations
Donate link: https://wedoio.com
Requires at least: 4.6
Tested up to: 5.8
Stable tag: 3.1.10
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Uniconta integration synchronizes your WooCommerce Orders, Customers, Customer specific Pricing, Delivery addresses, stock and Products to your Uniconta accounting system. Uniconta SALES ORDERs can be automatically created. The SALES ORDER and PRODUCT sync features require a license purchase from http://wedoio.com. WooCommerce Uniconta integration plugin connects to license server hosted at http://wedoio.com to check the validity of the license key you type in the settings page.

== Description ==
Wedoio makes it simple to handle and maintain a webshop directly from Uniconta, without any needs of going into WordPress or WooCommerce on a daily basis. All major features are covered in the plugin.
Major features in Wedoio Uniconta wooCommerce connector include:
-    Automatically and/or scheduled synchronizing of debtors/customers, Inventory/Products/ customer Specific Prices*, stock, shipping addresses, Payment Gateway controlled by role directly from Uniconta, Images, title, short and long description are automatically updated from Uniconta to WooCommerce, categories are syncronized for Items and updates automatically product categories for products in WooCommerce.
-    New customers are automatically created in Uniconta
-    New orders are automatically created in Uniconta
-    Dynamically generic mapping of fields with excellent mapping tool in the plugin.
-    We are dedicated and committed to develop our Uniconta WooCommerce Connector to the highest possible level, and are listening to our happy users, therefore we promise that there will be added  a lot more features on a weekly or monthly basis. If you have any needs besides what we offer today, please just reach out, and let's figure out how to solve your needs.

PS: You'll need an wedoio.com API key to use it. Keys are paid subscriptions available for businesses and commercial sites.

CAUTION | DO NOT UPDATE THIS PLUGIN ON PRODUCTION WITHOUT PROPER TESTING IF YOUR VERSION IS UNDER 3.1.x . SOME FEATURES MIGHT BREAK. Contact us at support@wedoio.com for assistance if needed.

== Installation ==
You'll need an wedoio.com API key to use it. Keys are paid subscriptions available for businesses and commercial sites.
Connect to https://wedoio.com

== Changelog ==

= 3.1.10 =
* Preparing for changes pertaining the orders sync
* Test on wordpress 5.8

= 3.1.9 =
* Enforcing woocommerce requirement by preventing plugin activation without woocommerce
* Fixes for the split payment feature
* Fixing the Debtor original mapping for the 2 way sync
* Minor fixes

= 3.1.8 =
* Removing completely access to the crons processes as they cause unwanted effects

= 3.1.7 =
* Adding support for related debtor on user sync

= 3.1.6 =
* Adding a way to enable/disable the user sync from wordpress

= 3.1.5 =
* Updating the latest wordpress tested version

= 3.1.4 =
* Updating the plugin description with Caution

= 3.1.3 =
* Changing the way the stock is managed
* Adding a way for the Orders to be sent to Uniconta using the anonymous account
* CAUTION | DO NOT UPDATE THIS PLUGIN ON PRODUCTION WITHOUT PROPER TESTING. SOME FEATURES MIGHT BREAK

= 3.1.2 =
* Changing the debtor price listg processing to not use the Odata
* Changing the way the Sync Currencies work

= 3.1.1 =
* Minor fixes

= 3.0.12 =
* Using full url in the mapping to fetch the fields

= 3.0.11 =
* Fixing a bug preventing CSP to be activated
* Adding support for new fields weight, width, height, length, etc ...

= 3.0.10 =
* Adding the icons back in the backend
* Restoring the User md5 check to prevent recursive update from uniconta
* Removing the hook response handler for InvItemClient

= 3.0.9 =
* Fixing various warnings by checking variables before using them
* Improving the debtorOrder sync from woocommerce to prevent parallel processing of the same entity

= 3.0.8 =
* Addind a way to control the InvItemStorage hook in the backend

= 3.0.7 =
* Adding the possibility to chose an user_login from mapping for the Debtor
* Minor Fixes

= 3.0.5 =
* Adding backend for InvItemStorage hook

= 3.0.4 =
* Fixes Bambora options

= 3.0.3 =
* Changing the stable version

= 3.0.2 =
* Adding wedoio links include files

= 3.0.1 =
* Adding Default Payment Term for User created from woo
* Adding support for Multi currencies using (Woocommerce-Multilingual)
* Fixing a bug with Bambora credit card linking to payment terms
* Fixing Payment terms config page
* Adding support for the Groups plugin
* Improving DebtorOrder Sync from woocommerce
* Prevent synchronization when Order is cancelled
* Adding support for InvItemStorage in managing the stocks
* Adding soft links in the system
* Improving CSP handling for the prices
* Handling DebtorInvoice Sync
* Implementing soft matching ( No more duplicates when a product with the same sku exists already )

= 2.0.13 =
* Added actions and filters for the variations and images

= 2.0.12 =
* Added a selector for the invoice numberserie
* Removed the files csv input on the mapping
* Added a couple of actions and filters to help the creation of external plugins

= 2.0.11 =
* Fix a bug with the mapping tabs

= 2.0.10 =
* Ajaxify some calls on the main configuration to reduce network interference on page responsiveness
* Fixed an error on the logs page
* Fixed a synchronization problem for the images when the text is not set in Uniconta
* Fixed an issue with the Item master sync where the same item could be synchronized multiple times

= 2.0.9 =
* Added the ability to handle product variations from Uniconta

= 2.0.8 =
* Fix an issue with the log cleaner

= 2.0.7 =
* EULA Checkbox is mandatory before any action on the plugin
* Changing default mapping for the products

= 2.0.6 =
* Applying the fix from SVAR allowing us to put some html directly from Uniconta
* Cleaning the hooks page Adding _DOInvoice statement when creating a debtorOrderLine
* Adding an EULA checkbox on the general page to be accepted before the plugin starts working
* Adding the payment tab feature that allows us to chose a payment account depending on the Payment gateway
* Fixing the retro compatibility with the XWebStatus Field for publishing
* Changing the transaction Id for the transaction Order to the real transaction ID from quickpay
* Adding the transaction ID in the _ReferenceNumber of a DebtorOrder when the payment method is quickpay
* Correcting the Price on the debtorOrderLine when a debtorOrder is created.

= 2.0.5 =
* Disable the cron for the invoices for further testing
* Fixed a bug with the order update

= 2.0.4 =
* Correcting the Price on the debtorOrderLine when a debtorOrder is created.
* Adding the transaction ID in the _ReferenceNumber of a DebtorOrder when the payment method is Quickpay

= 2.0.3 =
* Fixed the DebtorOrder syncing Added a cron for the invoices processing Removed the stock cron for the invitem
* Limit the number of invoices processed by cron and record the last invoice processed directly after processing them instead of waiting that the batch has been completely processed.


= 2.0.2 =
* Fixed a bug with the manual invitem sync
* Changed the way the debtorOrders are synced with Uniconta

= 2.0.1 =
* Minor bug fix

= 2.0.0 =
* New Features added :
* Product Variations Synchronization with the Uniconta Variants
* New Dashboard for managing the hooks from the dashboard
* Improved code for better stability while synchronizing the Invitem
* Fixed a bug where the products with the setting publish set to false where still created

= 1.10.2 =
* Improving Watchdog
* Adding WorkInstallation endpoint processor
* Correcting few bugs in the debtor sync process
* Correcting few bugs in the InvItem sync process
* Improving integration with third party plugins

= 1.10.1 =
* Fixing a problem with the debtorOrder creation

= 1.10 =
* Adding a db watchdog log
