=== Hungarian Pickup Points & Shipping Labels for WooCommerce ===
Contributors: passatgt
Tags: gls, postapont, foxpost, packeta, dpd
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 4.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pickup point map and shipping labels for WooCommerce, for home delivery too. MPL, Foxpost, GLS, DPD, Express One, Packeta and more.

== Description ==

With this plugin you can show a map on the checkout page, where the customer can choose from the pickup points of the selected providers. In the settings you can set separate pricing for each provider, and you can change the colors of the map in the Customize menu.
With the PRO version you can also generate shipping labels, for home delivery orders too, and you can create your own tracking page, with custom automations based on the package status.

> **PRO version**
> A PRO version of the plugin is available for €30, which you can buy here: [https://visztpeter.me](https://visztpeter.me/woocommerce-csomagpont-integracio/)
> The license key can be activated on one website and is valid for 1 year. It includes e-mail support for setup, customization and configuration.
> Buying it also supports the development of the plugin, even if you don't need the features of the PRO version.

= Features =

* Postapont, Foxpost, Packeta, GLS, Express One, DPD and Sameday (easybox) pickup point selector on one map
* In the PRO version you can generate and print Foxpost, Packeta, GLS, DPD, Posta (MPL), Sameday, Express One, Trans-Sped ZERO and Csomagpiac labels, for home delivery too, not just for pickup points _PRO_
* The pickup point list is synced automatically every day, so it always shows the current points without a plugin update
* Good looking, fast and simple map interface
* Zooms into the right county automatically based on the billing postcode, no need to allow location access
* Pricing based on conditions (product category, weight, cart total, volume, etc.)
* Tracking info sync, so you can see the package status in the admin and change the order status automatically _PRO_
* Create your own tracking page for your customers, so you don't need to link to the courier's website _PRO_
* COD fee for pickup points or any other shipping method, as a fixed amount or a percentage _PRO_
* The selected pickup point is stored as the shipping address, so it shows up automatically in every WooCommerce e-mail, the customer's account and the admin
* The selected pickup point can be removed from the order (if the customer wants a different shipping method), or replaced with another point
* You can add your own pickup points, with a custom icon
* You can generate your own labels for the packages, for example if you deliver them yourself _PRO_
* Delivery note handling: for MPL, Foxpost, Express One and DPD you can close the day and create the pickup list (delivery note) _PRO_
* Change the colors and other display settings in the Appearance / Customize / WooCommerce menu
* Works fast and simple on mobile too
* Filter orders by pickup point provider in the order list
* Compatible with Számlázz.hu, Woo Billingo Plus, WooCommerce Shipment Tracking and YITH WooCommerce Order Tracking (PRO version)
* Compatible with the Webshippy and iLogistic fulfillment services

== Installation ==

1. Download the plugin
2. Upload it in WordPress under Plugins / Add New
3. Check the settings under WooCommerce / Settings / Shipping / Pickup points
4. Set the colors under Appearance / Customize / WooCommerce / Pickup points
5. Done

== Screenshots ==

1. Map interface
2. Settings page

== Changelog ==

4.3.1
* Mark GLS lockers that are currently out of order
* Run GLS points update hourly for Hungary
* Security fix: the Kvikk webhook rejects requests without a valid API key
* Security hardening: input handling, output escaping and permission checks improved across the plugin
* Fixed a fatal error when the plugin object was cloned or unserialized
* Marked compatible with the latest WP version

4.3
* More secure PDF storage and access
* Permission check for label printing (security fix, thanks to Alan Karl, AHK Solutions LLC & wellknown.info)
* Fixed bulk ZIP download
* Warning if the labels folder is directly accessible (e.g. on nginx servers)
* Fixed a JS error on the map

4.2.9
* Kvikk automatic pricing includes the weight correction in the calculation
* Fixed COD fee on the checkout block
* Fixed box size calculation

4.2.8
* Kvikk COD validation
* Fixed permission check (security fix)
* Fixed VAT label on the cart and checkout pages when taxes are turned off

4.2.7
* GLS ShipitThermo label size (if you ship abroad too, use this one, or the recommended A6 on A4 paper setting)
* Marked compatible with the latest WooCommerce version
* Checkout block fixes

4.2.6
* Coupon condition for pricing
* Page break between providers in bulk printing, can be turned on with the vp_woo_pont_print_page_break_between_providers filter
* Fixed PHP warning

4.2.5
* Fixed PHP warnings
* Fixed Kvikk automatic pricing with currency switchers
* Marked compatible with the latest WooCommerce version

4.2.4
* Label generation uses the edit_shop_orders capability instead of manage_woocommerce, so it works with the shop manager role too
* Fixed phone number validation on the checkout block

4.2.3
* Updated MPL pickup point list
* WP 7.0 compatibility fixes

4.2.2
* Optional use of the Sameday Romanian API
* Fixed weight calculation with Product Bundles
* Fixed GLS tracking (date)
* The Kvikk sender address selector is only visible for Kvikk providers

4.2.1
* In the provider selector on the map, the first click selects only that provider and hides the others
* Kvikk Sameday fix
* Kvikk sender address selection before label generation
* Fixed Express One tracking
* Fixed geolocation for other countries

4.2
* GLS XXL
* Kvikk Sameday

4.1.2
* Csomagpiac Foxpost fix
* Fixed COD fee tax rate
* Express One label note field
* Fixed GLS label generation for other countries
* Express One maximum insurance amount setting
* Fixed Sameday pickup point list import

4.1.1
* New label button: if the order already has a label that can't be deleted (it's already being shipped), the new label button removes the label data from the order, so you can generate a new label for the same order
* Fixed changing the provider: the fields in the settings reload properly, no need to refresh the page
* Csomagpiac Foxpost

4.1
* Kvikk automatic pricing: calculates the shipping costs based on the current price list and the package weight, so you don't need to set up every provider and country one by one
* Country selector on the checkout block: the pickup point option has a country selector too, and the country selector in the billing details also reloads the pickup point options
* Change the shipping method: inside the order you can switch from pickup point to home delivery (and back) with one click
* The default state of the extra services can be set with code using the vp_woo_pont_extra_service_enabled filter
* The pickup point details are not saved as the shipping address in the customer's account
* The weight condition works for weight correction too
* Fixed printing labels with multiple packages

4.0.6
* Checkout block fix

4.0.5
* Checkout block fixes
* Fixed Kvikk Packeta Z-Box in other countries

4.0.4
* Fixed CheckoutWC translation

4.0.3
* Setting to hide the county field for Hungarian addresses (Woo / Settings / Shipping / Pickup points)
* Fixed the map on custom checkout pages for pickup points in other countries

4.0.2
* Fixed the pickup point selector in the cart when only points in other countries are used
* Fixed replacing the selected point in the admin
* Fixed Sameday point import

4.0.1
* Packeta MPL options are back (probably temporarily) - refresh the provider list in the Packeta settings and add the Packeta MPL options again under pickup points

4.0
* New features in the detailed pricing settings: copy, move up/down, improved country handling
* Weight based pricing: set up weight based pricing in one place in the detailed pricing, no need for separate conditional prices
* Completely new checkout block: shows the pickup point options for each shipping method separately, with a search field and a map button
* Vector map with Kvikk Map (on the block checkout)
* Provider icons for home delivery (on the block checkout, based on the pairing in the label settings)
* The map only loads the points for the billing/shipping country
* Pickup point list import split by country (faster, uses fewer resources)
* GLS Express locker support (+ icon on the map, optional filter)
* Clearer Packeta pickup point settings for other countries
* Kvikk home delivery to other countries with Packeta
* Packeta pickup points in other countries show up separately for each provider, each with its own logo
* Pick Pack Pont by Sameday pickup points instead of the old (no longer working) Pick Pack Pont
* If the default price is left empty, only the options set in the detailed pricing are used
* Checkout block compatibility fixes for the price display in the cart and on the checkout
* Fixed Packeta label generation for pickup points in other countries
* Fixed GLS pickup point import (nicer names)
* Fixed Pactic pickup point handling for other countries
* Fixed Booster currency switcher compatibility

3.7.5.1
* Marked compatible with WP 6.9
* Multi-currency support for the COD fee

3.7.5
* Kvikk fixes for pickup points in other countries
* Booster currency switcher compatibility

3.7.4.1
* Fixed PHP warnings

3.7.4
* Kvikk shipping to other countries

3.7.3
* Csomagpiac shipments with multiple packages
* Fixed GLS pickup points in other countries
* The vp_woo_pont_foxpost_id_field filter changes which Foxpost pickup point ID is used (for compatibility with external systems)

3.7.1
* When replacing a pickup point in the admin, you can select it on the map

3.7
* GLS pickup points in other countries
* Sameday Romania Easybox lockers
* Faster map loading with a lot of points
* Fixed tracking automation
* Csomagpiac fix, guaranteed courier pairing
* Fixed Foxpost pickup point ID

3.6.10
* Compatibility fix for WooCommerce versions before 10.3

3.6.9
* Marked compatible with the latest Woo version
* Currency Switcher for WooCommerce (WPExperts) compatibility

3.6.8
* Optional GLS delivery note generation
* Sameday home delivery to other countries
* Csomagpiac guaranteed courier setting
* Tracking e-mail fixes

3.6.7
* GLS fixes
* Checkout block compatibility fix
* Marked compatible with the latest Woo version

3.6.6
* Fixed Foxpost pickup point list

3.6.5
* Fixed Kvikk PHP warning

3.6.4
* Fixed Kvikk reference number
* Fixed Kvikk A5 label position
* Marked compatible with the latest Woo version

3.6.3
* Checkout block fixes
* Fixed GLS label printing with multiple packages
* Kvikk Foxpost home delivery support
* vp_woo_pont_convert_foxpost_pdf_with_imagick filter, to turn this part off if Imagick doesn't work properly on the server

3.6.2
* MPL bulky service based on shipping class or tag
* Fixed GLS bulk printing with the recommended A6 label size (the thermo label is now placed on A6, so the landscape A6 label doesn't need to be rotated, which caused the issue)
* Fixed JS error on the order admin page

3.6.1
* MPL pickup point labels use the pickup point ID instead of the name

3.6
* Kvikk shipments with multiple packages and extra services
* Trans-Sped ZERO fixes
* Fixed MPL shipping outside the EU

3.5.9.4
* Fixed the COD fee setting: the new payment settings screen doesn't support custom forms, so the setting moved to the COD submenu of the Shipping menu

3.5.9.3
* Checkout block fixes

3.5.9.2
* WooCommerce 9.9.3 compatibility fix

3.5.9.1
* vp_woo_pont_target_order_status_after_label_generated, vp_woo_pont_custom_label_mpdf_config and vp_woo_pont_custom_label_mpdf_template filters

3.5.9
* Express One lockers
* Fixed user role condition
* Fixed a bug with a pickup point that was saved for a logged in customer, but was deleted since
* The category / shipping class condition works for variable products too

3.5.8.1
* Settings fix

3.5.8
* Kvikk Foxpost shipping support
* Fixed DPD reference number
* Marked compatible with the latest WP/WC versions

3.5.7.5
* Alternative solution for Foxpost labels, if Imagick doesn't work properly on the server

3.5.7.2
* Foxpost printing fix: converts the label to PNG with Imagick and works with that

3.5.7.1
* Reverted the Foxpost change in 3.5.7, because the solution wasn't compatible with the GPL license. Label generation works, but bulk printing doesn't work for Foxpost unfortunately, the label can only be downloaded.

3.5.7
* Fixed Foxpost label generation with the recommended A6 size
* CheckoutWC compatibility fix
* Fix for virtual orders

3.5.6
* CheckoutWC compatibility improvements
* The percentage COD fee can optionally include the shipping cost
* A custom field can be used as the reference number in the settings
* Webshippy fix with the Sequential Order Numbers plugin
* Fixed tracking e-mail
* Fixed DPD tracking

3.5.5.1
* Fixed GLS search on the map (accented characters)
* Kvikk bug fixes
* Csomagpiac character limit filter (DPD allows max 35)

3.5.5
* Kvikk DPD pickup point and AlzaBox support

3.5.4.4
* Fixed map search

3.5.4.3
* Kvikk DPD fix

3.5.4.2
* Fixed COD fee on the shortcode checkout

3.5.4.1
* Fixed landscape A4 printing

3.5.4
* Checkout block improvements
* Fixed DPD pricing
* Fixed COD fee on the checkout block

3.5.3
* Bulk printing works for labels with multiple packages
* Fixed COD fee on the checkout block
* GLS INS service
* Marked compatible with WC 9.7

3.5.2.1
* Kvikk GLS and DPD support
* Fixed Packeta labels generated with Foxpost on A4 label sheets
* Csomagpiac label size setting

3.5.1.1
* Fixed Foxpost pickup point database import

3.5.1
* Foxpost Packeta support: in the Foxpost settings you can choose to generate labels with Foxpost for Packeta pickup points and lockers too (add the Packeta points to the map as usual)
* No API key needed to download the Packeta pickup point database

3.5.0.3
* Filled in the required WebshopEngine parameter for GLS

3.5.0.2
* Fixed DPD Weblabel for other countries

3.5.0.1
* Uses mb_substr instead of substr

3.5
* User role and logged in user conditions for pricing
* "Greater or equal" and "less or equal" conditions for pricing
* DPD Weblabel uses 1 kg by default if there's no weight for a pickup point order
* DPD Shipping API fixes: the ref1 parameter is the order number, COD reference number limited to 14 characters

3.4.12
* Uses a middleware for DPD, because Weblabel is being discontinued

3.4.11
* Fixed Sameday insurance

3.4.10
* Fixed COD rounding for GLS pickup points in other countries
* The maximum insurance amount can be set for Sameday in the settings
* Support for all DPD pickup points in other countries

3.4.9
* Csomagpiac bug fix (address length limit)
* Fixed DPD package weight and other Shipping API fixes (postcode in other countries, courier note)
* Small map performance improvement
* GLS: if delivered is selected in the tracking automation, but the package is delivered after being returned, the automation doesn't run
* Checkout block fix with WC 9.5
* Marked compatible with WC 9.5

3.4.8.4
* Fixed phone number for shipping addresses in other countries

3.4.8.3
* Fixed Kvikk package size
* DPD Shipping API: address line 2 in a separate field, to avoid issues with the character limit
* The e-mail sent to the customer shows the provider name in the shipping address too, not just the pickup point type

3.4.8.2
* DPD Shipping API uses the 101 service code for shipping to other countries

3.4.8.1
* Package size support: set the boxes you pack in, and they are paired automatically based on the size of the products in the order, but a custom size can also be entered for each order (required for MPL if you drop off packages at a locker)

3.4.7
* Packeta Foxpost as a separate pickup point
* Fixed COD amount for partially refunded orders
* Simple A6 label size support for DPD
* YayCurrency compatibility
* Option to drop off MPL packages at a locker

3.4.6.1 & 3.4.6.2
* Fixed pickup point import bug

3.4.6
* Kvikk Foxpost support (through Packeta)

3.4.5.2
* WP 6.7 and WC 9.4 compatibility

3.4.5.1
* COD fee bug fix

3.4.5
* Fixed Express One label size
* Fixed DPD Weblabel error handling
* If you use custom order statuses and the automation doesn't run, you can enter the order status ID in the settings
* Fixed hiding COD on the checkout block

3.4.4
* Kvikk QR code scanning for packing
* vp_woo_pont_trigger_tracking_email_* filter, to turn off the tracking e-mail with code

3.4.3.1
* Automation fix
* DPD Weblabel name limited to 40 characters
* Fixed PHP warning during label generation

3.4.3
* Tax class setting for the COD fee
* The company name is sent to DPD Weblabel too
* Fixed DPD delivery note generation
* Fixed PHP warning on the orders page
* vp_woo_pont_label_generate_error action, runs when label generation fails
* Save a number in the "_vp_woo_pont_package_count" order meta to generate a label with multiple packages automatically

3.4.2.2
* GLS COD bug fix

3.4.2.1
* Fixed COD PHP warning

3.4.2
* Fixed GLS EUR and CZK rounding
* The COD fee can be a fixed fee plus a percentage
* Fixed custom label PDF generation
* Kvikk IPN call handling (package deletion)
* Fixed the settings button after a change in a custom field

3.4.1.1
* Fixed duplicate label generation
* Fixed Kvikk label deletion (if it was already deleted in the app, it can be deleted in Woo too)

3.4.1
* DPD Shipping API works for pickup points / AlzaBox too
* Fixed MPL label generation for postapont
* Marked compatible with WooCommerce 9.3

3.4.0.2
* Checkout page bug fix

3.4
* Package weight correction: in the label settings you can set how much to add to the automatically calculated weight based on conditions (for example the weight of the packaging)
* MPL: Coop / Mol / Mediamarkt are merged under the name Postapontok, to keep it simple
* DPD pickup points are split into Pickup points and AlzaBox lockers (so you can set separate prices)
* The pickup point filter in the orders list can filter by provider too
* Sameday multiple package shipping
* Kvikk Fámafutár support
* Fixed Curcy compatibility

3.3.5.1
* Changed API call timeout

3.3.5
* Packeta renamed to Packeta Z-Pont
* New Packeta Z-Box icon
* Fixed pagination for delivery notes

3.3.4
* Fixed Packeta Z-Box pricing in other countries
* The MPL fragile package condition can be set for shipping classes too
* GLS tracking fixes
* Pricing settings bug fix
* Express One multiple package shipping
* Fixed DPD pickup point list import

3.3.3.3
* Kvikk fixes (A5 label size and tracking status color in the orders table)

3.3.3.2
* Fixed DPD Shipping API COD packages

3.3.3.1
* Fixed Kvikk JS bug

3.3.3
* DPD Shipping API fix and DEV mode switch
* Számlázz.hu compatibility improvement: the tracking automation can mark the order as paid, so the invoice is marked as paid too

3.3.2
* DPD Shipping API can be used live instead of Weblabel
* Fixed Postapont import
* Marked compatible with WP 6.6
* Requires Plugins header

3.3.1.2
* Webshippy DPD and Pick Pack Pont fix
* DPD uses the D-COD-PREDICT service if it's set to D-PREDICT without COD
* Marked compatible with the latest Woo version

3.3.1.1
* Delivery notes no longer disappear

3.3.1
* Kvikk fixes
* Trans-Sped ZERO mini box size

3.3.0.2
* Fixed the label created e-mail preview
* Fixed Kvikk tracking refresh

3.3
* Kvikk provider compatibility
* Marked compatible with the latest Woo version
* A -1 price set for exclusion is applied even if the pricing logic is set to the most expensive option
* Faster delivery note loading with HPOS
* VP Extra Fees plugin compatibility fix
* Generating the delivery note doesn't reload the page
* Fixed PHP warning on the settings page
* Fixed accented characters on custom labels
* Fixed Postapont database import (sometimes the source has no coordinates)

3.2.11
* A temporary custom pickup date can be set in the Trans-Sped ZERO settings

3.2.10
* vp_woo_pont_get_carrier_from_order filter

3.2.9.1
* Block checkout fixes

3.2.8.3
* Increased Packeta timeout

3.2.8.1
* GLS bug fix

3.2.8
* DPD import works without login details
* vp_woo_pont_after_foxpost_label_created action, to process and store other data sent by Foxpost
* Fixed Csomagpiac error message display
* MPL bulky handling extra service
* Fixed GLS label download with multiple packages
* Fixed MPL insurance amount for labels with multiple packages
* Fixed Sameday token refresh

3.2.7
* DPD Predict service

3.2.6
* Improved phone number validation
* Fixed price display on the block cart and checkout pages
* Sameday settings fix
* Fixed Csomagpiac MPL label generation

3.2.5
* Fixed GLS pickup point list import (filters out duplicate points)
* Fixed PHP warning in class-pro.php

3.2.4
* Ajax bug fix
* Extra fees compatibility fix

3.2.3
* Packeta MPL support
* Ajax call bug fix

3.2.1
* Compatibility with the extra fees plugin (for example to add an extra fee to a pickup point provider)
* Fixed PHP warning

3.2.0.1
* Cart page bug fix

3.2
* DPD pickup points in other countries
* Csomagpiac MPL support
* Optional notes for the providers on the map, with conditions
* Automation bug fix
* Packeta error logging

3.1.2
* Fixed bulk label generation when you select more orders without reloading the page
* Fragile product tag for Posta, so this extra service is added to the package automatically
* Fixed Foxpost label deletion
* X-Currency compatibility
* GLS tracking info shows the location too
* Placeholder text for the shipping method pairing if no provider is set up yet
* The vp_woo_pont_validate_phone_number filter changes when the phone number is validated (for example not for home delivery)
* The vp_woo_pont_cod_fee filter changes the calculated COD fee
* Fixed PHP warning on the tracking page
* The enabled providers list in the admin shows how many points are available for each provider (database column)

3.1.1
* Price change (doesn't affect existing PRO users)
* Currency switcher plugin support for cart total based pricing
* Restart setup wizard button
* Fixed a JS compatibility issue with some themes
* Fixed Express One label size
* Posta value record correction

3.1.0.1
* CSS bug fix

3.1
* The map color and display settings moved to the Shipping / Pickup points menu
* Optional checkboxes for the provider filters on the map (see the setting above)
* Phone number validation: can be turned on to validate Hungarian phone numbers. It accepts any format as long as it can be converted to a normal +36 number, and only shows an error if that fails.
* WooPayments Multicurrency compatibility
* If the order status changes after label generation, bulk label generation hides the changed orders from the table when you are filtering by an order status
* A few new class names in the pont.php template, to make it easier to style with CSS
* Fixed a bug with orders without a phone number
* Fixed bulk printing of GLS thermo labels
* Fixed MPL label generation with multiple packages and extra services
* Fixed printing labels with multiple pieces
* Fixes for the Astra and Jupiter themes
* The vp_woo_pont_print_js_compat filter loads an alternative print.js file, in case there's an issue with the print preview

3.0.7
* Fixed MPL labels when all extra services are turned off during manual generation, but set in the settings (the settings overwrote the empty selection)
* If no provider is paired with the shipping method, the order page asks you to select one
* Hides the label generation button after manual label generation on the order page
* Delivery notes open on a new page
* The help link in the provider settings points to the right page
* GLS "ExchangeService" service
* With the GLS "ShopReturnService" and "ExchangeService" services, the label is printed on two pages with the recommended A6 label size (since there are 2 labels)
* Fixed missing translations
* Pactic pickup points are downloaded compressed, which should be more efficient
* The tracking number only shows up in the selected e-mail, not in all of them

3.0.6
* Fixed settings (empty values were saved randomly) - if you changed the settings since version 3.0, please check that everything is set correctly on the labels / tracking tabs
* Fixed the print button in the orders table
* Fixed merging in bulk label printing
* Fixed the recommended A6 label size for GLS
* MPL bug fix

3.0
* Bulk label generation: new UI, you can create labels in bulk without reloading the page and without a background process
* Tracking e-mails: the customer gets an e-mail when the package is handed to the courier, ready for pickup at the pickup point, and out for delivery, based on the tracking info
* Completely reorganized, more logical settings
* Portrait A6 label size for GLS and Foxpost (rotates the landscape one)
* Recommended label sizes: the A6 size is available for every provider, and if you use it, bulk printing merges them on A4 sheets no matter which provider they are from
* DPD Shipping API compatibility (still beta)
* Label created e-mail: the generated PDF can be sent to an e-mail address as an attachment (for example to set up automatic printing easily)
* The tracking info can be refreshed in the orders list too, and it shows when it was last refreshed
* Once the package is delivered, the tracking info is not refreshed anymore (except for Posta COD, which has an extra "COD paid" event)
* Tracking info is not synced at night, to avoid unnecessary API calls (the limit can run out quickly with Posta)
* The vp_woo_pont_tracking_sync_interval filter sets how often the tracking info is synced (2 hours by default)
* The Packeta pickup point list is imported from our own database, so there's no need to download/process a 50+ MB JSON file (and it works without an API key)
* HPOS fix for delivery notes
* Template preview for custom labels
* Pickup point selector block fix
* vp_woo_pont_metabox_after_generate_options action, to show extra fields in the label generation box
* Rate meta is only saved when the checkout block is used
* PHP 8.1+ compatibility (tested with 8.3 too)
* The PRO version is active without a license key on .local domains
* Webshippy compatibility fix
* Fixed Posta value record for orders with a 0 total
