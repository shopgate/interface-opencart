<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
// Heading
$_['heading_title'] = 'Shopgate';

// Text
$_['text_module']  = 'Modules';
$_['text_success'] = 'Success: You have modified Shopgate details!';
$_['text_simple']  = 'Simple';
$_['text_full']    = 'Full';
$_['text_edit']    = 'Edit';

// Entry
$_['entry_status']               = 'Status:';
$_['entry_customer_number']      = 'Customer number<span class="help">Customer number assigned by Shopgate</span>';
$_['entry_shop_number']          = 'Shop number<span class="help">Store number assigned by Shopgate</span>';
$_['entry_apikey']               = 'API key:<span class="help"></span>';
$_['entry_cname']                = 'CName:<span class="help">Redirecting to own URL</span>';
$_['entry_alias']                = 'Alias<span class="help">URL Alias your shopgate store. Do not use special character and space bar</span>';
$_['entry_store']                = 'Store:<span class="help">Select store to publish offer on Shopgate</span>';
$_['entry_server']               = 'Merchant API Mode:<span class="help">If you want to test the service, select the TEST mode</span>';
$_['entry_customer_server_url']  = 'Custom Merchant API Server URL:<span class="help">Only if you want to test the API with a custom URL</span>';
$_['entry_shop_is_active']       = 'Shop is active:<span class="help">Is the shop is active</span>';
$_['entry_encoding']             = 'Encoding:<span class="help">Store encoding. <br />We recommend UTF 8 encoding</span>';
$_['entry_storage']              = 'Stock control:<span class="help">If product stock is 0 then skip products</span>';
$_['entry_price']                = 'Price control:<span class="help">If price is 0 then skip products</span>';
$_['entry_comment_detail_level'] = 'Comment detail level:<span class="help">Please select how much information is entered into the comments section when importing orders.</span>';
$_['entry_default_store']        = '- Default Store -';

$_['entry_order_status_shipping_blocked']     = 'Order status "shipping blocked"';
$_['entry_order_status_shipping_not_blocked'] = 'Order status "shipping not blocked"';
$_['entry_order_status_shipped']              = 'Order status "shipped"';
$_['entry_order_status_canceled']             = 'Order status "canceled"';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Shopgate';
$_['error_required']   = 'Required!';

// Orders
$_['order_comment_processed_by_shopgate'] = "Order processed by Shopgate\nShopgate Order Number: %s\n";
$_['order_comment_test_order']            = '<p style=\"color:red\">This is a test order. Please do not ship.</p>';
$_['order_tax']                           = 'Tax (%0.2f%%)';
$_['order_tax_payment']                   = 'Payment Tax';
$_['order_amount_payment']                = 'Payment Fee';
$_['order_coupon_code']                   = 'Coupon';
$_['order_voucher_code']                  = 'Voucher';
