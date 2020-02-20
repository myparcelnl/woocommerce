=== Plugin Name ===
Tags: woocommerce, WooCommerce, export, Orders, orders, Bestellingen, bestellingen, Delivery, delivery options, bezorgopties, Packages, packages, PostNL, postnl, PostNL
Requires at least: 3.5.1 & WooCommerce 2.0+
Tested up to: 5.2.0
Stable tag: trunk
Requires PHP: 5.4
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

Export your WooCommerce orders to PostNL (www.postnl.nl) and print labels directly from the WooCommerce admin

== Description ==

This WooCommerce extension allows you to export your orders to the PostNL service (www.postnl.nl).

**Online Manual (in Dutch):** https://postnl.github.io/woocommerce/

= Main features =
- Delivery options integrated in your checkout
- Export your WooCommerce orders to PostNL with a simple click, single orders or in batch
- Print shipping labels directly (PDF)
- Create multiple shipments for the same order
- Choose your package type (Parcel, mailbox package or unpaid letter)
- Define preset PostNL shipping options (signature required, extra insurance, etc.)
- Modify the PostNL shipping options per order before exporting
- Extra checkout fields to separate street name, house number and house number suffix for more precise address data
- View the status of the shipment in the order details page
- Add track&trace link to the order confirmation email

A PostNL API account is required for this plugin! Contact your PostNL account manager for the API key.

== Installation ==

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install of WooCommerce PostNL, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WooCommerce PostNL" and click Search Plugins. You can install it by simply clicking Install Now. After clicking that link you will be asked if you're sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

= Manual installation via the WordPress interface =
1. Download the plugin zip file to your computer
2. Go to the WordPress admin panel menu Plugins > Add New
3. Choose upload
4. Upload the plugin zip file, the plugin will now be installed
5. After installation has finished, click the 'activate plugin' link

= Manual installation via FTP =
1. Download the plugin file to your computer and unzip it.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

= Setting up the plugin =
1. Go to the menu `WooCommerce > PostNL`.
2. Fill in your API Details.
3. Under 'Default export settings' you can set options that should be set by default for the export. You can change these settings per order at the time of export.
4. The plugin is ready to be used!

= Testing =
We advise you to test the whole checkout procedure once to see if everything works as it should. Pay special attention to the following:

The PostNL plugin adds extra fields to the checkout of your webshop, to make it possible for the client to add street name, number and optional additions separately. This way you can be sure that everything is entered correctly. Because not all checkouts are configured alike, it's possible that the positioning/alignment of these extra fields have to be adjusted.

Moreover, after a label is created, a track&trace code is added to the order. When the order is completed from WooCommerce, this track & trace code is added to the email (when this is enabled in the settings). Check that the code is correctly displayed in your template. You can read how to change the text in the FAQ section.

== Frequently Asked Questions ==

**Online Manual (in Dutch):** https://postnl.github.io/woocommerce/

= How do I get an API key? =
For the api key, you must be contacting PostNL.

= How do I change the track&trace email text? =
You can change the text (which is placed above the order details table by default) by applying the following filter:
`
add_filter( 'wcpostnl_email_text', 'wcpostnl_new_email_text' );
function wcpostnl_new_email_text($track_trace_tekst) {
	// Tutoyeren ipv vousvoyeren
	$nieuwe_tekst = 'Je kunt je bestelling volgen met het volgende PostNL track&trace nummer:';
	return $nieuwe_tekst;
}
`

== Screenshots ==

1. Export or print postnl label per order
2. Bulk export or print postnl labels
3. Change the shipment options for an order
4. postnl actions on the order overview page
5. postnl information on the order details page

== Changelog ==

= 3.1.7 (20-02-2020) =
* Fix: Outdated barcode

= 3.1.6 (2019-07-04) =
* Fix: Trying to get property of non-object
* Fix: Shipment validation error (PakjeGemak)
* Fix: Set default to 0 if there's no tax rate set up
* Fix: Issue with shipping class term id
* Fix: Shipping method issue with pickup
* Fix: Saturday cutoff time
* Fix: Use constants for delivery_type
* Improvement: Barcode in order grid
* Improvement: Cut the product title after 50 characters
* Improvement: Show deliveryday only for NL shipments

= 3.1.3 (2019-02-26) =
* Fix: Showing delivery date in the order when consumer using safari
* Fix: Scrolling of the order overview when an input is clicked.

= 3.1.2 (2019-02-19) =
* Improvement: 18+ check
* Fix: Standard delivery text
* Fix: showing checkout

= 3.1.1 (2019-01-30) =
* Fix: Remove some styling code
* Fix: Text changes
* Fix: Hide delivery options 
* Fix: Get the total weight on a later moment
* Fix: Unset weight by mailbox package 
* Fix: Since WooCommerce 3.0, logging can be grouped by context (error code 0 when exporting / printing)
* Fix: The checkout is still loading when change the country.

* Improvement: Add maxlength to number suffix field
* Improvement: Translate all text inside the checkout
* Improvement: The option to give a discount on the shipping method ( negative amounts)

= 3.1.0 (2018-12-12) =
* Hotfix: Show delivery options when checkout form already filled in.

= 3.0.10 (2018-12-05) =
* Hotfix: Flashing of the order summary.

= 3.0.9 (2018-12-04) =
* Hotfix: Get mailbox delivery option and save it into the order.

= 3.0.8 (2018-12-04) =
* Fix: The multiple calls that are made to retrieve the shipping data.
* Fix: The option for Pick up extra early
* Fix: Wrong house number / postcode message and the possibility to adjust the address in the postnl checkout
* Fix: Woocommerce tabel rates
* Improvement: Better support the default WooCommerce checkout address fields

= 3.0.7 (2018-11-20) =
* Fix: Set default values for dropoff days and delivery days window

= 3.0.6 (2018-11-16) =
* Fix: Remove concatenation from constant (causes an error on php version < 5.6)
* Fix: No more double address fields with delivery options disabled

= 3.0.5 (2018-11-15) =
* Fix: Error message about money_format
* Fix: Add the priority to the checkout field for support WooCommerce 3.5.1
* Fix: The PostNL logo is not visible with all browsers
* Improvement: Support Channel Engine
* Improvement: Information inside the checkout and the translations
* Improvement: Support WooCommerce default shipping fields (_address_1 and _address_2)

= 3.0.4 (2018-10-23) =
* Fix: mollie payments
* Improvement: Check for minimum php version (php 5.4) 
* Improvement: Hide automatic pickup express if pickup is not enabled

= 3.0.3 (2018-10-09) =
* Fix: Problem with WooCommerce PDF Invoices & Packing Slips
* Fix: error about "Bewaar barcode in een notitie" size
* Fix: Turn of the option allow Pickup Express
* Fix: Save settings with a new update
* Improvement: PostNL delivery header titel
* Improvement: Support WooCommerce 3.5.0
* Improvement: add preliminary support for "digitale postzegel"

= 3.0.2 (2018-10-09) =
* Fix: Error a non-numeric value encountered in class-wcmp-frontend-settings.php
* Fix: Notice Undefined index: checkout_position
* Fix: Add version number after the nl-checkout.css call

= 3.0.0 (2018-10-09) =
* Changes: The whole checkout has a new look. A choice has been made to go back to the basic checkout. The checkout is designed so that he will take the styling of the website.

These are the biggest changes:
* No use of libraries (only jQuery)
* No iframe is used
* The checkout is more stable
* Easier to implement improvements

* Fix: Use street and house number fields for export a Belgium order
* Fix: The at home or at work delivery title inside the checkout
* Fix: The default settings
* Improvement: The option to change the position of the checkout (edited)

= 3.0.0-beta.2 (2018-09-08) =
* Fix: at home delivery title
* Fix: Export Belgium delivery, use the street/number input fields

= 2.4.14 (2018-07-03) =
* Fix: Select the correct package type inside admin when there is one shipping used.

= 2.4.13 (2018-07-26) =
* Fix: Tabel rate shipping witch WooCommerce Table Rate Shipping by Automattic / Bolder Elements 4.0 / Bolder Elements 4.1.3
* Fix: The option to show the checkout only when he is linked to package

= 2.4.12 (2018-07-09) =
* Fix: #102 change Iceland to world shipping
* Fix: #106 tabel rates shipping
* Improvement: #94 support legacy consignment and tracktrace data
* Improvement: #95 Speed up order list view
* Improvement: #104 Add reference identifier, that is always the order id
= 2.4.11 (2018-04-30) =
* Fix: Export shipment labels

= 2.4.10 (2018-04-26) =
* Improvement: Support Effect Connect, you can place the barcode inside a note of the order

= 2.4.9 (2018-04-03) =
* Fix: Scrolling when changing package type in orderview
* Fix: Select the correct delivery methode inside the checkout
* Improvement: Support Cloudflare

= 2.4.8 (2018-02-27) =
* Fix: The array error from the userAgent (https://wordpress.org/support/topic/parse-error-syntax-error-unexpected-in-wp-content-plugins-woocommerce-post/)
* Fix: The countries Norway, Turkey, Switzerland changed to world country
* Fix: Changing Type from Order List (https://wordpress.org/support/topic/changing-type-from-order-list/#post-10020043)

= 2.4.7 (2018-02-07) =
* Improvement: WooCommerce 3.3.1 compatibility

= 2.4.6 (2018-02-01) =
* Improvement: WooCommerce 3.3 compatibility
* Feature: The option to print the label on A4 and A6 format

= 2.4.5 (2018-01-10) =
* Fix: Export an order with an old delivery date
* Refactor: Error about rest api (https://wordpress.org/support/topic/error-in-woocommerce/)
                  ```des/class-wcmp-rest-api-integration.php): failed to open stream```

= 2.4.4 (2018-01-09) =
* Fix:Error about rest api (https://wordpress.org/support/topic/error-in-woocommerce/)
      ```des/class-wcmp-rest-api-integration.php): failed to open stream```

= 2.4.3 (2018-01-05) =
* Fix: Add postnl fields to REST api to create order request
* Fix: Hide days when the pickup delivery is selected

= 2.4.2 (2017-10-29) =
* Fix: Price changes for 2018


= 2.4.1 (2017-10-12) =
* Fix: WooCommerce 3.2 compatibility

= 2.4.0 (2017-09-25) =
* Feature: Export world shipments + customs declaration form
* Feature: Show delivery options on thank you page
* Feature: Use WC logger when possible
* Fix: Return shipment error
* Fix: Order details layout for pickup location
* Fix: Delete cache of admin notices
* Fix: Display of negative delivery options price
* Fix: Improved tax handling on delivery options fees

= 2.3.3 (2017-06-27) =
* Fix: Pickup locations in Safari

= 2.3.2 (2017-06-26) =
* Fix: Delivery options header order
* Feature: Support for region (=state) in international addresses
* Feature: Hide Delivery options if PostNL service is unavailable

= 2.3.1 (2017-06-12) =
* Fix: Table Rate Shipping + WooCommerce 2.6 (error in settings)

= 2.3.0 (2017-06-12) =
* Feature: WooCommerce Table Rate Shipping support (woocommerce.com & Bolder Elements 4.0)
* Feature: Support for monday delivery
* Feature: Start print position
* Feature: Individual label printing from the order details page
* Fix: Delivery options checkout in Edge browser
* Fix: HTTPS issue with google fonts
* Fix: Multi-colli printing
* Fix: Delivery options tax in WC3.0
* Fix: Disable 'signature on delivery' & 'recipient only' when switching to pickup location in checkout
* Fix: Improve order-based calculation of highest shipping class

= 2.2.0 (2017-04-03) =
* WooCommerce 3.0 compatible
* **Requires PHP version 5.3 or higher**
* Feature: Validate NL postcodes
* Fix: Multistep checkout
* Fix: Email text translation typo
* Fix: Remove spin button (arrows) for house number checkout field
* Fix: Issues creating return shipments
* Fix: Clear delivery options (&costs) when no longer available or deselected
* Fix: Error exporting foreign addresses & PayPal Express checkout

= 2.1.3 =
* Feature: Option to autoload google fonts in delivery options
* Feature: [DELIVERY_DATE] placeholder on label
* Various minor fixes

= 2.1.2 =
* Fix: Script error on the Thank You page (interfered with Facebook/Google tracking)
* Fix: Don't show delivery date (backend/emails) if delivery days window is 0 (=disabled)
* Tweak: Notice for BE shop owners
* Tweak: Sanity check for delivery options

= 2.1.1 =
* Fix: Delivery options iPad/iPhone issues
* Fix: Ignore badly formatted delivery options data
* Fix: Don't show delivery options when cart doesn't need shipping (downloads/virtual items)
* Fix: Delivery options container not found (explicitly uses window scope)
* Tweak: Shipping column width/float in order backend
* Tweak: Page reloading & print dialogue flow optimizations

= 2.1.0 =
* Feature: Select combinations of Flat Rate & Shipping Class to link parcel settings & delivery options display
* Feature: Option to show delivery options for all shipping methods (except foreign addresses)
* Feature: Pick colors for the delivery options
* Feature: Set custom styles (CSS) for delivery options
* Feature: Enter '0' for the delivery days window to hide dates in the delivery options
* Fix: Don't apply 'only recipient' fee for morning & night delivery (already included)
* Fix: Order search issues
* Fix: 404 header on delivery options
* Tweak: Several delivery options style adjustments
* Tweak: Reload page after exporting

= 2.0.5 =
* Fix default insurance selection
* Tweak: Show shipping 'method title' instead of 'title' in settings (with fallback to title)
* Tweak: added `$order` object to `wcpostnl_email_text` filter

= 2.0.4 =
* Improved theme compatibility

= 2.0.3 =
* Fix: Checkout option fees tax
* Fix: Settings page conditional options display
* Improved settings migration from previous versions

= 2.0.2 =
* Fix order search
* Default delivery options background to white

= 2.0.1 =
* Completely revamped settings & export interface
* New delivery options replaces old 'Pakjegemak':
	* Postponed delivery (pick a delivery date)
	* Home address only option
	* Signature on delivery option
	* Evening or morning delivery option
	* PostNL Pickup & Early PostNL Pickup
	* Possibility to assign cost to the above delivery options
* Create return labels from the WooCommerce backend
* Uses new PostNL API

= 1.5.6 =
* Fix: Disable pakjegemak if 'ship to different address' is disabled after selecting Pakjegemak location
* Fix: Use billing postcode for Pakjegemak Track & Trace

= 1.5.5 =
* Fix: Foreign postcodes validation fix.

= 1.5.4 =
* Fix: Various Pakjegemak related issues (now saves & sends pakjegemak address separately to PostNL)
* Fix: Postcode validation issues with Portugal

= 1.5.3 =
* Feature: Edit postnl address fields on user profile page
* Fix: Bug with automatic order completion

= 1.5.2 =
* Feature: Option to keep old consignments when re-exporting
* Feature: Use billing name for pakjegemak (when empty)
* Feature: store pakjegemak choice
* Fix: prevent illegal export settings/combinations
* Tweak: Better error reporting
* Tweak: Small text changes (backend)

= 1.5.1 =
* Tweak: Added error when no consignments available when trying to print labels
* Tweak: Tip for direct processing of labels (when not enabled)
* Tweak: admin styles

= 1.5.0 =
* Feature: Shipment type setting (Pakket/Brievenbuspakje/Ongefrankeerd label)
* Feature: Multi-colli support
* Feature: More advanced insurance options
* Feature: Allow overriding pakjegemak passdata file via child theme (place in /woocommerce/)
* Fix: Backend address formatting/styles
* Fix: Unexpected output at first activation
* Tweak: Hide parcel settings for other shipment types
* Tweak: Remove deprecated comments field
* Tweak: Settings now under WooCommerce top menu
* Tweak: better error logging
* Dev: Code refactor

= 1.4.6 =
* Fix: Foreign Track & Trace link updated

= 1.4.5 =
* Tweak: Prevent label creation if direct processing is disabled. NOTE! If you had this setting disabled and were used to downloading the labels directly, you need to change this in the settings.
* Tweak: Remove required tags in checkout for disabled fields.

= 1.4.4 =
* Fix: error for missing shipping fields

= 1.4.3 =
* Fix: WooCommerce 2.2+ compatibility

= 1.4.2 =
* Fix: weight unit is now properly taken into account
* Tweak: different bulk action hook (for better compatibility)

= 1.4.1 =
* Fix: Broken special characters (ë, û, à etc.)
* Tweak: different API communication mode for secure configuration

= 1.4.0 =
* Feature: Print order number on label
* Feature: PakjeGemak integration
* Feature: Option to autocomplete order after successful export to PostNL
* Feature: Option to display Track & Trace link on my account page

= 1.3.8 =
* Fix: Big exports now run without any warnings/problems (was limited by the server)
* Fix: Names, cities etc. with quotes (')
* Fix: Error on combined foreign & Dutch exports
* Fix: IE9 compatibility

= 1.3.7 =
* Fix: Checkout placeholder data was being saved in older versions of Internet Explorer

= 1.3.6 =
* Feature: Option to download PDF or display in browser
* Fix: warnings when debug set to true & downloading labels directly after exporting
* Fix: WooCommerce 2.1 bug with copying foreign address data

= 1.3.5 =
* Fix: Errors when trashing & restoring trashed orders

= 1.3.4 =
* Fix: Errors on foreign country export
* Fix: legacy address data is now also displayed properly
* Tweak: background scrolling locked when exporting

= 1.3.3 =
* Fix: Checks for required fields
* Tweak: Improved address formatting
* Tweak: Removed placeholders on house number & suffix for better compatibility with old browsers

= 1.3.2 =
* Fix: Description labels for Custom ID ('Eigen kenmerk') & Message ('Optioneel bericht')

= 1.3.1 =
* Fix: button image width

= 1.3.0 =
* New PostNL icons
* Prepare & PDF buttons compatible with WC2.1 / MP6 styles
* Button styles are now in CSS instead of inline

= 1.2.0 =
* Feature: The postnl checkout fields (street name / house number) can now also be modified on the my account page
* Fix: WooCommerce 2.1 compatibility (checkout field localisation is now in WC core)
* Updated PostNL tariffs

= 1.1.1 =
* Fix: Labels for Custom id ('Eigen kenmerk') & Message ('Optioneel bericht') in the export window were reversed
* Fix: Removed depricated functions for better WooCommerce 2.1 compatibility

= 1.1.0 =
* Made extra checkout fields exclusive for dutch customers.
* Show process indicator during export.
* Various bugfixes.

= 1.0.0 =
* First release.

== Upgrade Notice ==
= 2.1 =
**Important!** Version 2.0 was a big update for this plugin, we recommend testing in a test environment first, before updating on a live site!
