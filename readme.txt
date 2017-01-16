=== Plugin Name ===
Contributors: pomegranate
Tags: woocommerce, export, myparcel
Requires at least: 3.5.1 & WooCommerce 2.0+
Tested up to: 4.7
Stable tag: 2.1.2
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

Export your WooCommerce orders to MyParcel (www.myparcel.nl) and print labels directly from the WooCommerce admin

== Description ==

This WooCommerce extension allows you to export your orders to the MyParcel service (www.myparcel.nl).

**Online Manual (in Dutch):** https://myparcelnl.github.io/woocommerce-handleiding/

= Main features =
- Delivery options integrated in your checkout
- Export your WooCommerce orders to MyParcel with a simple click, single orders or in batch
- Print shipping labels directly (PDF)
- Create multiple shipments for the same order
- Choose your package type (Parcel, mailbox package or unpaid letter)
- Define preset MyParcel shipping options (signature required, extra insurance, etc.)
- Modify the MyParcel shipping options per order before exporting
- Extra checkout fields to separate street name, house number and house number suffix for more precise address data
- View the status of the shipment in the order details page
- Add track&trace link to the order confirmation email

A MyParcel API account is required for this plugin! You can create this in your account or contact MyParcel at info@myparcel.nl

== Installation ==

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install of WooCommerce MyParcel, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WooCommerce MyParcel" and click Search Plugins. You can install it by simply clicking Install Now. After clicking that link you will be asked if you're sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

= Manual installation via the WordPress interface =
1. Download the plugin zip file to your computer
2. Go to the WordPress admin panel menu Plugins > Add New
3. Choose upload
4. Upload the plugin zip file, the plugin will now be installed
5. After installation has finished, click the 'activate plugin' link

= Manual installation via FTP =
1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

= Setting up the plugin =
1. Go to the menu `WooCommerce > MyParcel`.
2. Fill in your API Details. If you don't have API details, log into your myparcel account at [backoffice.myparcel.nl](http://backoffice.myparcel.nl) where you can find your API key under Instellingen → Algemeen.
3. Under 'Default export settings' you can set options that should be set by default for the export. You can change these settings per order at the time of export.
4. The plugin is ready to be used!

= Testing =
We advise you to test the whole checkout procedure once to see if everything works as it should. Pay special attention to the following:

The MyParcel plugin adds extra fields to the checkout of your webshop, to make it possible for the client to add street name, number and optional additions separately. This way you can be sure that everything is entered correctly. Because not all checkouts are configured alike, it's possible that the positioning/alignment of these extra fields have to be adjusted.

Moreover, after a label is created, a track&trace code is added to the order. When the order is completed from WooCommerce, this track & trace code is added to the email (when this is enabled in the settings). Check that the code is correctly displayed in your template. You can read how to change the text in the FAQ section.

== Frequently Asked Questions ==

**Online Manual (in Dutch):** https://myparcelnl.github.io/woocommerce-handleiding/

= How do I get an API key? =
When logged in on your myparcel account at [backoffice.myparcel.nl](http://backoffice.myparcel.nl) you can find your API key under Instellingen → Algemeen.

= How do I change the track&trace email text? =
You can change the text (which is placed above the order details table by default) by applying the following filter:
`
add_filter( 'wcmyparcel_email_text', 'wcmyparcel_new_email_text' );
function wcmyparcel_new_email_text($track_trace_tekst) {
	// Tutoyeren ipv vousvoyeren
	$nieuwe_tekst = 'Je kunt je bestelling volgen met het volgende PostNL track&trace nummer:';
	return $nieuwe_tekst;
}
`

== Screenshots ==

1. Export or print myparcel label per order
2. Bulk export or print myparcel labels
3. Change the shipment options for an order
4. MyParcel actions on the order overview page
5. Myparcel information on the order details page

== Changelog ==

**Important!** Version 2.0 was a big update for this plugin, we recommend testing in a test environment first, before updating on a live site!

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
* Tweak: added `$order` object to `wcmyparcel_email_text` filter

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
* Uses new MyParcel API

= 1.5.6 =
* Fix: Disable pakjegemak if 'ship to different address' is disabled after selecting Pakjegemak location
* Fix: Use billing postcode for Pakjegemak track & trace

= 1.5.5 =
* Fix: Foreign postcodes validation fix.

= 1.5.4 =
* Fix: Various Pakjegemak related issues (now saves & sends pakjegemak address separately to MyParcel)
* Fix: Postcode validation issues with Portugal

= 1.5.3 =
* Feature: Edit myparcel address fields on user profile page
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
* Fix: Foreign track & trace link updated

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
* Feature: Option to autocomplete order after successful export to MyParcel
* Feature: Option to display track&trace link on my account page

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
* New MyParcel icons
* Export & PDF buttons compatible with WC2.1 / MP6 styles
* Button styles are now in CSS instead of inline

= 1.2.0 =
* Feature: The myparcel checkout fields (street name / house number) can now also be modified on the my account page
* Fix: WooCommerce 2.1 compatibility (checkout field localisation is now in WC core)
* Updated MyParcel tariffs

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
= 2.1.2 =
**Important!** Version 2.0 was a big update for this plugin, we recommend testing in a test environment first, before updating on a live site!

