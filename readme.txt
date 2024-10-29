=== Age Verification for your checkout page. Verify your customer's identity ===
Contributors: agechecker
Tags: age checker, age check, AgeChecker.Net, check age, verify age
Requires at least: 4.4
Tested up to: 6.6
Stable tag: 1.20.0

AgeChecker.Net seamlessly and securely verifies the age of your customers directly on your website during the checkout process.

== Description ==

AgeChecker.Net is more than an age gate anyone can pass by checking a box. We are the leading provider in online age verification for age-restricted industries, offering a true age verification solution designed to prevent underage sales and comply with the latest FDA, federal, and state regulations. We handle age verification so you don't have to manually verify customers or risk selling to underage buyers.

[youtube https://www.youtube.com/watch?v=PCz5dUZqIuc]

### Instant Age Verification
AgeChecker.Net accesses the world’s largest identity networks and a proprietary database of verified customers to instantly verify over 90% of your customers at checkout. Further authentication is necessary for fewer than 10% of your customers, who have simple options to upload an image of their ID to complete a transaction. Eliminate extensive wait times—our 24/7 live team manually verifies IDs in less than a minute, and customers never have to leave your checkout page. Those approved via photo ID are then instantly verified on any website using AgeChecker.Net.

### Enhanced Compliance
For highly regulated industries such as vape and tobacco, alcohol, and firearms, compliance with the latest state and federal regulations is critical to good business practices. AgeChecker.Net makes it easy to stay on top of the latest changes with a flexible system that allows you to customize age verification requirements based on location and modify the verification process to fit your business’s needs.

### Superior Security
AgeChecker.Net takes data privacy very seriously. Information submitted through our secure system is stored solely for the purpose of expediting verification during subsequent transactions, and is never shared or sold. Images of photo IDs are deleted as soon as they are verified, ensuring your customers’ private information will not fall into the wrong hands.

### Optimized Customer Experience
Confusing, slow verification systems can drive frustrated customers away. AgeChecker.Net’s fast and easy process reduces cart abandonment by automatically retrieving information already entered at checkout for verification, eliminating any redundant input.

### What's included with an AgeChecker.Net account:
*   Free, same-day installation by our team
*   Plug-ins for all major ecommerce platforms
*   Flexible API for custom integration
*   ~95% instant verification rate
*   Manual ID verification by our live team in 10 seconds on average
*   Customize minimum age, digital signature requirements, and order blocking by location
*   Disable verification for customer or product groups
*   Reduce chargebacks and fraudulent orders
*   24/7 email and phone support for you and your customers
*   Compliant with FDA, federal, and state regulations
*   Volume discounts available

Demo: [https://agechecker.net/demo](https://agechecker.net/demo)

### Contributors & Developers
AgeChecker.Net is open source software. The following people have contributed to this plugin.

### Contact
For setup support or questions, call us at 1-888-276-2303 or email contact@agechecker.net

== Installation ==

Create an account at [AgeChecker.Net](https://agechecker.net/) and activate it by entering your billing information.

Go to **WooCommerce > Settings > Integration > AgeChecker.Net**

In the AgeChecker.Net settings view, enter the name of your store and the API key. You can find your API key under the **Websites** tab of your AgeChecker.Net account dashboard.

If you are using the default theme, this configuration should work. However custom themes, templates, and extensions may modify the checkout form and change the order button. Right click on the order button on your checkout page and click "Inspect Element", then right click on the element in the DOM viewer and choose "Copy > Copy Selector" to get the unique element selector for the order button.

![Screenshot](https://agechecker.net/images/guides/woocommerce/3.png)

Click "Save changes" when you are finished.

== Changelog ==

= 1.20.0 =
*Release Date - 22 August 2024*

* Adds display wording for 'dropped verification' denial reason in order details for after-payment flow

= 1.19.1 =
*Release Date - 3 July 2024*

* Add check that stops AgeChecker from trying to load twice in the uncommon case that the site loads the script twice in the DOM

= 1.19.0 =
*Release Date - 12 June 2024*

* Add a setting to disable verification initiation if order status is set to "Completed" for the After-Payment workflow

= 1.18.0 =
*Release Date - 11 June 2024*

* After-Payment verification will no longer initiate on order status page if order status is set to "Failed" (This behavior can be reversed if needed by setting the plugin setting "Trigger Popup on Order Failed" to "Enable popup if order status is failed")

= 1.17.0 =
*Release Date - 22 May 2024*

* "Page" setting is now a selectbox for choosing which page to display AgeChecker on (Previously an input box that required manual page slug/ID input)
* Add a setting to give the option of which customer data from checkout is used for the verification
* Changes default value for "Tag Orders" setting to "Tag Order Notes with age verification UUID"

= 1.16.2 =
*Release Date - 7 March 2024*

* Fix warnings about creation of dynamic properties
* Fix error that occurred on some setups at checkout relating to "Category Specific API Keys" setting values returning incorrect variable types
* Add settings validation check to ensure "Account Secret" is filled out, if After-Payment workflow is selected

= 1.16.1 =
*Release Date - 26 February 2024*

* Fix issue that could occur when a product category gets removed from store system that is still stored in "Exclude/Include Categories" AgeChecker plugin settings

= 1.16.0 =
*Release Date - 28 December 2023*

* "Element" setting field's default value on fresh installs is now: **#place_order,.wc-block-components-checkout-place-order-button**
* Adds support for Before-payment workflow to function on WooCommerce block-based checkout pages
  * Site-owners who installed the plugin before this version, and are looking to set their checkout page to block-based, will need to update set their "Element" setting field to **#place_order,.wc-block-components-checkout-place-order-button**

= 1.15.3 =
*Release Date - 30 October 2023*

* Fix order page AgeChecker Status UI not showing for After-payment workflow when High-performance order storage (HPOS) is enabled
* Fix After-payment age verification not sending correctly on some setups

= 1.15.2 =
*Release Date - 19 October 2023*

* Fix hidden product categories not showing in settings
* Fix 'Undefined array key "title"' error from being thrown

= 1.15.1 =
*Release Date - 25 September 2023*

* Changes internally how After-payment workflow detects when user is on the order received page

= 1.15.0 =
*Release Date - 10 February 2023*

* Add "Custom Form Trigger" option to settings

= 1.14.0 =
*Release Date - 30 December 2022*

* Updates how product categories are stored internally in the "Exclude/Include Categories" section. Although the old internal format is backwards compatible with this version, it is recommended to do the following so the categories are stored in the new format:

  1. Take a screenshot or write down every product category you have added to "Exclude/Include Categories"
  2. Remove all categories from the "Exclude/Include Categories" list
  3. Add back the categories
  4. Save settings

= 1.13.4 =
*Release Date - 9 November 2022*

* Update "Tested up to:" version to latest

= 1.13.3 =
*Release Date - 9 November 2022*

* Stops before-payment AgeChecker script from executing in background on order received/confirmation page

= 1.13.2 =
*Release Date - 20 October 2022*

* Checks capabilities as well for role check

= 1.13.1 =
*Release Date - 13 October 2022*

* After-Payment workflow: Don't set status from "Set Order Status when Verification is Pending" if customer is excluded

= 1.13.0 =
*Release Date - 8 July 2022*

* After-Payment workflow: Change step of order submission where order status is set for "Set Order Status when Verification is Pending" (Occurs now after payment capture has been performed)
* After-Payment workflow: "Set Order Status when Verification is Denied" (now called "Set Order Status when Verification is Denied or Failed") also runs if there was an error running the verification or if the user is banned.

= 1.12.0 =
*Release Date - 28 June 2022*

* Add option to overwrite default script that's used for initializing AgeChecker

= 1.11.1 =
*Release Date - 20 June 2022*

* Fixes bug where if Category Specific API Keys are used, an error popup sometimes appears on the order confirmation page.

= 1.11.0 =
*Release Date - 3 June 2022*

* After-Payment workflow: Add ability to automatically set order status depending on verification result
* After-Payment workflow: CSS adjustments on order list for verification statuses

= 1.10.0 =
*Release Date - 17 May 2022*

* Add ability to exclude age verification from orders with a payment total less than a certain amount. Can be set under "Minimum Total" setting.

= 1.9.0 =
*Release Date - 26 January 2022*

* If After Payment workflow is enabled, the verification status will now display in the order list and order details box.

= 1.8.0 =
*Release Date - 31 December 2021*

* Added ability to exclude payment methods from age verification

= 1.7.3 =
*Release Date - 29 November 2021*

* Set customer role (if set) to newly created accounts made through guest orders.
* Give option to assign roles to existing accounts based on email and billing info from guest order.

= 1.7.2 =
*Release Date - 11 November 2021*

* Fix warning about path not existing

= 1.7.1 =
*Release Date - 11 November 2021*

* Add support for "WooCommerce Square" Google Pay and Apple Pay buttons

= 1.7.0 =
*Release Date - 9 November 2021*

* Add option to disable redirection that occurs if user's Javascript is disabled

= 1.6.2 =
*Release Date - 21 October 2021*

* Fixed warning that would throw about plugin's endpoints on newer Wordpress versions

= 1.6.1 =
*Release Date - 20 October 2021*

* Fixed bug where an error would throw about WC()->cart being null on some setups

= 1.6.0 =
*Release Date - 2 October 2021*

* Add a way to assign specific API keys to product categories
* Fix bug with excluded product categories sometimes not correctly loading when using after payment workflow

= 1.5.0 =
*Release Date - 1 September 2021*

* Add setting that allows switching the location from the AgeChecker popup from before the order is submitted, to instead prompt after an order is submitted. 
* Add a setting to automatically add a user role to a logged-in user upon successful age verification.

= 1.4.1 =
*Release Date - 19 August 2021*

* Correct shipping method title function

= 1.4.0 =
*Release Date - 19 August 2021*

* Added ability to exclude shipping methods from age verification

= 1.3.92 =
*Release Date - 8 February 2021*

* Add option to change script location

= 1.3.91 =
*Release Date - 15 January 2021*

* Add option to enable AgeChecker script on all pages

= 1.3.9 =
*Release Date - 4 November 2020*

* Add option in plugin settings to tag orders if age verified through order notes, order custom fields, or both.

= 1.3.8 =
*Release Date - 28 June 2020*

* get_id() is causing issues for some websites. Changing back to calling ->id until we can figure out what's going on.

= 1.3.7 =
*Release Date - 29 May 2020*

* Fixed warning caused by calling ->id instead of ->get_id()

= 1.3.6 =
*Release Date - 23 May 2019*

* Add option to 'Include' product categories for age verification instead of excluding.
* Add option to 'Include' user roles for age verification instead of excluding.