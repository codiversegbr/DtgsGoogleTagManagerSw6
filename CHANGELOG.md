# 6.4.1
- New: Display payment status in purchase event (key in datalayer: "transactionPaymentStatus") (GTM-GH-36)
- New: Add option to load gtm.js after user gave cookie consent (GTM-GH-27)
- New: Add option to completely deactivate plugin functionality per saleschannel (GTM-GH-26)
- New: Events add_payment_info & add_shipping_info (GTM-GH-14)
- Bugfix: Error „Call to a member function getGuest() on null“ in Checkout when using B2B Suite (GTM-GH-34)
- Bugfix: Add null check to all $item->getPrice() calls (GTM-GH-35)
- Bugfix: use sales channel context for price type configuration (GTM-GH-PR #30, thanks to @bethlehemit)
- Bugfix: JS error on select_item event for products without add to cart button in listings

# 6.4.0
- Bugfix: JS Error on listing pages when showing off-canvas cart and cart is empty (GTM-GH-16)
- Bugfix: Use nullsafe operator when fetching price in checkout (GTM-GH-24)
- SW6.7 compatibility

# 6.3.22
- Bugfix: updateConsentMode using the wrong variable for Google Ad Permissions (GTM-GH-19)
- Change: get Product in Datalayer via ReferenceID instead of ID (GTM-GH-17)
- Bugfix: JS Error on listing pages when showing off-canvas cart and cart is empty (GTM-GH-16)
- Bugfix: gtag is not defined JS Error when using a thirdparty consent service (GTM-GH-21)

# 6.3.21
- Bugfix: Checkout Error for custom products

# 6.3.20
- New: more product data in transactionProducts array (GTM-GH-10)
- New: PCI compliance (GTM-GH-12)
- Bugfix: error when using sliders with empty dynamic product groups (GTM-GH-12)

# 6.3.19
- New: Option to include product ids (Shopware UUIDs) in ecommerce events (GTM-GH-7)
- New: include parameters new_customer und customer_lifetime_value in purchase Event (GTM-GH-3)
- New: include visitorLifetimeOrderCount in datalayer (GTM-GH-6)
- New: change remarketing implementation to new standard (legacy implementation will be removed in future versions) (CDVRS-49)
- New: view_item_list events will now also be fired for shopping world elements (CDVRS-8)

# 6.3.18
- New: trim GTM-IDs when using multiple (CDVRS-61)
- New: remove_from_wishlist is now fired on wishlist (CDVRS-51)

# 6.3.17
- Google Ads Consent Setting is now separate from Shopwares Google Ads Cookie (FD-33043)
- New: view_item_list is now fired on wishlist (CDVRS-51)

# 6.3.16
- New: Added wishlist add/remove events on listing & detail pages
- Bugfix: Price in listing events (FD-33029)
- Bugfix: Comments in JS (CDVRS-56)

# 6.3.15
- Bugfix: Possible Error in Cart when using coupons

# 6.3.14
- New: select_item now fires after using pagination in listing (CDVRS-53)
- New: view_cart now fires when viewing offcanvas cart (CDVRS-52)
- Bugfix: JS Error in listing when buy button is off (CDVRS-54)
- Bugfix: Possible Error on json_decoding GA4 data

# 6.3.13
- Bugfix: Fixed a bug with pagination on listing pages

# 6.3.12
- New: Plugin-Config now also available in Dutch (CDVRS-34)
- New: GenericPageLoadedEvent added as event (FD-32989)
- New: Added more data to select_item event (CDVRS-45)
- Bugfix: Prices in add_to_cart_list are now according to setting in config (CDVRS-46)

# 6.3.11
- New: Added more data to remove_from_cart event
- Bugfix: Noscript Code will now be removed if using plugin in datalayer only mode

# 6.3.10
- New: Consent Mode update is now sent immediately, not just upon page reload (CDVRS-40)

# 6.3.9
- view_item_list-Event now includes item_variant (CDVRS-36)
- view_item_list-Event now includes item_category (CDVRS-36)
- small bugfixes for view_item_list-Event (CDVRS-36)
- Indices added for Events view_cart, confirm_order und purchase. Indices now start globally at 0 instead of 1 (CDVRS-38)

CAUTION: Due to a bug in SW 6.6.6.0, our plugin is not compatible with this version.
Please install SW 6.6.6.1, which is already available for download.

# 6.3.8
- removed old snippet/translation files (CDVRS-35)

# 6.3.7
- New: item_variant Parameter added to checkout events (CDVRS-22)
- New: option to hash enhanced conversion data before sending it to Google (CDVRS-28)
- Update: CookieFirst integration updated (CDVRS-26)
- Bugfix: Undefined array key "item_category" on detail pages (CDVRS-30)

# 6.3.6
- Bugfix: Exception handling for products without UUIDs (e.g. vouchers) (CDVRS-23)

# 6.3.5
- Removed deprecated services

# 6.3.4
- Bugfix: Exception handling for products without UUIDs (e.g. deposit)
- Bugfix: Exception handling for custom products (SW Plugin SwagCustomizedProducts)

# 6.3.3
- New: value-Parameter in add_to_cart Event (CDVRS-1)
- New: Category in "add_to_cart" Event (CDVRS-11)
- New: Parent Product ID in Datalayer (CDVRS-13)
- Bugfix: Detail Page Template is now using component/buy-widget/buy-widget-form.html.twig Template

# 6.3.2
- Bugfix: Missing semicolon in Remarketing Code (FD-32841)
- Bugfix: Loading of Customer Group (FD-32842)
- Bugfix: PHP Warning when entering a voucher code (SW-267207)
- Bugfix: add_to_cart_listing data fixed (SW-267320)
- PageHiding Option removed (Functionality has been discontinued by Google)
- New: Option to chose from different implementation styles for Google Consent Mode. Please review your plugin settings.

# 6.3.1
- Due to the way that Shopware implemented Google's Consent Mode V2, some setups could lose connection to GA. This update fixes this issue. Please check your data after installing this update and read our detailed blogpost: https://www.codiverse.de/google-consent-mode-2-0-infos-und-nutzung/

# 6.3.0
- SW6.6 compatibility

# 6.2.10
- Bugfix for promocodes in Datalayer on purchase confirmation pages

# 6.2.9
- select_item will now also fire when clicking the "Details" button in listing
- Bugfix for products without manufacturer information
- new: Skip code insertion delay on checkout/finish pages

# 6.2.8
- changed twig-block for select_item Event data

# 6.2.7
- bugfix for net prices when already using net prices in frontend
- Optimization for noscript-Tag when using Server-side-tagging (SST)
- increased minimum SW version to 6.5.5.x

# 6.2.6
- update dist files

# 6.2.5
- new plugin option: enable CookieFirst compatibility

# 6.2.4
- Move noscript tag to "base_noscript" twig-block

# 6.2.3
- Changed order of data: regular datalayer first, then GA4 data
- removed double purchase event when using Adwords tracking

# 6.2.2
- compatibility fixes

# 6.2.1
- GA4 Events: Add To Cart Event on listing pages has been renamed to "add_to_cart_list". Please update your GTM settings.
- GA4 Events: Remove From Cart Event now also contains the key "item_name"

# 6.2.0
- this plugin now supports GA4 natively


    CAUTION: This is a major update, ensuring out-of-the-box compatibility to Google Analytics 4. Google announced that the old
    Universal Analytics (UA) will no more be available as per 07/01/2023. Updating to this version will deactivate the old Enhanced
    Ecommerce structure (we included an option to bring it back for a limited amount of time) and activate the new GA4-structure.
    Please check your GTM and GA4 setup immediately after updating to this plugin version. More information will soon be available
    at: https://www.codiverse.de/category/blog/

    The following events are supported by this plugin version:

    - view_item_list
    - view_item
    - view_cart
    - begin_checkout
    - select_item
    - add_to_cart
    - remove_from_cart
    - purchase
    - confirm_order (custom event for the checkout/confirm page)
    - add_payment_info

    The following events have been removed:

    - shopwareGTM.orderCompleted
    - gtmAddToCart
    - gtmRemoveFromCart

# 6.1.49
- critical security bugfix: the option to pass custom GET parameters to GTM has been removed completely due to security issues. Please update the plugin ASAP.

# 6.1.48
- New: Config option "Remove container code"

# 6.1.47
- New: Config option "Delay insertion of GTM main code"

# 6.1.46
- SW6.5 compatibility

# 6.1.45
- Custom JS URLs may now also contain a filename
- Datalayer now contains the manufacturer number on detail pages

# 6.1.44
- Added new value for Enhanced Conversions: transactionCountryIso (ISO 3166-1 ALPHA-2)
- Added new value for Enhanced Conversions: transactionStateName (if availables)
- Changed: aw_feed_country (Adwords Tag) now contains 2-digit country code
- Changed: aw_feed_language (Adwords Tag) now contains 2-digit language code

# 6.1.43
- Price and Quantity in Addtocart Event should be numbers, not strings
- Addtocart and Removefromcart will now be fired regardless of SW Cookie Consent to increase third-party-compatibility. Please review your GTM settings
- Small bugfix for usercentrics code

# 6.1.42
- Optimizations for Add to cart on listing pages

# 6.1.41
- Improvement of backend description

# 6.1.40
- New: provide a custom URL for GTM.js
- new options for using Enhanced Conversion - please review plugin settings!

# 6.1.39
- Adjustments for Custom Products Plugin

# 6.1.38
- GTM base code now contains full HTTPS URL

# 6.1.37
- Fix for noscript Tag

# 6.1.36
- SW6.4.10.0 compatibility

# 6.1.35
- new plugin setting: limit amount of products in impressions-array

# 6.1.34
- New: Customer Email is now part of the finish-page (Key: transactionEmail)

# 6.1.33
- Fixed a bug that could cause a crash in fresh installations
- Fixes for noscript-template

# 6.1.32
- Bugfix for 404 pages
- Bugfix for custom products

#6.1.31
- fixed a bug leading to errors in account and checkout when remarketing was active

# 6.1.30
- ecomm_pagetype 'home' will now be used on frontpage
- Basic Tag Manager Code now included on non-standard shop pages

# 6.1.29
- Adjustments for Custom Products Plugin

# 6.1.28
- Corrected net prices in checkout

# 6.1.27
- added category info to datalayer on listing an detail pages

# 6.1.26
- new plugin option: enable UserCentrics compatibility
- GTM code will now also show up on landingpages

# 6.1.25
- Checkout exception handling

# 6.1.24
- Corrected tax calculation for EU countries in checkout

# 6.1.23
- Checkout exception handling

# 6.1.22
- A products SEO category will now be used as the official category for EE and in datalayer

# 6.1.21
- Remove From Cart Event is now also fired from offcanvas-cart

# 6.1.20
- Include Brand name in EE items
- Include coupon code on finish-page

# 6.1.19
- Bugfix for Sales Channels not using an ID

# 6.1.18
- Bugfix for PHP8 > 8.0.2

# 6.1.17
- SW6.4.0.0 compatibility

# 6.1.16
- Bugfixes in Datalayer Service

# 6.1.15
- Bugfix for Page Hiding

# 6.1.15
- Bugfix for adding promo codes

# 6.1.14
- Bugfixes

# 6.1.13
- Bugfixes for Remarketing

# 6.1.12
- AddtoCart and RemoveFromCart now use sku
- minor bugfixes

# 6.1.11
- Fix Product Number in EE Checkout
- Fix for Tax rate in Checkout
- Add Category Names to EE Checkout

# 6.1.10
- GTM Code on Newsletter Register-/Subscribe-Pages
- Bugfix for tax free countries when net price option is active

# 6.1.9
- Bugfix for carts including promo codes

# 6.1.8
- GTM code will now also appear on error pages

# 6.1.7
- remove GA cookies on preference change

# 6.1.6
- adjustments for the checkout/confirm view injection

# 6.1.5
- bugfix for dispatch methods
- bugfix for empty category trees

# 6.1.4
- bugfix for taxfree countries
- bugfix for listing pages without layout

# 6.1.3
- bugfix for shopping worlds

# 6.1.2
- listing performance update

# 6.1.1
- compatibility fix for third party plugins

# 6.1.0
- include Enhanced Ecommerce Tracking JS Events
- include Adwords Tags

# 6.0.5
- Bugfix for detailpages
- Bugfix for EE

# 6.0.4
- Bugfix for multishops

# 6.0.3
- SW6.2.x compatibility

# 6.0.2
- Enhanced Ecommerce is now available

# 6.0.1
- Remarketing is now available

# 6.0.0
- initial release
