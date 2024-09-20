=== Plugin Name ===
Contributors: richardperdaan, edielemoine, joerimyparcel
Tags: woocommerce, export, delivery, packages, myparcel, flespakket, postnl
Requires at least: 5.2.0
Tested up to: 6.2
Stable tag: 4.24.1
Requires PHP: 7.1
License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php

Export your WooCommerce orders to MyParcel (www.myparcel.nl) or to Flespakket (www.flespakket.nl) and print labels directly from the WooCommerce admin

== Description ==
[vimeo https://vimeo.com/507950149]
This WooCommerce extension allows you to export your orders to the MyParcel service (www.myparcel.nl) & Flespakket service (www.flespakket.nl). The products are delivered by PostNL.

**Online Manual (in Dutch):** [https://myparcelnl.github.io/woocommerce/](https://myparcelnl.github.io/woocommerce/)

###The no. 1 shipping platform for every online entrepreneur.
We are MyParcel, the platform with which every online entrepreneur, large or small, can easily create shipping labels to send parcels, mail, letterbox parcels, and pallets. To destinations inland and abroad. Our goal is to make the services of major carriers accessible to every online entrepreneur so that they have time for what they are good at: doing business. We do this with our easy and accessible shipping service, smart IT solutions, and the most personal customer service.

###How does it work?
Automatically import your orders from any desired sales channel into the platform and print out your shipping labels in no time. Have your packages picked up or bring them yourself to a PostNL or Instabox delivery point. Your customer receives the track & trace information, and you can keep an overview of all shipments in the MyParcel platform.

###Just like the other 36,000 webshops, experience…
- Our free personal customer service. We offer you direct telephone support!
- How to save time and money printing your shipping labels.
- That you are not tied to anything! We do not charge monthly fees.
- How to easily integrate our shipping platform into your webshop.

###Main features.
- Delivery options integrated in your checkout.
- Export your WooCommerce orders to MyParcel with a simple click, single orders or in batch.
- Print shipping labels directly (PDF).
- Create multiple shipments for the same order.
- Choose your package type (Parcel, Digital stamp, mailbox package or unpaid letter)
- Define preset MyParcel shipping options (signature required, extra insurance, etc.).
- Modify the MyParcel shipping options per order before exporting.
- Extra checkout fields to separate street name, house number and house number suffix for more precise address data
- View the status of the shipment in the order details page.
- Add Track & Trace link to the order confirmation email.

An API-key is required for this plugin! You can create this in your backoffice account.

== Installation ==

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install of MyParcel, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "MyParcel" and click Search Plugins. You can install it by simply clicking Install Now. After clicking that link you will be asked if you're sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

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
2. Fill in your API Details. If you don't have API details, log into your MyParcel or Flespakket account, you can find your API key under Instellingen → Algemeen.
3. Under 'Default export settings' you can set options that should be set by default for the export. You can change these settings per order at the time of export.
4. The plugin is ready to be used!

= Testing =
We advise you to test the whole checkout procedure once to see if everything works as it should. Pay special attention to the following:

The MyParcel plugin adds extra fields to the checkout of your webshop, to make it possible for the client to add street name, number and optional additions separately. This way you can be sure that everything is entered correctly. Because not all checkouts are configured alike, it's possible that the positioning/alignment of these extra fields have to be adjusted.

Moreover, after a label is created, a Track & Trace code is added to the order. When the order is completed from WooCommerce, this Track & Trace code is added to the email (when this is enabled in the settings). Check that the code is correctly displayed in your template. You can read how to change the text in the FAQ section.

== Frequently Asked Questions ==

**Online Manual (in Dutch):** [https://myparcelnl.github.io/woocommerce/](https://myparcelnl.github.io/woocommerce/)

= How do I get an API key? =
When logged in on your MyParcel or Flespakket account, you can find your API key under Instellingen → Algemeen.

= How do I change the Track & Trace email text? =
You can change the text (which is placed above the order details table by default) by applying the following filter:
`
add_filter( 'wcmyparcel_email_text', 'wcmyparcel_new_email_text' );
function wcmyparcel_new_email_text($track_trace_tekst) {
	// Tutoyeren ipv vousvoyeren
	$nieuwe_tekst = 'Je kunt je bestelling volgen met het volgende PostNL Track & Trace nummer:';
	return $nieuwe_tekst;
}
`

== Screenshots ==

1. Export or print MyParcel label per order
2. Bulk export or print MyParcel labels
3. Change the shipment options for an order
4. MyParcel information on the order details page
5. MyParcel at home delivery options
6. MyParcel pickup delivery options

== Changelog ==

= 4.24.1 (2024-09-20) =

* Fixed bug: allow dhl for you export with default volume properties

= 4.24.1-rc.1 (2024-09-20) =

* Fixed bug: allow dhl for you export with default volume properties

= 4.24.0 (2024-09-17) =

* New: add support for small package (#1158)
* Fixed bug: fix small parcel in the Delivery Options (#1165)
* Fixed bug: prevent warning undefined array key (#1122)

= 4.23.1-rc.2 (2024-09-16) =

* Fixed bug: fix small parcel in the Delivery Options (#1165)

= 4.23.1-rc.1 (2024-07-04) =

* Fixed bug: prevent warning undefined array key (#1122)

= 4.23.0 (2024-06-11) =

* New: add hpos incompatibility warning (#1109)

= 4.23.0-rc.1 (2024-06-11) =

* New: add hpos incompatibility warning (#1109)

= 4.22.2 (2024-04-30) =

* Fixed bug: fix myparcel div not found (#1091)
* Fixed bug: restore compatibility with wooc table rate (#1090)
* Fixed bug: use correct url for foreign shipments (#1088)
* Fixed bug: fix item value on customs declaration (#1066)

= 4.22.2-rc.4 (2024-04-22) =

* Fixed bug: fix myparcel div not found (#1091)

= 4.22.2-rc.3 (2024-04-19) =

* Fixed bug: restore compatibility with wooc table rate (#1090)

= 4.22.2-rc.2 (2024-04-12) =

* Fixed bug: use correct url for foreign shipments (#1088)

= 4.22.2-rc.1 (2024-02-28) =

* Fixed bug: fix item value on customs declaration (#1066)

= 4.22.1 (2024-01-02) =

* Fixed bug: compare versions for upgrade or install (#1063)

= 4.22.1-rc.1 (2024-01-02) =

* Fixed bug: compare versions for upgrade or install (#1063)

= 4.22.0 (2024-01-02) =

* New: change digital stamp weight range (#1061)
* Fixed bug: conform to minimum item value of api (#991)
* Fixed bug: do not confirm delivery for virtual orders (#987)

= 4.22.0-rc.1 (2024-01-02) =

* New: change digital stamp weight range (#1061)
* Fixed bug: conform to minimum item value of api (#991)
* Fixed bug: do not confirm delivery for virtual orders (#987)

= 4.21.1-rc.2 (2023-08-10) =

* Fixed bug: conform to minimum item value of api (#991)

= 4.21.1-rc.1 (2023-08-10) =

* Fixed bug: do not confirm delivery for virtual orders (#987)

= 4.21.0 (2023-07-31) =

* New: add order notes (#994)

= 4.20.2 (2023-05-26) =

* Fixed bug: fix missing translations

= 4.20.1 (2023-05-25) =

* Fixed bug: update @myparcel/delivery-options to 5.7.4 (#982)

= 4.20.0 (2023-05-23) =

* New: allow dhl for you features for every user

= 4.19.2 (2023-05-23) =

* Fixed bug: fix showing address errors before address is filled in (#976)

= 4.19.1-rc.1 (2023-05-01) =

* Fixed bug: fix showing address errors before address is filled in (#976)
* Fixed bug: fix billing address is required error in checkout (#970)

= 4.19.1 (2023-04-13) =

* Fixed bug: fix billing address is required error in checkout (#970)

= 4.19.0 (2023-04-11) =

* New: add barcode to fulfilment orders (#966)
* Fixed bug: default to postnl when carrier id is invalid (#972)

= 4.19.0-rc.1 (2023-04-11) =

* New: add barcode to fulfilment orders (#966)
* Fixed bug: default to postnl when carrier id is invalid (#972)

= 4.19.0-rc.2 (2023-04-11) =

* Fixed bug: default to postnl when carrier id is invalid (#972)

= 4.19.0-rc.1 (2023-04-11) =

* New: add barcode to fulfilment orders (#966)

= 4.18.8 (2023-04-11) =

* Fixed bug: fix address_1 being empty with separate address fields (#971)

= 4.18.7 (2023-04-06) =

* Fixed bug: prevent passing unknown carrier id (#969)
* Fixed bug: prevent carrier 5 error when retrieving account settings (#968)
* Fixed bug: do not show address inputs when allowRetry is false (#967)
* Fixed bug: fix absent version in svn release script (#964)

= 4.18.7-rc.3 (2023-04-06) =

* Fixed bug: prevent passing unknown carrier id (#969)

= 4.18.7-rc.2 (2023-04-04) =

* Fixed bug: prevent carrier 5 error when retrieving account settings (#968)

= 4.18.7-rc.1 (2023-04-04) =

* Fixed bug: do not show address inputs when allowRetry is false (#967)
* Fixed bug: fix absent version in svn release script (#964)

= 4.18.6 (2023-03-17) =

* Fixed bug: allow delivery options with partial address (#963)
* Fixed bug: prevent javascript errors on order-pay (#961)

= 4.18.6-rc.1 (2023-03-16) =

* Fixed bug: prevent javascript errors on order-pay (#961)

= 4.18.5 (2023-03-10) =

* Fixed bug: dont send number suffix with delivery options api call (#960)

= 4.18.4 (2023-03-10) =

* Fixed bug: fix numeric number suffix not working in checkout

= 4.18.3 (2023-03-10) =

* Fixed bug: fix operator error in order settings (#959)

= 4.18.2 (2023-03-09) =

* Fixed bug: change the return type of getTimestamp to match with the parent (#958)

= 4.18.2-rc.1 (2023-03-09) =

* Fixed bug: change the return type of getTimestamp to match with the parent (#958)

= 4.18.1 (2023-03-09) =

* Fixed bug: fix delivery options for dhl parcelconnect and dhl europlus (#957)

= 4.18.0 (2023-03-01) =

* New: add carrier dhl europlus and dhl parcelconnect (#955)
* Fixed bug: close inline style properly (#954)

= 4.17.1-rc.1 (2023-02-21) =

* Fixed bug: close inline style properly (#954)

= 4.17.0 (2023-02-21) =

* New: add customizable string for address not found (#945)
* Fixed bug: pass cc argument to insurance function (#953)
* Fixed bug: fix type error in getting value of option (#952)
* Fixed bug: only read shipping class term when available (#951)
* Fixed bug: prevent type error in flat rate evaluate cost (#949)
* Fixed bug: prevent widget config errors (#947)
* Fixed bug: fix belgian number suffix (#923)
* Fixed bug: delete address not found prompt from checkout (#946)
* Fixed bug: prevent undefined array keys (#933)

= 4.17.0-rc.7 (2023-02-20) =

* Fixed bug: pass cc argument to insurance function (#953)

= 4.17.0-rc.6 (2023-02-16) =

* Fixed bug: fix type error in getting value of option (#952)

= 4.17.0-rc.5 (2023-02-03) =

* Fixed bug: only read shipping class term when available (#951)

= 4.17.0-rc.4 (2023-01-25) =

* Fixed bug: prevent type error in flat rate evaluate cost (#949)

= 4.17.0-rc.3 (2023-01-12) =

* Fixed bug: prevent widget config errors (#947)

= 4.17.0-rc.2 (2023-01-11) =

* Fixed bug: fix belgian number suffix (#923)

= 4.17.0-rc.1 (2023-01-09) =

* New: add customizable string for address not found (#945)
* Fixed bug: delete address not found prompt from checkout (#946)

= 4.16.5-rc.1 (2023-01-09) =

* Fixed bug: prevent undefined array keys (#933)

= 4.16.5-develop.1 (2023-01-09) =

* Fixed bug: prevent undefined array keys (#933)

= 4.16.4 (2023-01-05) =

* Fixed bug: convert product dimensions from measurement unit (#941)

= 4.16.3 (2023-01-04) =

* Fixed bug: prevent passing null to datetime (#940)

= 4.16.2 (2023-01-04) =

* Fixed bug: fix several type errors (#939)

= 4.16.1 (2023-01-03) =

* Fixed bug: show order detail even when package type is integer (#935)

= 4.16.0 (2023-01-03) =

* New: add insurance options for eu shipments (#922)
* New: add carrier dhl (#913)
* Fixed bug: fix str_replace fatal error during checkout (#931)
* Fixed bug: fix type error on fresh install
* Fixed bug: fix deprecation warning on belgian shipments (#929)
* Fixed bug: fix exclude billing with digital download (#930)
* Fixed bug: prevent error substr expects parameter 1 to be string (#927)
* Fixed bug: always load account settings during upgrade (#916)

= 4.16.0-rc.2 (2022-12-20) =

* Fixed bug: fix deprecation warning on belgian shipments (#929)
* Fixed bug: fix exclude billing with digital download (#930)
* Fixed bug: prevent error substr expects parameter 1 to be string (#927)

= 4.16.0-rc.1 (2022-12-07) =

* New: add carrier dhl (#913)

= 4.15.3-rc.1 (2022-12-07) =

* Fixed bug: always load account settings during upgrade (#916)

= 4.15.2 (2022-12-06) =

* Fixed bug: provide correct version to svn deploy (#919)

= 4.15.1 (2022-11-22) =

* Fixed bug: prevent error myparcel admin already exists

= 4.15.0 (2022-11-22) =

* New: add modal option for myparcel orders only (#869)
* New: show digital stamp weight in order view (#896)
* New: show test connection button (#881)
* Fixed bug: allow return options (#915)
* Fixed bug: load account settings during upgrade (#914)
* Fixed bug: remove carrier instabox (#912)
* Fixed bug: allow link to order detail from widget (#906)
* Fixed bug: show tracktrace in order email when no refunds (#908)
* Fixed bug: get correct hs code and country of origin for variation (#910)
* Fixed bug: show tracktrace in order email when no refunds
* Fixed bug: prevent dismissed message from reappearing (#898)
* Fixed bug: prevent unnecessary automatic export (#895)
* Fixed bug: remove barcode from refund mail (#894)

= 4.14.0 (2022-10-11) =

* New: add support for advanced shipping (#879)
* Fixed bug: make deploy workflow reusable (#888)
* Fixed bug: prevent automatic export when local pickup (#887)
* Fixed bug: fix belgian box numbers (#883)
* Fixed bug: fix digital stamp default weight (#871)

= 4.13.3 (2022-08-16) =
* Fix: prevent external translations (#873) (a67f404)
* Fix: prevent shipping method from defaulting to package (#865) (45409e7)
* Fix: use shift instead of index (#866) (d31bb15)

= 4.13.2 (2022-08-16) =
* Fix: catch orders without shipping method (#864) (2368a8e)

= 4.13.1 (2022-08-16) =
* Fix: use local pickup method id (#860) (56fb80d)

= 4.13.0 (2022-08-02) =
* Feat: add myparcel block to dashboard (#854) (f13f9b6e)
* Fix: allow numbers as suffix using myparcel address fields (#850) (3b5d712)
* Fix: fix tablerate plugin mailbox (#859) (09f1d08)
* Fix: fix track trace link in order summary (#856) (c3b03ea)
* Fix: om: error when delivery options absent (#845) (e964884)
* Fix: only subscribe to webhook when api key changed (#846) (8a2b92c)
* Fix: php warnings undefined array key (#848) (7a82b33)
* Fix: prevent exporting of orders with local pickup as shipping method (#858) (508dc49)
* Fix: remove age check from eu and row consignments (#855) (2e6a9eb)

= 4.12.1 (2022-06-20) =
* Fix: om: fix error when creating webhook (#840) (2b09ef4)

= 4.12.0 (2022-06-14) =
* Feat: add default weight for digital stamp (#832) (bccf454)
* Feat: add option to save customer address (#827) (bdcb0bf)
* Feat: order status change after label print in backoffice (#831) (5846d56)
* Feat: show message when order management is enabled (#815) (66be2e7)
* Fix: allow empty shipping method in post from checkout (#834) (c273e42)
* Fix: auto export function (#835) (9c3a672)
* Fix: **export** messages handling (#809) (e073f55)
* Fix: **export** show correct shipment status in order grid (#828) (fd6b627)
* Fix: no shipping details when using local pickup (#826) (97413cf)
* Fix: only show delivery date in order view when necessary (#816) (44e02a7)
* Fix: syntax error on 4.1.0 migration (#817) (df61016)
* Fix: the weight calculations must be multiplied (#814) (08c8992)

= 4.11.0 (2022-04-15) =
* Feat: enable order management setting (#824) (78524ae)

= 4.10.2 (2022-04-05) =
* Fix: update delivery options

= 4.10.1 (2022-04-05) =
* Fix: the weight calculations must be multiplied (#814)
* Fix: show message when order management is enabled (#815)
* Fix: only show delivery date in order view when necessary (#816)
* Fix: syntax error on 4.1.0 migration (#817)

= 4.10.0 (2022-03-21) =
* Feat: add settings for same day delivery (#810) (3c170b7)
* Fix: allow 6 characters as number suffix (#806) (073acc9)
* Fix: fatal error in 4.1.0 migration (#811) (ee20777)
* Fix: multiple return labels for label in the box (#808) (fa19b4a)

= 4.9.2 (2022-02-16) =
* Fix: **hotfix:** catch fatal error on faulty address

= 4.9.1 (2022-02-02) =
* Fix: **hotfix:** fix error when an address can't be split

= 4.9.0 (2022-02-01) =
* Feat: propagate api url to delivery options (#775) (9656be2)
* Feat: add same day shipment option for Instabox (#764) (ccd4c1c)
* Feat: persist notices over requests (#753) (548160d)
* Fix: do not show insurance amount when irrelevant (#771) (d47a111)
* Refactor: rename Red je Pakketje to Instabox (#767) (ab93339)

= 4.8.2 (2022-01-11) =
* Fix: **hotfix:** show delivery options prices in order review

= 4.8.1 (2022-01-10) =
* Fix: prevent eternal recreation of webhook

= 4.8.0 (2022-01-03) =
* Feat: add option to disable insurance for shipments to Belgium (#744) (5017e19)
* Feat: add region to consignment when exporting (#745) (0d91010)
* Feat: pps: export label description (b7ae15f)
* Fix: accountsettings may be empty not absent (#729) (004ce07)
* Fix: add 'parent' element to return_shipment_data array (#738) (8dfb65f)
* Fix: allow old shipping methods in order (#720) (fda83bc)
* Fix: export orders with a pickup location for PPS (4b21dea)
* Fix: pps: export ROW orders (#742) (48867a6)
* Fix: fix return email not working (#751) (4334bbf)
* Fix: save lowest insurance possibility (#734) (c962a22), closes #730

= 4.7.0 (2021-11-18) =
* Feat: add instabox
* Fix: no notice when weight missing in order
* Fix: re-add alternate delivery days settings

= 4.6.0 (2021-11-17) =
* Feat: Customize delivery type title on confirmation
* Feat: Weight feature for extra large package
* Fix: Duplicate queries in wordpress backend
* Fix: Use correct product quantity for label description

= 4.5.0 (2021-10-08) =
* Feat: Add order mode 'Export entire order' aka PPS
* Feat: Make age check, large format and insurance available to pickup
* Feat: Disable morning/evening delivery when agecheck is present
* Feat: Always show delivery date when available on order grid
* Fix: Return extra options without extra linebreaks
* Fix: Add WPDesk flexible shipping single compatibility
* Fix: Url for print button to act like other print label buttons

= 4.4.5 (2021-08-17) =
* Fix: Update package type above shipment options dialog without refreshing page
* Fix: Ignore virtual products when creating consignment
* Fix: Check shipment type before empty DPZ is calculated
* Fix: When a product is created, country of origin must be set to default
* Fix: Woocommerce hooks for the address fields in the admin

= 4.4.4 (2021-07-15) =
* Hotfix: Minimum steps for empty weight
* Hotfix: Export ROW shipments witch correct country of origin

= 4.4.3 (2021-07-14) =
* Fix: Being able to remove the delivery date from the delivery options

= 4.4.2 (2021-07-13) =
* Feat: Filter orders by delivery date
* Feat: Add empty weight for digital stamp shipments
* Fix: Age check with different scenarios
* Fix: Double export when using the bulk actions2

= 4.4.1 (2021-07-06) =
* Feat: Added setting to show or hide delivery date in frontend
* Feat: Allow overriding delivery options via filter hooks
* Feat: Added hooks to let plugins change the label description
* Fix: Error on changing country on account page
* Fix: Additional fees not being added to totals if delivery options titles are empty
* Fix: Check if package type is set in _myparcel_shipment_options meta
* Fix: Bug where getShipmentData would return all orders
* Fix: Delete status delivered

= 4.4.0 (2021-05-26) =
* Improvement: Default option for deliveryoptions maps or list
* Improvement: Display product country of origin as list
* Improvement: Choose an order status that will automatically export a shipment to MyParcel
* Improvement: Only show delivery options with non-virtual product in order
* Fix: use `type hidden` to hide the delivery options input.
* Fix: about empty shipping class
* Fix: Show delivery type only when myparcel delivery options are enabled
* Fix: isworldshipmentcountry returns false when country does not exist
* Fix: More friendlier error messages
* Fix: Error when changing the order when shipment_options_extra meta does not exist
* Fix: Change mailbox shipments outside Netherlands to package

= 4.3.2 (2021-03-30) =
* Improvement: wpm-config.json included (support WP-Multilang plugin)
* Improvement: add translation files fr_FR
* Improvement: Deactivate delivery date
* Improvement: Option for automatic order status after exporting or printing
* Fix: Saving options in the order grid model
* Fix: Validation for sending to other address on checkout page

= 4.3.1 (2021-03-19) =
* Improvement: Export bulk order shipments although there is a wrong shipment
* Improvement: Add option to show prices as surcharge
* Improvement: Support WP Desk Flexible Shipping plugin
* Fix: Calculate weight from grams to kilos during the migration
* Fix: Set MyParcel response cookie with 20 sec expire
* Fix: Use saturday cutoff time
* Fix: Use country codes from the MyParcel SDK
* Fix: Translation files not being generated properly

= 4.2.0 (2021-01-21) =
* Fix: Rename `WCMP`, `WCMP()`, `WCMP_Admin` and `WCMP_Settings`
* Fix: Weight calculation for all shipment types
* Fix: Delivery options after woocommerce subtotal for solving conflicts with multiple themes
* Fix: Error array_replace_recursive second parameter must be array
* Fix: Show correct delivery type in orders grid
* Fix: Package type from shipping method/class not shown in order grid
* Fix: Unable to send return email
* Fix: Send return email modal
* Fix: Show delivery date in order grid for any order that has one
* Fix: Don't load checkout scripts on order received page
* Fix: Multicollo voor Dutch shipments and for international shipments can you create multiple labels
* Fix: Missing barcode in emails of automatically processed orders
* Fix: Properly add/remove cart fees when using delivery options
* Fix: Error on checkout when using custom address fields
* Fix: Maximum label description length of 45 characters
* Fix: Multiple barcode in order note
* Fix: Saving the correct days in the setting drop off days
* Fix: Save the correct shipping class
* Fix: Check if shipping address is selected on checkout page en use the correct address
* Fix: Order confirmation on the thank you page, confirmation email and on the customer account
* Fix: Do not save address in address book
* Fix: Correct package type for international shipments
* Fix: Only add empty parcel weight to packages
* Fix: Export via actions at the bottom of the order-grid
* Improvement: Set correct UserAgent
* Improvement: More options for age verification at product level
* Improvement: Better country of origin selection
* Improvement: Improve shipment options dialog
* Improvement: Spinner for order grid bulk actions
* Improvement: Update icons
* Improvement: Use base price for delivery options
* Improvement: Error handling after exporting and printing a label
* Improvement: Stabilizer code for opening a label in a new tab
* Improvement: New status for letter and DPZ and show them on the barcode column
* Improvement: Use gulp to allow es6 javascript and use sass.
* Improvement: Use customer note for label description.
* Improvement: Use the latest MyParcel SDK.
* Improvement: Handle translations in gulp

= 4.1.5 (2020-12-15) =
* Fix: select box for country of origin
* Fix: delivery options for not logged in users
* Improvement: prices from the shipping method inside the delivery options

= 4.1.4 (2020-11-24) =
* Fix: Shipping classes not saving
* Fix: Drop off days
* Fix: WooCommerce PDF Invoices & Packing Slips placeholders compatibility
* Fix: Calculate DPZ weight
* Fix: Error delivery_date is too early
* Fix: Multiple barcode in order note
* Fix: Maximum label description lenght of 45 characters
* Improvement: support WP-Multilang


= 4.1.3 (2020-11-13) =
* Fix: Error on checkout when using custom address fields

= 4.1.2 (2020-11-12) =
* Fix: Crack down on invalid package types

= 4.1.1 (2020-11-11) =
* Fix: 4.1.0 migration fatal errors
* Fix: PHP Fatal error: Uncaught TypeError: Return value of WCMP_Export::getPackageTypeFromOrder()

= 4.1.0 (2020-11-11) =
* Improvement: All enabled/disabled dropdowns replaced with clickable toggles.
* Improvement: Show package type and delivery date instead of "details".
* Improvement: Add label description for individual shipments.
* Improvement: Loading speed/experience.
* Improvement: Spinner for order grid bulk actions.
* Improvement: make default export settings show up in shipment options.
* Improvement: show delivery date in order grid for any order that has one (only when "show delivery day" setting is enabled).
* Fix: Calculated weight is shown for digital stamps.
* Fix: Wrong label for "show delivery day" setting.
* Fix: Error on sending return email.
* Fix: Allow split address field for Belgium as well.
* Fix: Add options that were missing in 4.0.0
* Fix: Rename at_home_delivery to delivery_title
* Fix: Monday delivery

= 4.0.6 (2020-10-14) =
* Fix: Free_shipping linked to package then you should also see the delivery options
* Fix: If you have a shipping method with flatrate: 181 and the method gives flatrate: 18 then you should not see the delivery options
* Fix: Error CRITICAL Uncaught TypeError: Return value of WCMP_Export::getShippingMethod()

= 4.0.5 (2020-10-05) =
* Fix: Disable order status delivered
* Fix: Package type not being recognized
* Fix: migrate all package types in export defaults settings

= 4.0.4 (2020-10-01) =
* Fix: Failed opening class-wcmypa-settings.php

= 4.0.3 (2020-10-01) =
* Fix: Old settings non existent error
* Fix: Class naming for theme compatibility

= 4.0.2 (2020-08-21) =
* Fix:  Show delivery options with a shipping class and with tablerates
* Improvement: Automatic insurance

= 4.0.1 (2020-07-29) =
* Fix: Wrong meta variable country of origin
* Fix: Html layout of shipment summary settings and searching in WooCommerce orders overview
* Fix: Translations
* Fix: Export pickup locations
* Fix: When deliveryType is empty use default package
* Fix: Html layout of shipment summary and searching in WooCommerce orders overview
* Improvement: Add empty parcel weight option
* Improvement: Add multicollo option

= 4.0.0 (2020-06-24) =
* Fix: HS code
* Fix: Delete options keep old shipments
* Fix: Insurance possibilities
* Fix: Barcode in orderview
* Fix: Housenumber and suffix
* Improvement: Country of origin
* Improvement: New checkout and SDK
* Improvement: Automatic export after payment
* Improvement: V2 shipment endpoint
* Improvement: HS code for variable product

= 3.2.1 (2020-02-04) =
* Fix: The recursive delivery date loop and full cache

= 3.2.0 (2020-01-27) =
* Fix: Since November is it no longer possible to use pickup express.
* Fix: Warning: invalid argument supplied .... class-wc-shipping-flat-rate.php.

= 3.1.8 (2019-11-12) =
* Fix: Check if there is connection with MyParcel

= 3.1.7 (2019-07-16) =
* Fix: Search in order grid MyParcel shipment
* Fix: More than 5 products for World shipments

= 3.1.6 (2019-07-04) =
* Fix: Use constants for delivery_type
* Fix: Saturday cutoff time
* Fix: Shipping method issue with pickup
* Fix: Digital stamp weight issue

= 3.1.5 (2019-05-14) =
* Improvement: Add the link for the personalized Track & Trace page (portal)
* Improvement: Show deliverday only for NL shipments
* Improvement: Cut the product title after 50 characters
* Improvement: Barcode in order grid
* Fix: Translation house number again button
* Fix: Set default to 0 if there's no tax rate set up
* Fix: fix issue with shipping class term id
* Fix: Correct amount on the digital stamp
* Fix: trying to get property of non-object
* Fix: Shipment validation error (PakjeGemak)

= 3.1.4 (2019-03-18) =
* Fix: Delivery date when deliveryday window is 0
* Fix: Change `afgevinkt` to `uitgevinkt`
* Preparation: Move Great Britain to world shipment for the Brexit

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
* Improvement: Add Digital stamp

= 3.0.10 (2018-12-05) =
* Hotfix: Flashing of the order summary.

= 3.0.9 (2018-12-04) =
* Hotfix: Get mailbox delivery option and save it into the order.

= 3.0.8 (2018-12-04) =
* Fix: The multiple calls that are made to retrieve the shipping data.
* Fix: The option for Pick up extra early
* Fix: Wrong house number / postcode message and the possibility to adjust the address in the MyParcel checkout
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
* Improvement: MyParcel delivery header titel
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
* Fix: The array error from the userAgent (https://wordpress.org/support/topic/parse-error-syntax-error-unexpected-in-wp-content-plugins-woocommerce-mypa/)
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
* Fix: Add MyParcel fields to REST api to create order request
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
* Fix: Use billing postcode for Pakjegemak Track & Trace

= 1.5.5 =
* Fix: Foreign postcodes validation fix.

= 1.5.4 =
* Fix: Various Pakjegemak related issues (now saves & sends pakjegemak address separately to MyParcel)
* Fix: Postcode validation issues with Portugal

= 1.5.3 =
* Feature: Edit MyParcel address fields on user profile page
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
* Feature: Option to autocomplete order after successful export to MyParcel
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
* New MyParcel icons
* Export & PDF buttons compatible with WC2.1 / MP6 styles
* Button styles are now in CSS instead of inline

= 1.2.0 =
* Feature: The MyParcel checkout fields (street name / house number) can now also be modified on the my account page
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
= 2.1 =
**Important!** Version 2.0 was a big update for this plugin, we recommend testing in a test environment first, before updating on a live site!

= 4.0.0 =
**Important!** Version 4.0.0 was a big update for this plugin, we recommend testing in a test environment first, before updating on a live site!
