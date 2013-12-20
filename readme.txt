=== Plugin Name ===
Contributors: pomegranate
Tags: woocommerce, export, myparcel
Requires at least: 3.5.1
Tested up to: 3.7.1
Stable tag: 1.1.1
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

Export your WooCommerce orders to MyParcel (www.myparcel.nl) and print labels directly from the WooCommerce admin

== Description ==

This WooCommerce extension allows you to export your orders to the MyParcel service (www.myparcel.nl). Single orders exports aswell as batch exports are possible.

= Main features =
- Export single orders or batches of orders
- Define preset MyParcel shipping options (signature required, extra insurance, etc.)
- Modify the MyParcel shipping options per order before exporting
- Extra checkout fields to separate street name, house number and house number suffix for more precise address data
- View the status of the shipment in the order details page
- Add track&trace link to the order confirmation email
- Print MyParcel labels directly from WooCommerce (PDF)

A MyParcel API account is required for this plugin! Get one at info@myparcel.nl

== Installation ==

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install of WooCommerce, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

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

== Frequently Asked Questions ==

= How do I get an API key =

Send an email top info@myparcel with your account name and you will be sent all necessary information.

== Screenshots ==

1. Export or print myparcel label per order
2. Bulk export or print myparcel labels
3. View the status of the shipment on the order details page.

== Changelog ==

= 1.1.1 =
* Fix: Labels for Custom id ('Eigen kenmerk') & Message ('Optioneel bericht') in the export window were reversed
* Fix: Removed depricated functions for better WooCommerce 2.1 compatibility

= 1.1.0 =
* Made extra checkout fields exclusive for dutch customers.
* Show process indicator during export.
* Various bugfixes.

= 1.0.0 =
* First release.