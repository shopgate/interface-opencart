# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## 2.9.36 - 2017-09-26
### Changed
- improved database connection setup
- improved item export by using parent-child-relations, when possible

## 2.9.35 - 2017-08-30
### Changed
- migrated Shopgate integration for OpenCart to GitHub
- updated Shopgate Cart Integration SDK to 2.9.68

## 2.9.34
- fixed reading configuration so empty array not seen erroneously  as null or false

## 2.9.33
- import custom order fields to order comment
- fixed tax issues in shipping method cart validation
- updated Shopgate Library to 2.9.59

## 2.9.32
- fixed bug in installation routine
- fixed issue with items which don't require shipping

## 2.9.31
- fixed error in import of orders with configurable products

## 2.9.30
- fixed database error in order import
- improved performance of item export

## 2.9.29
- fixed issue related to not valid coupons and vouchers in cart validation functionality

## 2.9.28
- fixed wrong assignment of shipping methods in order import
- fixed frontend namespace error on servers running php lower than 5.3
- fixed register_customer mysql error when default customer group with id 1 does not exist

## 2.9.27
- fixed coupons applying a 100% discount on invalid coupons in version 1.4.0
- fixed using multiple coupons & vouchers on the same cart
- fixed plugin installer by adding the needed includes
- added a modman file

## 2.9.26
- added compatibility for version higher 2.1.0.2
- fixed bug with missing items in product export

## 2.9.25
- fixed mysqli issue
- updated Shopgate Library to 2.9.42
- removed maximum order quantity for items without stock handling
- fixed tax export
- fixed order total labels for coupons and voucher
- fixed order total amounts on order detail page
- fixed compatibility issues for older OpenCart versions (< 1.5.0.0)
- fixed tax issues related to coupons and shipping
- fixed Shopgate Connect encoding issues
- fixed issue in validation of cart related to empty address state information

## 2.9.24
- fixed tax subtotal in order import

## 2.9.23
- add_order will fail if a used voucher doesn't have a sufficient amount left

## 2.9.22
- added support for coupons that can only be used by logged in customers

## 2.9.21
- fixed issue with POST parameters which caused wrong stock validation
- issue with get_customer

## 2.9.20
- check_cart now handles stock of product options
- voucher support
- fix tax problems

## 2.9.19
- fixed customer group in check_cart in case customer was not logged in
- plugin zip file now complies with the Opencart Extension Installer conventions

## 2.9.18
- updated Shopgate Library to 2.9.27
- get_customer will now return associated customer group and group name
- check_cart will now return associated customer information
- fixed setting "Comment detail level" in module settings
- add_order: refactored/fixed insertion of items/coupons

## 2.9.17
- fixed compatibility issues in method checkCart
- fixed issues with child products in method checkCart response for items
- product variants that are not avaialbe are not exported to Shopgate
- fixed issue with duplicate child products
- fixed a warning in the frontend in old OpenCart versions

## 2.9.16
- cron job for transmitting order shipment status to Shopgate didn't work correctly
- fixed two bugs that broke compatibility with OpenCart 1.3
- the weight gets now exported correctly for child products
- fixed issue with export of product stock in XML
- products with a required option type file are ignored in the export
- fixed compatibility with older versions in method check_cart
- established parent child product export compatibility for version 1.3.4 and higher (before fix it was 1.5.0)
- extended method checkStock with some exception handling
- fixed display issue on the payment modules page in the admin area

## 2.9.15
- added new config settings for mapping Shopgate order statuses to those of Opencart
- fixed various bugs
- check_stock: wrong quantity was returned for child products
- updated Shopgate Library to 2.9.21

## 2.9.14
- updated Shopgate Library to 2.9.19

## 2.9.13
- product options are now exported as parent-child-item relations, if necessary and a max child count is not exceeded

## 2.9.12
- order import comments have been summarized to only show relevant data if the according setting is set to "simple"

## 2.9.11
- fixed small warnings on order import

## 2.9.10
- fixed issue in Shopgate configuration/product export with generating urls
- fixed general backward compatibility issues
- fixed mobile redirect issue while plugin was deactivated
- fixed product export issue caused by product field sort_order
- added missing template for OpenCart v## 2.0.x.x

## 2.9.9
- exporting localized 'In Stock' snippet now

## 2.9.8
- fix invalid array indexes
- fix js issues

## 2.9.7
- export image type options in product xml
- fix order item name issue with quotes
- multi store support added
- net price export issue fixed

## 2.9.6
- added localized strings in order totals and order comments
- added currency formatting for order totals

## 2.9.5
- fix promotion rule end date
- handle serialized shop configuration
- fix address state issues
- send order confirmation mail
- transfer shipping method name to order shipping address
- added XML export
- added support for OpenCart 2.0
- updated Shopgate Library to 2.9.8

## 2.9.4
- fix promotion rule with either undefined start or undefined end date
- added gross market support
- fixed paypal payment notification that breaks add order request
- added support of OpenCart >= 1.3.0 & < 2.0.0
- optimize mobile redirect
- fixed product price display issues at mobile orders in OpenCart backend
- fix update order issue

## 2.9.3
- export products with not supported but required file option as inactive
- catch and process merchant api exceptions
- support for all OpenCart > 1.5.1.3.1 & < 2.0.0 added
- improved redirect script
- updated Shopgate Library to 2.9.6

## 2.9.2
- updated Shopgate Library to 2.9.2
- remove payment and shipping extension to simplify installation
- move Shopgate extension from feed to module
- fix product tax issue on add_order
- activate redeem_coupon action

## 2.9.1
- improve export of product options
- fix problem with umlauts
- fix check_cart issue
- fix address issue in coupon validation

## 2.9.0
- updated Shopgate Library to 2.9.1
- improve export of product availability
- improve database integration
- improve export of product sort order
- export of product upc added
- compatibility with php < 5.3 added
- add user input data to order

## 2.8.3
- support for all OpenCart > 1.5.4 added
- get_customer fix
- fix for empty database field default value

## 2.8.2
- updated Shopgate Library to 2.8.10
- orders now gets transferred from Shopgate into OpenCart
- orders get updated as soon they get payed, shipped or canceled at Shopgate
- cancelled/shipped orders at OpenCart will be market as cancelled/shipped at Shopgate too
- Shopgate Connect: mobile customer can log in with desktop account credentials
- new mobile customer registration get transferred to OpenCart
- added mobile redirect
- allow splitted export for products and categories
- improve sort order logic for categories and products
- plugin query cleanup
- major code rework
- process multiple promotion rules
- implemented get_settings to transfer billing/shipping countries and tax settings to Shopgate
- implemented check_stock for live stock checks
- implemented get_orders to display previous orders in customer account
- implemented check_cart for coupon & item validation and shipping & payment method transfer

## 2.8.1
- improved backend templates
- added authentification
- configuration rework

## 2.8.0
- major code rework
- updated Shopgate Library to 2.8.5
- export categories, products and reviews to Shopgate

[Unreleased]: https://github.com/shopgate/interface-opencart/compare/2.9.35...HEAD
[2.9.36]: 